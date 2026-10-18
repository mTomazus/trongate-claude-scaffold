# Plan: shared scaffold verification

Date: 2026-10-18. Tier 1. Status: Complete.
Branch: `feature/scaffold-verification`, based on the completed installer branch.

1. Add a scaffold-only `tools/check-scaffold.sh` command for active PHP/Bash
   syntax checks, staged/unstaged whitespace checks and both regression suites.
   Accept an optional base revision so CI also checks committed changes.
2. Add Linux/macOS GitHub Actions jobs using the same command, pinned actions,
   PHP 8.3, read-only repository permissions and no persisted checkout credentials.
3. Reference the command from shared AGENTS instructions, Claude's commit and
   verifier instructions, and README. Keep installed-project application gates
   distinct; do not install this scaffold-only runner into projects.
4. Execute the command locally, validate workflow YAML/structure, and test failure
   propagation and whitespace detection in disposable repositories. Self-review
   the diff and record exact results before committing in bounded steps.

Acceptance: one offline command passes the current suites; nonzero checks stop
with a nonzero exit; calling from another directory works; CI checks PR/push
commit ranges on both OS runners; both agents are directed to the same command.
No engine changes, application dependencies, deployment, push, account changes
or GitHub workflow dispatch. Hosted CI execution requires a later approved push.

## Evidence

- Initial working tree clean. Existing root regression suites are available;
  no root application HTTP runner or PHPStan installation exists.
- Initial shared command run: exit 0; installer 26/26 and guard 19/19 passed,
  all active PHP/Bash syntax and staged/unstaged whitespace checks passed.
- Workflow YAML parsed with Ruby's standard YAML library; assertions for events,
  OS matrix, read-only permissions, pinned action hashes and shared command:
  exit 0. Action pins verified from upstream release commit pages:
  [checkout v6.0.2](https://github.com/actions/checkout/commit/de0fac2e4500dabe0009e67214ff5f5447ce83dd),
  [setup-php 2.40.0](https://github.com/shivammathur/setup-php/commit/eb7c497e18156a6bbabfef1d3a82760b9eda3962).
- Twelve disposable-repository checks passed: external directory/spaced path 0;
  new-branch zero SHA 0; invalid revision 128; excess arguments 2; installer
  failure 37; guard failure 38; invalid new PHP 255; invalid new Bash 2;
  unstaged/staged/committed whitespace 2 each; missing PHP 2.
- Self-review found the initial zero-SHA fallback audited the entire repository
  and failed on existing template whitespace (real run exit 2). Changed this
  case to check the tip commit's patch. Existing-branch pushes and PRs still
  check their full ranges. This avoids unrelated historical cleanup and is
  documented explicitly; new-branch multi-commit ranges are checked on the PR.
- Corrected real-repository zero-SHA run, invoked by absolute path from `/tmp`:
  exit 0; installer 26/26 and guard 19/19 passed, syntax/whitespace passed.
- Three follow-up new-branch scenarios passed: initial commit whitespace rejected
  (exit 2); clean tip with historical whitespace accepted (exit 0); tip whitespace
  rejected (exit 2). These supplement the twelve earlier runner checks.
- Final `bash tools/check-scaffold.sh 43353e4` with CI/README staged: exit 0;
  installer 26/26, guard 19/19, active syntax and staged/unstaged/committed-range
  whitespace checks passed. Final Ruby YAML/structure assertions also checked
  full history checkout and disabled credential persistence: exit 0.
- Explicit self-review: only runner, workflow, shared instructions, Claude commit/
  verifier guidance, README and this plan changed. No independent review claimed
  for this Tier 1 change. Hosted workflow and Linux execution remain unverified;
  they require a later approved push. No engine changes, dependencies in the
  application, deployment or push occurred.
