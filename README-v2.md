# Trongate Scaffold Installer v2

This is a **drop-in update for your existing Trongate scaffold repository**, not a complete Trongate project or a full replacement for the other scaffold files you already maintain. Keep all existing `AGENTS.md`, `CLAUDE.md`, reference, agent, test, UI, and PHPStan source files from your original scaffold.

## Files included

- `trongate-init-v2.sh` — revised initializer, compatible with the original scaffold layout.
- `project-files/deploy.sh` — v2 project-local rsync deployment helper (dry-run by default).
- `project-files/.deploy.env` — safe example configuration. Replace values in the **generated project's** `.deploy.env`, not in this scaffold template.

## Updating the scaffold source

From the unpacked v2 folder:

```bash
cp trongate-init-v2.sh /path/to/your-scaffold/trongate-init.sh
cp project-files/deploy.sh /path/to/your-scaffold/project-files/deploy.sh
cp project-files/.deploy.env /path/to/your-scaffold/project-files/.deploy.env
chmod +x /path/to/your-scaffold/trongate-init.sh /path/to/your-scaffold/project-files/deploy.sh
```

Then, from the **Trongate project root** (where `engine/` and `modules/` exist):

```bash
bash /path/to/your-scaffold/trongate-init.sh --dry-run
bash /path/to/your-scaffold/trongate-init.sh
```

Add `--skip-docs` to skip the documentation Git checkout, for offline use or testing.

## Home page behavior

| Scenario | `Welcome.php` | `default_homepage.php` | `public.php` |
| --- | --- | --- | --- |
| Brand-new project, first initialization | **Overwritten** (backup kept) | **Overwritten** (backup kept) | **Overwritten** (backup kept) |
| Existing v1-initialized project | Preserved | Preserved | Preserved |
| Later v2 rerun | Preserved | Preserved | Preserved |
| Later v2 `--force` | Preserved | Preserved | Preserved |
| `--reset-homepage` | **Overwritten** (backup kept) | **Overwritten** (backup kept) | **Overwritten** (backup kept) |

A fresh project's initial run is determined from `.trongate-init-state` and the legacy `_reference/trongate-memory.md` marker. A v2 state marker is written *before* installation and marked `complete` at the end, so interrupted installations can resume first-run behavior and completed projects remain protected. If a previous first-run attempt was interrupted, review `--dry-run` before resuming.

`--force` refreshes scaffold-managed files and can replace `deploy.sh`, **but it never replaces the homepage files** without the explicit `--reset-homepage` flag. `AGENTS.md`, `CLAUDE.md`, `welcome_test.php`, and `.deploy.env` are always preserved if already present.

## Deployment changes

`deploy.sh` and `.deploy.env` now belong inside **each project root**. The initializer does not touch v1's old `../deploy.sh` or `../.deploy.env` or migrate any credentials. Review the old deployment configuration separately when moving to v2.

In the generated project's `.deploy.env`:

```sh
DEPLOY_HOST=example.com
DEPLOY_USER=deploy
DEPLOY_REMOTE_PATH=/var/www/my-trongate-app
DEPLOY_PORT=22
```

Deployment commands:

```bash
./deploy.sh          # dry-run preview only
./deploy.sh --apply  # actually upload files with rsync over SSH
```

The helper **does not use `--delete`**. It excludes deployment settings and secrets, `.git`, `.claude`, `_reference`, tests, tooling, backup and initialization state, and `config/` or `config_*/` (which must be provisioned separately on the server). A real deployment was not performed as part of this review.

**Important:** `.deploy.env` is stored inside your project as requested; its permissions are set to `600` and it is added to `.gitignore`. Ensure the web server cannot serve that file directly. Prefer a web document root restricted to `public/` or explicit deny rules when hosting arrangements differ. Do not put live secrets into `project-files/.deploy.env` in the scaffold repository.

## Other improvements

- Preflight verifies source files, expected Trongate directory layout, PHP CLI and Claude settings JSON **before** writing to the project.
- `--dry-run` previews file changes without writing project files or fetching docs.
- Initializations create unique timestamped backup directories under `.trongate-init-backup/`; directories holding backups use owner-only permissions.
- Existing Claude settings are merged additively for required `permissions.deny` rules and hooks; customized user permissions and hooks are preserved. The merge is idempotent.
- Existing `.gitignore` lines are preserved; the initializer no longer adds a blanket `config/` ignore, but it does **not** remove an existing one.
- Existing `.deploy.env` contents are never overwritten; permissions are tightened to `600`.
- Parent-directory symlink destinations are rejected before copying files.
- Documentation updates use `git pull --ff-only`, and a network failure is nonfatal.

## Local verification

The v2 initializer passed `bash -n` and temporary mock-project tests for first installation, subsequent installation, `--force`, `--reset-homepage`, v1 migration, file backups, settings merge, invalid-source preflight, `.deploy.env` permissions, symlink refusal, and deploy helper command construction using a fake rsync executable.

**Not verified:** your real Trongate application, actual deployment destination, and the full set of original scaffold files (only the v1 initializer and `AGENTS.md` were supplied for review).
