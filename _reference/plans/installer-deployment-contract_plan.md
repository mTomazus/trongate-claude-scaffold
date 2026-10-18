# Plan: installer and deployment contract

Date: 2026-10-18. Tier 2. Status: Complete.
Spec: `_reference/specs/installer-deployment-contract.md`.
Branch: `feature/installer-deployment-contract` (from the current local base).

1. Align the deployment templates with the installer's project-local v2 contract;
   guard configuration and sync exclusions. Commit this bounded change.
2. Preserve existing files by default, validate settings/unsafe paths before
   writes, and make installer dry-run read-only. Add offline regression tests
   that exercise the real scripts, then commit the installer and tests.
3. Update README to match the contract and verification commands. Run integrated
   checks and separate verification; record exact outcomes here and commit.

Acceptance: observable outcomes in the spec, refined by the specifier review.
Affected files: `trongate-init.sh`, `project-files/deploy.sh`,
`project-files/.deploy.env`, `tests/installer_test.php`, `README.md`, this plan
and its spec. Existing application templates and engine files are out of scope.

Verification: Bash syntax checks; offline installer/deployment regression suite;
existing PHP guard suite; lint and guard any added PHP; `git diff --check`;
independent verifier of spec and diff. The scaffold has no root `tests/run.php`
or `tools/phpstan.sh`; installed-project HTTP/PHPStan checks are reported
separately and cannot establish scaffold script correctness.

## Evidence and risks

- Initial inspection: working tree clean; installer requires a marker absent
  from bundled deploy.sh; first install overwrites files; settings dry-run uses
  mktemp; README describes obsolete parent-directory deployment.
- No deployment, network test, push, dependency installation or engine change
  is authorized by this plan.
- Deployment commit `750a398`: Bash syntax check of installer, deploy script and
  env template, plus `git diff --check`: exit 0.
- Installer regression development: first run 24 tests, 22 passed, 2 failed
  (live-repository metadata snapshot and an unquoted invalid shell-config
  fixture). Corrected the fixtures; next run 24/24 passed. Isolated the source
  snapshot from live Git metadata and added missing/invalid source coverage;
  expanded run: 25 tests, 25 passed, 0 failed, exit 0.
- `php tests/php_guard_test.php`: 19 tests, 19 passed, 0 failed, exit 0.
- `php -l tests/installer_test.php`: no syntax errors, exit 0. Guard invocation
  and Bash syntax checks produced no findings. Bash is macOS 3.2.57.
- Policy: preserve contents and executable modes of retained custom scripts;
  `.deploy.env` alone is deliberately restricted to mode 600 on real installs.
  Installer state and additive gitignore/settings merges are documented writes.
- Integrated checks after README update: `php tests/installer_test.php` →
  25 tests, 25 passed, 0 failed, exit 0; `php tests/php_guard_test.php` →
  19 tests, 19 passed, 0 failed, exit 0. `php -l tests/installer_test.php` →
  no syntax errors, exit 0. JSON PostToolUse payload for that file piped into
  `php .claude/hooks/php_guard.php` → no findings, exit 0. Individual `bash -n`
  checks (installer, deploy script, env template) → exit 0.
  `git diff --check 750a398^` → no findings, exit 0.
- Application gates attempted using the bundled paths (root application paths
  do not exist): `php project-files/tests/run.php` → exit 2, "No base URL found";
  `bash project-files/tools/phpstan.sh` → exit 2, "PHPStan is not installed".
  These are unavailable checks, not passes; no application/server, downloaded
  PHPStan, real SSH/rsync deployment, or UI validation was exercised.
- Independent review found: oversized integers could round during JSON merge;
  decimals such as `1.0` lost their float type; inherited environment variables
  could supply missing deployment settings, and empty legacy keys were accepted.
  Fixed with early rejection of unrepresentable integers, preservation of zero
  fractions, clearing inherited deploy settings and checking legacy key presence.
  Added regression cases; spec and README document the numeric safety limit.
- Final implementer run after review fixes and default-port regression addition:
  `php tests/installer_test.php` → 26 tests, 26 passed, 0 failed, exit 0.
  PHP lint, changed-file guard, individual Bash syntax checks and diff whitespace
  check → exit 0, no findings. Name-only diff lists only the seven planned files;
  `git diff --name-only 750a398^ -- engine` → empty, exit 0.
- Independent verifier reran installer (26/26) and convention guard (19/19)
  suites, Bash syntax, changed PHP lint/guard and diff whitespace checks: all
  exit 0. Separate reproductions confirmed the review fixes; no remaining
  findings. Its root application gate attempts were unavailable:
  `php tests/run.php` → exit 1 (missing file), `bash tools/phpstan.sh` → exit 127
  (missing script). Neither is a pass; bundled-path exit-2 outcomes above also
  remain applicable.
- Final scope: installer, two deployment templates, scaffold-only regression
  tests, README, spec and plan. No engine/application template changes or new
  dependencies. Real docs fetches and remote deployment remain untested.
