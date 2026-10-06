<?php
/**
 * Zero-dependency HTTP test runner for Trongate.
 *
 * Usage:
 *   php tests/run.php              run every tests/*_test.php
 *   php tests/run.php welcome      run tests/welcome_test.php only
 *
 * Base URL resolution order:
 *   1. TEST_BASE_URL environment variable
 *   2. BASE_URL from config/config.php
 *
 * Exit codes:
 *   0  every test passed
 *   1  at least one test failed
 *   2  the suite could not run (no base URL, no server, no curl)
 *
 * Exit code 2 exists so that "could not run" is never mistaken for "passed".
 */

$tests = [];
$base_url = '';
$cookie_jar = '';

/**
 * Registers a test. Called from tests/*_test.php files.
 */
function test(string $name, callable $fn): void {
    global $tests;
    $tests[] = ['name' => $name, 'fn' => $fn];
}

/**
 * Performs an HTTP request against the application under test.
 * Cookies persist across every request in a run, so login flows work.
 *
 * @return array{status:int,body:string,headers:string,location:string,url:string}
 */
function tg_request(string $method, string $path, array $fields = [], array $headers = []): array {
    global $base_url, $cookie_jar;

    $url = rtrim($base_url, '/') . '/' . ltrim($path, '/');

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_COOKIEJAR => $cookie_jar,
        CURLOPT_COOKIEFILE => $cookie_jar,
        CURLOPT_HTTPHEADER => $headers
    ]);

    if ($fields !== []) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    }

    $raw = curl_exec($ch);

    if ($raw === false) {
        $error = curl_error($ch);
        curl_close($ch);
        tg_fail("Request to {$url} failed: {$error}");
    }

    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $header_size = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $head = substr($raw, 0, $header_size);
    $body = substr($raw, $header_size);

    $location = '';
    if (preg_match('/^Location:\s*(.+)$/mi', $head, $match) === 1) {
        $location = trim($match[1]);
    }

    return [
        'status' => $status,
        'body' => $body,
        'headers' => $head,
        'location' => $location,
        'url' => $url
    ];
}

/**
 * The base URL the suite resolved, for assertions that need to name it.
 */
function tg_base_url(): string {
    global $base_url;
    return $base_url;
}

function tg_get(string $path, array $headers = []): array {
    return tg_request('GET', $path, [], $headers);
}

function tg_post(string $path, array $fields = [], array $headers = []): array {
    return tg_request('POST', $path, $fields, $headers);
}

function assert_status(int $expected, array $res): void {
    if ($res['status'] === $expected) {
        return;
    }
    tg_fail("Expected HTTP {$expected} from {$res['url']}, got {$res['status']}." . tg_excerpt($res['body']));
}

function assert_body_contains(string $needle, array $res): void {
    if (str_contains($res['body'], $needle)) {
        return;
    }
    tg_fail("Expected {$res['url']} to contain \"{$needle}\"." . tg_excerpt($res['body']));
}

function assert_body_lacks(string $needle, array $res): void {
    if (!str_contains($res['body'], $needle)) {
        return;
    }
    tg_fail("Expected {$res['url']} NOT to contain \"{$needle}\", but it does." . tg_excerpt($res['body']));
}

function assert_redirects_to(string $expected_fragment, array $res): void {
    if ($res['status'] < 300 || $res['status'] > 399) {
        tg_fail("Expected a redirect from {$res['url']}, got HTTP {$res['status']}." . tg_excerpt($res['body']));
    }
    if (!str_contains($res['location'], $expected_fragment)) {
        tg_fail("Expected {$res['url']} to redirect to something containing \"{$expected_fragment}\", got \"{$res['location']}\".");
    }
}

function assert_same($expected, $actual, string $label = 'value'): void {
    if ($expected === $actual) {
        return;
    }
    tg_fail(sprintf(
        "Expected %s to be %s, got %s.",
        $label,
        var_export($expected, true),
        var_export($actual, true)
    ));
}

function assert_true($actual, string $label = 'condition'): void {
    if ($actual === true) {
        return;
    }
    tg_fail("Expected {$label} to be true, got " . var_export($actual, true) . '.');
}

/**
 * Aborts the current test. Every assertion routes through here.
 */
function tg_fail(string $message): void {
    throw new Exception($message);
}

