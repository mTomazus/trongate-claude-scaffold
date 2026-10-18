<?php
/** Offline scaffold tests: php tests/installer_test.php. No application/server needed. */
$repo = dirname(__DIR__);
$root = sys_get_temp_dir() . '/trongate_installer_' . bin2hex(random_bytes(6));
mkdir($root, 0700);
$passed = 0;
$failed = 0;

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

function put(string $path, string $contents): void {
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
    file_put_contents($path, $contents);
}

function remove_tree(string $path): void {
    if (is_link($path) || is_file($path)) { unlink($path); return; }
    foreach (scandir($path) as $name) {
        if ($name !== '.' && $name !== '..') remove_tree($path . '/' . $name);
    }
    rmdir($path);
}

function snapshot(string $path): array {
    clearstatcache();
    $result = [];
    $walk = function (string $file) use (&$walk, &$result, $path): void {
        $stat = lstat($file);
        $result[substr($file, strlen($path))] = [
            $stat['mode'], $stat['mtime'], $stat['ctime'],
            is_link($file) ? readlink($file) : (is_file($file) ? hash_file('sha256', $file) : null)
        ];
        if (is_dir($file) && !is_link($file)) {
            foreach (scandir($file) as $name) {
                if ($name !== '.' && $name !== '..') $walk($file . '/' . $name);
            }
        }
    };
    $walk($path);
    return $result;
}

function run_command(array $args, string $cwd, array $extra_env = [], string $input = ''): array {
    $proc = proc_open($args, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd, array_merge(getenv(), $extra_env));
    check(is_resource($proc), 'Could not start process');
    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [proc_close($proc), $out . $err];
}

function fixture(): string {
    global $root;
    $project = $root . '/case_' . bin2hex(random_bytes(4)) . '/project with spaces';
    foreach (['engine', 'modules/welcome/views', 'modules/templates/views', 'public/css', 'config'] as $dir) mkdir($project . '/' . $dir, 0700, true);
    put($project . '/engine/keep.php', "<?php // engine must stay untouched\n");
    put($project . '/config/config.php', "<?php // private fixture config\n");
    return $project;
}

function install(string $project, array $args = [], array $env = []): array {
    global $repo;
    return run_command(['bash', $repo . '/trongate-init.sh', '--skip-docs', ...$args], $project, $env);
}

function scaffold_fixture(): string {
    global $repo, $root;
    $source = $root . '/source_' . bin2hex(random_bytes(4));
    foreach (['trongate-init.sh', 'AGENTS.md', 'CLAUDE.md', '.claude', 'project-files', '_reference'] as $rel) {
        $copy = function (string $from, string $to) use (&$copy): void {
            if (is_dir($from)) {
                mkdir($to, 0700, true);
                foreach (scandir($from) as $name) {
                    if (!in_array($name, ['.', '..', '.git', 'trongate-docs-repo'], true)) $copy($from . '/' . $name, $to . '/' . $name);
                }
            } else {
                if (!is_dir(dirname($to))) mkdir(dirname($to), 0700, true);
                copy($from, $to);
            }
        };
        $copy($repo . '/' . $rel, $source . '/' . $rel);
    }
    return $source;
}

function succeeds(array $result): void {
    check($result[0] === 0, 'Expected exit 0, got ' . $result[0] . "\n" . $result[1]);
}

function rejects_unchanged(string $project, array $args = []): void {
    $before = snapshot(dirname($project));
    [$code, $output] = install($project, $args);
    check($code !== 0, 'Expected rejection: ' . $output);
    check(snapshot(dirname($project)) === $before, 'Rejected install changed files or metadata');
}

function backed_up(string $project, string $rel, string $contents): bool {
    foreach (glob($project . '/.trongate-init-backup/*/' . $rel) as $file) {
        if (file_get_contents($file) === $contents) return true;
    }
    return false;
}

