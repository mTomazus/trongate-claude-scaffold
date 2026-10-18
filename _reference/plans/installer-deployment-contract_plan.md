# Plan: installer and deployment contract

Date: 2026-10-18. Tier 2. Status: In Progress.
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