/**
 * Renders a short, indented slice of a response body for failure output.
 */
function tg_excerpt(string $body): string {
    $body = trim(preg_replace('/\s+/', ' ', $body));

    if ($body === '') {
        return "\n--- response body was empty ---";
    }

    $slice = substr($body, 0, 300);
    if (strlen($body) > 300) {
        $slice .= ' ...';
    }

    return "\n--- response ---\n" . $slice;
}

/**
 * Finds the line inside a *_test.php file that triggered a failure.
 */
function tg_origin(Throwable $e): string {
    $frames = array_merge([['file' => $e->getFile(), 'line' => $e->getLine()]], $e->getTrace());

    foreach ($frames as $frame) {
        if (isset($frame['file']) && str_ends_with($frame['file'], '_test.php')) {
            return basename(dirname($frame['file'])) . '/' . basename($frame['file']) . ':' . ($frame['line'] ?? 0);
        }
    }

    return 'unknown location';
}

function tg_colour(string $text, string $colour): string {
    if (!stream_isatty(STDOUT)) {
        return $text;
    }
    $codes = ['green' => '0;32', 'red' => '0;31', 'grey' => '0;90'];
    return "\033[{$codes[$colour]}m{$text}\033[0m";
}

/**
 * Stops the run before any test executes. Used for setup problems only.
 */
function tg_abort(string $message): void {
    fwrite(STDERR, "\nCannot run the suite.\n\n  " . str_replace("\n", "\n  ", $message) . "\n\n");
    exit(2);
}

// --- setup ---------------------------------------------------------------

$project_root = dirname(__DIR__);

if (!function_exists('curl_init')) {
    tg_abort("The curl extension is not enabled for the PHP CLI.\nEnable ext-curl, then run this again.");
}

$base_url = getenv('TEST_BASE_URL') ?: '';

if ($base_url === '') {
    $config_path = $project_root . '/config/config.php';
    if (is_file($config_path)) {
        require_once $config_path;
        $base_url = defined('BASE_URL') ? BASE_URL : '';
    }
}

if ($base_url === '') {
    tg_abort(
        "No base URL found.\n" .
        "Either set BASE_URL in config/config.php, or run:\n\n" .
        "  TEST_BASE_URL=http://localhost:3000 php tests/run.php"
    );
}

$cookie_jar = tempnam(sys_get_temp_dir(), 'tg_test_cookies_');

// Preflight: prove the server answers before blaming the application.
$probe = curl_init();
curl_setopt_array($probe, [
    CURLOPT_URL => $base_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_NOBODY => true
]);
curl_exec($probe);
$probe_error = curl_error($probe);
curl_close($probe);

if ($probe_error !== '') {
    tg_abort(
        "No server responded at {$base_url}\n  ({$probe_error})\n\n" .
        "Start the web server that serves this project, then run this again.\n" .
        "If it is already running, BASE_URL in config/config.php probably does\n" .
        "not match the address it is actually served on."
    );
}

// --- discovery -----------------------------------------------------------

$filter = $argv[1] ?? '';
$pattern = $filter === '' ? '*_test.php' : $filter . '_test.php';
$files = glob(__DIR__ . '/' . $pattern);

if ($files === false || $files === []) {
    tg_abort("No test files matched tests/{$pattern}");
}

foreach ($files as $file) {
    require_once $file;
}

// --- run -----------------------------------------------------------------

echo "\nTrongate test suite\n";
echo tg_colour("Base URL: {$base_url}\n\n", 'grey');

$passed = 0;
$failures = [];

foreach ($tests as $t) {
    try {
        ($t['fn'])();
        $passed++;
        echo '  ' . tg_colour('PASS', 'green') . '  ' . $t['name'] . "\n";
    } catch (Throwable $e) {
        $failures[] = ['name' => $t['name'], 'error' => $e];
        echo '  ' . tg_colour('FAIL', 'red') . '  ' . $t['name'] . "\n";
        echo tg_colour('        ' . str_replace("\n", "\n        ", $e->getMessage()) . "\n", 'grey');
        echo tg_colour('        at ' . tg_origin($e) . "\n", 'grey');
    }
}

@unlink($cookie_jar);

$total = count($tests);
$failed = count($failures);

echo "\n{$total} tests, {$passed} passed, {$failed} failed\n\n";

exit($failed === 0 ? 0 : 1);
