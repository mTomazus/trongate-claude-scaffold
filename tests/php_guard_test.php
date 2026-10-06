<?php
/**
 * Tests for .claude/hooks/php_guard.php. Scaffold-only: trongate-init never
 * copies this into a project.
 *
 *   php tests/php_guard_test.php
 *
 * Each case writes a fixture into a throwaway project tree, pipes a
 * PostToolUse payload into the guard, and checks the exit code and message.
 *
 * Fixtures are single-line strings with \n escapes on purpose: the guard also
 * checks this file, and a fixture line starting with "class" or "namespace"
 * would trip it.
 *
 * Exit codes: 0 all passed, 1 at least one failed.
 */

$guard = dirname(__DIR__) . '/.claude/hooks/php_guard.php';
$root = sys_get_temp_dir() . '/php_guard_test_' . getmypid();

$composer_cmd = 'composer' . ' require';

$cases = [
    // [name, relative path, contents (null = do not create), expected exit, expected message fragment]
    ['clean controller passes', 'modules/teams/Teams.php',
        "<?php\nclass Teams extends Trongate {\n    public function index(): void {}\n}\n", 0, ''],
    ['clean model passes', 'modules/teams/Teams_model.php',
        "<?php\nclass Teams_model extends Model {\n}\n", 0, ''],
    ['view ending in an echo tag passes', 'modules/teams/views/form.php',
        "<?php echo 'x'; ?>\n<p>Hi</p>\n<?= form_close() ?>\n", 0, ''],
    ['non-php file in a module passes', 'modules/teams/assets/app.js',
        "console.log('ok');\n", 0, ''],
    ['missing file is ignored', 'modules/teams/Gone.php', null, 0, ''],
    ['files under .claude are skipped', '.claude/hooks/anything.php',
        "<?php\nclass Wrong_name {}\n", 0, ''],

    ['trailing ?> fails', 'modules/teams/Teams.php',
        "<?php\nclass Teams extends Trongate {\n}\n?>\n", 2, 'trailing ?>'],
    ['syntax error fails', 'modules/teams/Teams.php',
        "<?php\nclass Teams extends Trongate {\n", 2, 'Syntax error'],
    ['controllers subfolder fails', 'modules/teams/controllers/Teams.php',
        "<?php\nclass Teams extends Trongate {\n}\n", 2, 'controllers/ subfolders'],
    ['hyphenated module folder fails', 'modules/team-list/Team_list.php',
        "<?php\nclass Team_list extends Trongate {\n}\n", 2, 'lowercase snake_case'],
    ['_module suffix fails', 'modules/teams_module/Teams_module.php',
        "<?php\nclass Teams_module extends Trongate {\n}\n", 2, '_module suffix'],
    ['two classes fail', 'modules/teams/Teams.php',
        "<?php\nclass Teams extends Trongate {\n}\nclass Helper {\n}\n", 2, 'One class per file'],
    ['class name mismatch fails', 'modules/teams/Teams.php',
        "<?php\nclass Team extends Trongate {\n}\n", 2, 'must match the filename'],
    ['empty constructor fails', 'modules/teams/Teams.php',
        "<?php\nclass Teams extends Trongate {\n    public function __construct() {\n        parent::__construct();\n    }\n}\n", 2, 'empty constructor'],
    ['stray namespace fails', 'modules/teams/Teams.php',
        "<?php\nnamespace App;\nclass Teams extends Trongate {\n}\n", 2, 'namespaces'],
    ['composer command in code fails', 'modules/teams/Teams.php',
        "<?php\n// run {$composer_cmd} foo/bar\nclass Teams extends Trongate {\n}\n", 2, 'Composer is banned'],
    ['composer.json fails', 'composer.json',
        "{}\n", 2, 'bans Composer'],
    ['vendor folder fails', 'vendor/foo/bar.php',
        "<?php\n", 2, 'bans Composer'],
];

$passed = 0;
$failed = 0;

foreach ($cases as [$name, $rel, $contents, $want_exit, $want_msg]) {
    exec('rm -rf ' . escapeshellarg($root));
    $path = $root . '/' . $rel;

    if ($contents !== null) {
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $contents);
    }

    [$exit, $stderr] = run_guard($guard, $path);

    $ok = $exit === $want_exit && ($want_msg === '' || str_contains($stderr, $want_msg));
    report($ok, $name, "expected exit {$want_exit}" . ($want_msg !== '' ? " with \"{$want_msg}\"" : '') . ", got {$exit}", $stderr);
}

// Namespaces are allowed once the project already uses them elsewhere.
exec('rm -rf ' . escapeshellarg($root));
@mkdir($root . '/modules/shop', 0777, true);
@mkdir($root . '/modules/teams', 0777, true);
file_put_contents($root . '/modules/shop/Shop.php', "<?php\nnamespace App;\nclass Shop {\n}\n");
file_put_contents($root . '/modules/teams/Teams.php', "<?php\nnamespace App;\nclass Teams {\n}\n");
[$exit, $stderr] = run_guard($guard, $root . '/modules/teams/Teams.php');
report($exit === 0, 'namespace passes when another module already uses one', "expected exit 0, got {$exit}", $stderr);

exec('rm -rf ' . escapeshellarg($root));

echo "\n" . ($passed + $failed) . " tests, {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);

/**
 * Pipes a PostToolUse payload for $path into the guard.
 *
 * @return array{0:int,1:string} exit code and stderr
 */
function run_guard(string $guard, string $path): array {
    $payload = json_encode(['tool_input' => ['file_path' => $path]]);
    $proc = proc_open(
        [PHP_BINARY, $guard],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    fwrite($pipes[0], $payload);
    fclose($pipes[0]);
    stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($proc), $stderr];
}

function report(bool $ok, string $name, string $detail, string $stderr): void {
    global $passed, $failed;

    if ($ok) {
        $passed++;
        echo "  PASS  {$name}\n";
        return;
    }

    $failed++;
    echo "  FAIL  {$name}\n        {$detail}\n";
    if (trim($stderr) !== '') {
        echo '        ' . str_replace("\n", "\n        ", trim($stderr)) . "\n";
    }
}