function scenario(string $name, callable $fn): void {
    global $passed, $failed;
    try { $fn(); $passed++; echo "PASS  {$name}\n"; }
    catch (Throwable $e) { $failed++; echo "FAIL  {$name}: {$e->getMessage()}\n"; }
}

try {
    scenario('fresh install uses project-local deployment contract', function () use ($repo): void {
        $p = fixture();
        $engine = snapshot($p . '/engine');
        put(dirname($p) . '/deploy.sh', 'parent custom helper');
        put(dirname($p) . '/.deploy.env', 'parent custom secrets');
        succeeds(install($p));
        check(file_get_contents(dirname($p) . '/deploy.sh') === 'parent custom helper', 'Parent helper changed');
        check(file_get_contents(dirname($p) . '/.deploy.env') === 'parent custom secrets', 'Parent credentials changed');
        check(file_get_contents($p . '/deploy.sh') === file_get_contents($repo . '/project-files/deploy.sh'), 'Wrong deploy helper');
        check(str_contains(file_get_contents($p . '/.deploy.env'), 'DEPLOY_REMOTE_PATH='), 'Wrong deploy env');
        check((fileperms($p . '/.deploy.env') & 0777) === 0600, 'Credentials not private');
        check(is_executable($p . '/deploy.sh'), 'Helper not executable');
        check(trim(file_get_contents($p . '/CLAUDE.md')) === '@AGENTS.md', 'Shared instructions missing');
        check(snapshot($p . '/engine') === $engine, 'Engine changed');
    });

    scenario('first install and force preserve user-owned files and homepage', function (): void {
        $p = fixture();
        $files = ['AGENTS.md', 'CLAUDE.md', 'tests/welcome_test.php', '.deploy.env', 'modules/welcome/Welcome.php', 'modules/welcome/views/default_homepage.php', 'modules/templates/views/public.php'];
        foreach ($files as $rel) put($p . '/' . $rel, 'custom ' . $rel);
        put($p . '/public/css/app.css', 'custom css');
        put($p . '/.claude/commands/new-plan.md', 'custom command');
        succeeds(install($p));
        check(file_get_contents($p . '/public/css/app.css') === 'custom css', 'First run overwrote CSS');
        check(file_get_contents($p . '/.claude/commands/new-plan.md') === 'custom command', 'First run overwrote command');
        succeeds(install($p, ['--force']));
        foreach ($files as $rel) check(file_get_contents($p . '/' . $rel) === 'custom ' . $rel, 'Overwrote ' . $rel);
        check(backed_up($p, 'public/css/app.css', 'custom css'), 'Force backup missing');
    });

    scenario('force refreshes managed files and preserves custom script modes', function () use ($repo): void {
        $p = fixture();
        put($p . '/deploy.sh', 'custom deploy');
        chmod($p . '/deploy.sh', 0600);
        succeeds(install($p));
        check((fileperms($p . '/deploy.sh') & 0777) === 0600, 'Custom script mode changed');
        succeeds(install($p, ['--force']));
        check(backed_up($p, 'deploy.sh', 'custom deploy'), 'Deploy backup missing');
        check(file_get_contents($p . '/deploy.sh') === file_get_contents($repo . '/project-files/deploy.sh'), 'Force did not refresh deploy');
    });

    scenario('homepage reset replaces only explicit protected files with backups', function () use ($repo): void {
        $p = fixture();
        $files = ['modules/welcome/Welcome.php', 'modules/welcome/views/default_homepage.php', 'modules/templates/views/public.php'];
        foreach ($files as $rel) put($p . '/' . $rel, 'custom ' . $rel);
        succeeds(install($p, ['--reset-homepage']));
        foreach ($files as $rel) {
            check(backed_up($p, $rel, 'custom ' . $rel), 'Missing reset backup ' . $rel);
            check(file_get_contents($p . '/' . $rel) === file_get_contents($repo . '/project-files/' . $rel), 'Reset failed ' . $rel);
        }
    });

    scenario('settings merge preserves custom values, JSON types and hooks', function () use ($repo): void {
        $p = fixture();
        $custom = '{"model":"custom","env":{},"list":[],"nullable":null,"custom_float":1.0,"permissions":{"allow":["custom"],"ask":["ask"],"deny":["deny"]},"hooks":{"PostToolUse":[{"matcher":"custom","hooks":[]}],"Stop":[]}}';
        put($p . '/.claude/settings.json', $custom);
        succeeds(install($p));
        $data = json_decode(file_get_contents($p . '/.claude/settings.json'));
        check($data->model === 'custom' && is_object($data->env) && $data->list === [] && $data->nullable === null, 'Lost custom value/type');
        check($data->custom_float === 1.0, 'Changed float to integer');
        check($data->permissions->allow === ['custom'] && $data->permissions->ask === ['ask'], 'Lost custom permissions');
        check($data->hooks->PostToolUse[0]->matcher === 'custom' && $data->hooks->Stop === [], 'Lost custom hook');
        check(count($data->permissions->deny) === 3 && count($data->hooks->PostToolUse) === 2, 'Missing additions');
        check(backed_up($p, '.claude/settings.json', $custom), 'Settings not backed up');
        $bytes = file_get_contents($p . '/.claude/settings.json');
        succeeds(install($p));
        check(file_get_contents($p . '/.claude/settings.json') === $bytes, 'Repeated merge changed settings');
    });

    foreach (['{', '[]', '{"permissions":null}', '{"permissions":"x"}', '{"permissions":{"deny":{}}}', '{"permissions":{"allow":[1]}}', '{"hooks":[]}', '{"hooks":{"PostToolUse":{}}}', '{"custom_id":9223372036854775809}'] as $json) {
        scenario('invalid settings rejected before writes: ' . $json, function () use ($json): void {
            $p = fixture();
            put($p . '/.claude/settings.json', $json);
            rejects_unchanged($p);
        });
    }

    scenario('dry-run creates no files or temps and calls no mutating/network commands', function (): void {
        $source_path = scaffold_fixture();
        foreach ([false, true] as $existing) {
            $p = fixture();
            if ($existing) succeeds(install($p));
            put($p . '/.claude/settings.json', '{"env":{}}');
            $bin = dirname($p) . '/bin';
            mkdir($bin);
            $tmp = dirname($p) . '/tmp';
            mkdir($tmp);
            foreach (['mktemp', 'mkdir', 'cp', 'mv', 'chmod', 'git'] as $cmd) {
                put($bin . '/' . $cmd, "#!/bin/sh\necho forbidden-{$cmd} >&2\nexit 93\n");
                chmod($bin . '/' . $cmd, 0700);
            }
            $before = snapshot(dirname($p));
            $source = snapshot($source_path);
            // No --skip-docs: dry-run must suppress the docs network action itself.
            succeeds(run_command(['bash', $source_path . '/trongate-init.sh', '--dry-run', '--force', '--reset-homepage'], $p, ['PATH' => $bin . ':' . getenv('PATH'), 'TMPDIR' => $tmp]));
            check(snapshot(dirname($p)) === $before, 'Dry-run changed project/parent/temp metadata');
            check(snapshot($source_path) === $source, 'Dry-run changed scaffold');
        }
    });

    scenario('missing source and invalid template settings fail before writes', function (): void {
        $source = scaffold_fixture();
        foreach (['missing', '{"hooks":null}', '[]'] as $case) {
            $p = fixture();
            if ($case === 'missing') unlink($source . '/project-files/deploy.sh');
            else put($source . '/.claude/settings.json', $case);
            $before = snapshot(dirname($p));
            [$code, $output] = run_command(['bash', $source . '/trongate-init.sh', '--skip-docs'], $p);
            check($code === 1, 'Invalid source accepted: ' . $output);
            check(snapshot(dirname($p)) === $before, 'Invalid source changed project');
            if ($case === 'missing') {
                global $repo;
                copy($repo . '/project-files/deploy.sh', $source . '/project-files/deploy.sh');
            }
        }
    });

    foreach (['deploy.sh', '.deploy.env', '.claude', 'modules/welcome', '.trongate-init-backup'] as $rel) {
        scenario('symlink rejected before writes: ' . $rel, function () use ($rel): void {
            $p = fixture();
            $outside = dirname($p) . '/outside';
            mkdir($outside);
            if (is_dir($p . '/' . $rel)) remove_tree($p . '/' . $rel);
            symlink($outside, $p . '/' . $rel);
            rejects_unchanged($p, ['--force']);
        });
    }

    scenario('blocked parent and invalid project/state are rejected before writes', function (): void {
        $p = fixture(); put($p . '/tools', 'blocked'); rejects_unchanged($p);
        $p = fixture(); put($p . '/.trongate-init-state', 'trongate-init-v2:unknown'); rejects_unchanged($p);
        $p = fixture(); rmdir($p . '/modules/templates/views'); rejects_unchanged($p);
        $p = fixture(); rejects_unchanged($p, ['--unknown']);
    });

    scenario('legacy and interrupted installs preserve existing files', function (): void {
        foreach (['legacy', 'trongate-init-v2:installing:new', 'trongate-init-v2:installing:existing', 'trongate-init-v2:complete'] as $state) {
            $p = fixture();
            if ($state === 'legacy') put($p . '/_reference/trongate-memory.md', 'legacy memory');
            else put($p . '/.trongate-init-state', $state . "\n");
            put($p . '/modules/welcome/Welcome.php', 'custom homepage');
            put($p . '/tools/phpstan.sh', 'custom tool');
            succeeds(install($p));
            check(file_get_contents($p . '/modules/welcome/Welcome.php') === 'custom homepage', 'Homepage overwritten');
            check(file_get_contents($p . '/tools/phpstan.sh') === 'custom tool', 'Tool overwritten');
            check(trim(file_get_contents($p . '/.trongate-init-state')) === 'trongate-init-v2:complete', 'State incomplete');
        }
    });

    scenario('gitignore is additive and repeated installation creates no backups', function (): void {
        $p = fixture(); put($p . '/.gitignore', 'custom-ignore');
        succeeds(install($p));
        $ignore = file_get_contents($p . '/.gitignore');
        check(str_starts_with($ignore, 'custom-ignore') && str_contains($ignore, "\n.deploy.env\n"), 'Ignore entries lost');
        $backups = snapshot($p . '/.trongate-init-backup');
        succeeds(install($p));
        check(file_get_contents($p . '/.gitignore') === $ignore, 'Duplicate ignore entries');
        check(snapshot($p . '/.trongate-init-backup') === $backups, 'Unnecessary backups');
    });

    scenario('deployment uses local source, protected exclusions, preview and confirmation', function (): void {
        $p = fixture(); succeeds(install($p));
        put($p . '/.deploy.env', "DEPLOY_USER=tester\nDEPLOY_HOST=example.invalid\nDEPLOY_PORT=22\nDEPLOY_REMOTE_PATH=/srv/app\n");
        $bin = dirname($p) . '/bin'; mkdir($bin);
        put($bin . '/rsync', "#!/bin/sh\nprintf '%s\\n' \"\$@\"\n"); chmod($bin . '/rsync', 0700);
        $env = ['PATH' => $bin . ':' . getenv('PATH'), 'DEPLOY_USER' => 'inherited', 'DEPLOY_HOST' => 'inherited.invalid', 'DEPLOY_REMOTE_PATH' => '/inherited', 'DEPLOY_PORT' => '1234'];
        [$code, $out] = run_command(['bash', $p . '/deploy.sh'], dirname($p), $env);
        succeeds([$code, $out]);
        check(str_contains($out, "\n--dry-run\n") && str_contains($out, "\n" . $p . "/\n"), 'Wrong preview/source');
        foreach (['/.trongate-init-backup/', '/.trongate-init-state*', '.deploy.env', '.claude/', '.codex/', '.agents/', '/tests/', '/tools/', 'config/'] as $excluded) check(str_contains($out, "\n" . $excluded . "\n"), 'Missing exclusion ' . $excluded);
        check(str_contains($out, 'tester@example.invalid:/srv/app/'), 'Wrong remote');
        put($p . '/.deploy.env', "DEPLOY_USER=tester\nDEPLOY_HOST=example.invalid\nDEPLOY_REMOTE_PATH=/srv/app\n");
        [$code, $out] = run_command(['bash', $p . '/deploy.sh'], $p, $env);
        succeeds([$code, $out]);
        check(str_contains($out, "\nssh -p 22\n"), 'Default port came from inherited environment');
        foreach (["n\n", ''] as $input) {
            [$code, $out] = run_command(['bash', $p . '/deploy.sh', '--live'], dirname($p), $env, $input);
            succeeds([$code, $out]);
            check(str_contains($out, 'Aborted.') && !str_contains($out, '--human-readable'), 'Decline invoked rsync');
        }
        [$code, $out] = run_command(['bash', $p . '/deploy.sh', '--live'], dirname($p), $env, "y\n");
        succeeds([$code, $out]);
        check(str_contains($out, "\n--delete\n") && !str_contains($out, "\n--dry-run\n"), 'Confirmed live stub not invoked');
        // Invalid arguments are rejected before the trusted shell settings execute.
        put($p . '/.deploy.env', 'exit 87');
        check(run_command(['bash', $p . '/deploy.sh', '--live', 'extra'], $p, $env)[0] === 1, 'Extra arguments accepted/settings sourced');
    });

    scenario('deployment rejects invalid, placeholder and legacy settings before rsync', function (): void {
        $p = fixture(); succeeds(install($p));
        $bin = dirname($p) . '/bin'; mkdir($bin);
        put($bin . '/rsync', "#!/bin/sh\nexit 88\n"); chmod($bin . '/rsync', 0700);
        $env = ['PATH' => $bin . ':' . getenv('PATH'), 'DEPLOY_USER' => 'inherited', 'DEPLOY_HOST' => 'inherited.invalid', 'DEPLOY_REMOTE_PATH' => '/inherited', 'DEPLOY_PORT' => '1234'];
        $valid = "DEPLOY_USER=tester\nDEPLOY_HOST=example.invalid\nDEPLOY_PORT=22\nDEPLOY_REMOTE_PATH=/srv/app\n";
        $configs = ['', file_get_contents($p . '/.deploy.env'), $valid . "DEPLOY_SOURCE=old\n", $valid . "DEPLOY_PATH=/old\n", $valid . "DEPLOY_SOURCE=\n", $valid . "DEPLOY_PATH=\n"];
        foreach (['/', '/srv/../', '/./', '/srv/app;touch', '/srv/my app'] as $path) $configs[] = str_replace('/srv/app', '"' . $path . '"', $valid);
        foreach (['0', '65536', 'x', '22 -oProxyCommand=bad'] as $port) $configs[] = str_replace('PORT=22', 'PORT="' . $port . '"', $valid);
        foreach ($configs as $config) {
            put($p . '/.deploy.env', $config);
            [$code, $out] = run_command(['bash', $p . '/deploy.sh'], $p, $env);
            check($code === 1, 'Invalid configuration reached rsync or shell: ' . $code . ' ' . $out);
        }
        put($p . '/.deploy.env', $valid);
        remove_tree($p . '/engine');
        check(run_command(['bash', $p . '/deploy.sh'], $p, $env)[0] === 1, 'Invalid source accepted');
    });
} finally {
    remove_tree($root);
}

echo "\n" . ($passed + $failed) . " tests, {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
