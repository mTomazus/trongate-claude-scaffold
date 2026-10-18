# Installer and deployment contract

Date: 2026-10-18. Scope: scaffold installation and bundled deployment helpers.

- Install `deploy.sh` and `.deploy.env` inside each project. Deploy the script's
  own project root, regardless of working directory, using `DEPLOY_USER`,
  `DEPLOY_HOST`, `DEPLOY_PORT` (default 22), and `DEPLOY_REMOTE_PATH`.
- Default installation creates missing files and preserves existing custom
  files, including on the first run. `--force` refreshes managed tooling with
  backups; only `--reset-homepage` replaces existing homepage/template files.
- Never overwrite instructions, example tests, or deployment credentials.
  Merge required Claude deny rules/hooks without changing custom settings or
  JSON types. Validate settings before making any project changes.
- Installer `--dry-run` performs no writes (including temporary files), chmod,
  or network operations. Reject unsafe destinations before making changes.
- Deployment defaults to rsync preview. Live sync requires `--live` and explicit
  confirmation. Reject legacy/missing/unsafe configuration. Exclude credentials,
  backups, installer state and development files from sync.
- Offline regression tests must cover new, existing, legacy and interrupted
  projects, custom settings, dry-run, symlinks, backups and deployment arguments.
- README must describe these policies and distinguish Claude-only automation
  from the shared AGENTS.md instructions used by Codex.

No engine changes, application dependencies, live deployment or push. Tests use
temporary fixture projects and stub network/deployment commands.
