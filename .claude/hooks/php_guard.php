<?php
/**
 * PostToolUse guard for Trongate projects.
 *
 * Every rule below is already written in CLAUDE.md. This file makes them
 * deterministic instead of advisory: prose gets drifted from, a non-zero
 * exit code does not.
 *
 * Exit 0 = clean. Exit 2 = violations on stderr, fed back to Claude to fix.
 */

$payload = json_decode(stream_get_contents(STDIN), true);
$path = $payload['tool_input']['file_path'] ?? '';

if ($path === '' || !is_file($path)) {
    exit(0);
}

// The guard's own tooling names the patterns it searches for, so checking
// .claude/ against these rules only ever produces false positives.
if (str_contains($path, '/.claude/')) {
    exit(0);
}

$violations = [];
$name = basename($path);
$is_php = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php';

// --- rules that apply to any file ---------------------------------------

if ($name === 'composer.json' || str_contains($path, '/vendor/')) {
    $violations[] = 'This project bans Composer for application code. Build a Trongate module instead. '
        . 'Dev-only tooling belongs in tools/ as a pinned .phar, never in vendor/.';
}

$module = '';
if (preg_match('#/modules/([^/]+)/#', $path, $match) === 1) {
    $module = $match[1];

    if (preg_match('/^[a-z][a-z0-9_]*$/', $module) !== 1) {
        $violations[] = "Module folder \"{$module}\" must be lowercase snake_case with no hyphens.";
    }

    if (str_ends_with($module, '_module')) {
        $violations[] = "Module folder \"{$module}\" must not carry a _module suffix.";
    }

    if (preg_match('#/modules/[^/]+/controllers/#', $path) === 1) {
        $violations[] = 'controllers/ subfolders are the Trongate v1 pattern. '
            . 'The controller sits directly in the module folder.';
    }
}

if (!$is_php) {
    report_and_exit($violations, $path);
}

// --- PHP-only rules ------------------------------------------------------

$contents = (string) file_get_contents($path);

$lint = [];
$lint_status = 0;
exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1', $lint, $lint_status);

if ($lint_status !== 0) {
    // A file that will not parse makes every other check meaningless.
    $first = trim((string) (array_values(array_filter(array_map('trim', $lint)))[0] ?? 'php -l failed'));
    report_and_exit(['Syntax error: ' . $first], $path);
}

if (str_ends_with(rtrim($contents), '?>') && !ends_with_echo_tag($contents)) {
    $violations[] = 'Remove the trailing ?> from the end of the file.';
}

if (str_contains($contents, 'composer require') || str_contains($contents, 'composer install')) {
    $violations[] = 'Composer is banned in this project. Build a Trongate module instead.';
}

if (preg_match('/^\s*namespace\s+/m', $contents) === 1 && !project_uses_namespaces($path)) {
    $violations[] = 'Trongate modules do not declare namespaces, and nothing else in modules/ does either.';
}

$class_count = preg_match_all('/^\s*(?:abstract\s+|final\s+)?class\s+(\w+)/m', $contents, $classes);

if ($class_count > 1) {
    $violations[] = "One class per file. This file declares {$class_count}: " . implode(', ', $classes[1]) . '.';
}

if ($class_count === 1) {
    $expected = pathinfo($path, PATHINFO_FILENAME);
    if ($classes[1][0] !== $expected) {
        $violations[] = "Class \"{$classes[1][0]}\" must match the filename, so it should be \"{$expected}\".";
    }
}

if (preg_match('/function\s+__construct\s*\([^)]*\)\s*\{\s*parent::__construct\s*\(\s*\)\s*;?\s*\}/', $contents) === 1) {
    $violations[] = 'Remove the empty constructor that only calls parent::__construct().';
}

report_and_exit($violations, $path);

/**
 * Reports any violations to stderr and ends the hook.
 */
function report_and_exit(array $violations, string $path): void {
    if ($violations === []) {
        exit(0);
    }

    fwrite(STDERR, "CLAUDE.md violations in {$path}:\n");
    foreach ($violations as $violation) {
        fwrite(STDERR, "  - {$violation}\n");
    }
    fwrite(STDERR, "\nFix these before continuing.\n");
    exit(2);
}

/**
 * Distinguishes a view ending in "<?= form_close() ?>" from a stray closing tag.
 *
 * The rule bans a bare ?> closing a <?php block at end of file. A short echo
 * tag legitimately ends with ?> and is the normal way a Trongate view emits its
 * last value, so it must not be flagged.
 */
function ends_with_echo_tag(string $contents): bool {
    $trimmed = rtrim($contents);

    if (!str_ends_with($trimmed, '?>')) {
        return false;
    }

    $body = substr($trimmed, 0, -2);
    $echo_pos = strrpos($body, '<?=');

    if ($echo_pos === false) {
        return false;
    }

    $php_pos = strrpos($body, '<?php');

    return $php_pos === false || $echo_pos > $php_pos;
}

/**
 * Checks whether namespaces are already an established convention here,
 * so the guard does not fight a codebase that legitimately uses them.
 */
function project_uses_namespaces(string $path): bool {
    $root = strstr($path, '/modules/', true);

    if ($root === false) {
        return false;
    }

    foreach (glob($root . '/modules/*/*.php') ?: [] as $file) {
        if ($file !== $path && preg_match('/^\s*namespace\s+/m', (string) file_get_contents($file)) === 1) {
            return true;
        }
    }

    return false;
}
