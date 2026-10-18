# Trongate Claude Scaffold

A development scaffold for [Trongate PHP v2](https://trongate.io) projects using Claude Code or Codex. Run one command from a Trongate project to install shared agent instructions, reference docs, spec and plan templates, slash commands, and verification gates. Styling stays on Trongate's own `css/trongate.css` — no build step, no dependencies.

---

## What It Includes

```
trongate-claude-scaffold/
  AGENTS.md                          # The operating manual for Trongate projects (Codex and other agents read this)
  CLAUDE.md                          # One line, @AGENTS.md, so Claude Code reads the same manual
  trongate-init.sh                   # Non-destructive project setup; safe to rerun
  tools/check-scaffold.sh            # Shared offline checks; scaffold repository only
  tests/
    installer_test.php               # Installer/deployment regression suite
    php_guard_test.php               # Convention guard regression suite
  .github/workflows/scaffold.yml     # Linux/macOS CI using the shared checks
  _reference/
    trongate-memory.md               # AI-optimized Trongate v2 reference (read first)
    gauntlet.md                      # Gauntlet roles and loop, read for Tier 2/3 work
    specs/SPEC_TEMPLATE.md           # Template for scoping new features
    plans/PLAN_TEMPLATE.md           # Template for implementation plans
  .claude/
    settings.json                    # Permissions + the php_guard PostToolUse hook
    agents/
      specifier.md                   # Spec -> testable acceptance criteria
      verifier.md                    # Independent check; runs gates, reports findings
    hooks/
      php_guard.php                  # Checks PHP conventions after supported Claude edits
    commands/
      new-spec.md                    # /new-spec - create a feature spec
      new-plan.md                    # /new-plan - create a plan from a spec
      commit.md                      # /commit - review and commit; push only when requested
      security-audit.md              # /security-audit - full codebase security scan
      update-docs.md                 # /update-docs - pull latest Trongate docs
  project-files/                     # Mirrors the destination paths in your project
    phpstan.neon                     # Static analysis config, tuned for Trongate
    tools/
      install-phpstan.sh             # Downloads the pinned phar, builds the baseline
      phpstan.sh                     # Run wrapper (sets the memory limit)
      phpstan-bootstrap.php          # Declares Trongate's runtime constants
    public/css/app.css               # Plain CSS components extending trongate.css
    tests/
      run.php                        # Zero-dependency HTTP test runner
      welcome_test.php               # Worked example covering the welcome module
    modules/
      templates/views/public.php     # Template that loads css/trongate.css
      welcome/Welcome.php            # Controller: homepage + /welcome/components
      welcome/views/default_homepage.php   # Under-construction page
      welcome/views/components_showcase.php # Component reference
    deploy.sh                        # Project-local rsync + SSH deployment helper
    .deploy.env                      # Project-local server settings (gitignored on install)
```

The `project-files/` folder mirrors the exact paths these files will land at in your Trongate project. The installer creates missing files and preserves existing files by default.

Unless you use `--skip-docs` or `--dry-run`, `trongate-init` clones or updates the [official Trongate docs repo](https://github.com/trongate/trongate-docs) into `_reference/trongate-docs-repo/` for deep reference when needed.

### Claude Code and Codex

The shared manual lives in `AGENTS.md`. The bundled `CLAUDE.md` contains `@AGENTS.md` so Claude Code imports the same instructions. Existing instruction files are preserved, including with `--force`; the installer warns if an existing `CLAUDE.md` lacks that import. Review custom instructions for consistency.

The slash commands, agents and the `php_guard` hook under `.claude/` are Claude Code features. Other agents don't run the hook automatically; `AGENTS.md` tells them how to run it by hand.

---

## One-Time Mac Setup

### 1. Save the scaffold

Clone or download this repo to:

```
~/Documents/scripts/trongate-claude-scaffold/
```

### 2. Add the shell alias

```bash
echo 'alias trongate-init="bash ~/Documents/scripts/trongate-claude-scaffold/trongate-init.sh"' >> ~/.zshrc
source ~/.zshrc
```

---

## Per-Project Usage

Navigate to the root of any new Trongate project and run:

```bash
trongate-init
```

Preview the installation without writing files, creating temporary files, changing
permissions, or fetching documentation:

```bash
trongate-init --dry-run
```

The installer supports `--skip-docs` for offline installation, `--force` for
refreshing managed files, and `--reset-homepage` for explicitly replacing the
homepage controller, homepage view and public template. Flags can be combined.

The script will create missing files at these paths:

**Claude scaffold:**

- Create `AGENTS.md` and `CLAUDE.md` at the project root
- Create `_reference/` with the memory file and templates
- Create `.claude/commands/` with all slash commands
- Create `.claude/settings.json` and `.claude/hooks/php_guard.php` (convention gate); existing settings receive only missing template deny rules and hooks
- Create `.claude/agents/` with the specifier and verifier agents
- Create `phpstan.neon` and `tools/` (static analysis, enabled with one command)
- Create `tests/` with the HTTP test runner and a worked example
- Merge preferred Trongate defaults into `.gitignore` (existing entries are kept)
- Clone or fast-forward the Trongate docs in `_reference/trongate-docs-repo/` (unless skipped); failures are reported as warnings

**Project files:**

- Copy `public/css/app.css` (plain CSS components extending `trongate.css`)
- Add `modules/templates/views/public.php` to load both stylesheets
- Add `modules/welcome/Welcome.php` with the `/welcome/components` route
- Add `modules/welcome/views/default_homepage.php`, an under-construction page
- Add `modules/welcome/views/components_showcase.php`
- Create `./deploy.sh` and `./.deploy.env` in the project root if missing; parent-directory deployment files are untouched

The installer requires `engine/`, `modules/welcome/views/`, and
`modules/templates/views/`. It checks required sources, destination paths,
installation state, and Claude settings before writing. Symlinks at managed
file paths, their parent directories, or the backup directory are rejected.
Settings with integers outside PHP's exact integer range are rejected before
writing to avoid silently rounding custom values. It never edits `engine/`.

Existing files are preserved on the first run and on reruns, with these policies:

| Files | Update policy |
| --- | --- |
| Managed tooling, references, commands, hooks, CSS and deploy helper | Replace only with `--force`, with backups |
| Welcome controller, homepage view and public template | Replace only with `--reset-homepage`, even with `--force` |
| `AGENTS.md`, `CLAUDE.md`, `tests/welcome_test.php`, `.deploy.env` | Never replace existing contents |
| `.claude/settings.json` | Add missing template deny rules/hooks; preserve custom permissions, hooks, values and JSON types; back up changes |
| `.gitignore` | Append missing ignore entries; preserve existing contents and back up changes |

Existing `.deploy.env` permissions are restricted to `600` on real installations.
Retained custom scripts keep their executable modes. Newly installed scripts are
made executable. `.trongate-init-state` records installation progress; legacy and
interrupted installations follow the same preservation rules.

Backups live in `.trongate-init-backup/<timestamp>-<unique>/` under their original
relative paths. The folder is gitignored and excluded from deployment. Review
backups before removing them. Installation is not a transaction: if a copy or
filesystem operation fails after preflight, completed steps remain; rerun after
fixing the error.

---

## Deploying

`./deploy.sh` syncs the contents of its own project root over rsync + SSH,
regardless of your current directory. Edit the project-local `.deploy.env`:

```bash
DEPLOY_USER=your_user
DEPLOY_HOST=your_server.example
DEPLOY_PORT=22
DEPLOY_REMOTE_PATH=/home/your_user/public_html/
```

This is a trusted shell file sourced by the helper; do not put passwords or
untrusted commands in it. Destination settings must come from this file;
inherited `DEPLOY_*` environment values are cleared. Use SSH key authentication.
The helper accepts simple
SSH usernames, DNS/IPv4 hosts, ports 1–65535, and absolute remote paths containing
letters, numbers, underscores, dots, slashes and hyphens. Root-only and `.`/`..`
path segments are rejected. Paths containing spaces and IPv6 hosts are not
supported.

```bash
./deploy.sh          # rsync preview; contacts the server, transfers no files
./deploy.sh --live   # real deployment, requires affirmative confirmation
```

The default preview uses `rsync --dry-run`: it contacts SSH and may use/update SSH
client state. The installer's `--dry-run` is entirely offline and performs no
writes. Live deployment uses `--delete`; review the preview and destination first.

The helper excludes `config/`, development tooling/tests, references, AI settings
and instructions, Git metadata, installer backups/state, and both deployment
files. Excluded server files are preserved by rsync. Set up production `config/`
and the database separately before deployment.

For older installations, parent-directory helpers and credentials are left
untouched. Review them manually before migration: the new helper belongs inside
the project, uses no `DEPLOY_SOURCE`, and replaces `DEPLOY_PATH` with
`DEPLOY_REMOTE_PATH`. Existing project-local credentials are never rewritten;
legacy keys cause the new helper to stop. `trongate-init --force` can back up and
refresh an existing project-local helper, but credentials still need manual review.

**Check before your first live deploy:** Trongate expects `public/` to be the only web-exposed folder. If your host points the domain at the folder you deploy into (e.g. `public_html`), make sure `config/`, `engine/` and `modules/` cannot be fetched over HTTP.

When the demo templates have been installed, visit the project homepage and
`/welcome/components` to inspect them. Preserved custom controllers/templates may
not expose the demo route or load the bundled CSS; integrate those changes manually
or explicitly use `--reset-homepage`.

---

## Slash Commands

Open Claude Code in VS Code and use these from any project:

| Command | What it does |
| --- | --- |
| `/new-spec [feature name]` | Creates a spec file in `_reference/specs/` |
| `/new-plan [spec filename]` | Creates an implementation plan from a spec |
| `/commit` | Reviews and commits changes; pushes only with the `push` argument |
| `/security-audit` | Scans the codebase for security issues |
| `/update-docs` | Pulls the latest Trongate docs from GitHub |

### Typical workflow

```
/new-spec team registration form
/new-plan team-registration-form
# implement the plan one task at a time
php tests/run.php          # must exit 0 before the work counts as done
/security-audit
/commit
```

---

## Verification Gates

Claude Code and Codex use the same verification command in this repository:

```bash
bash tools/check-scaffold.sh
# Also check whitespace in committed changes since a base revision:
bash tools/check-scaffold.sh BASE_REVISION
```

The command checks active PHP/Bash syntax, staged and unstaged whitespace, and
runs `php tests/installer_test.php` and `php tests/php_guard_test.php`. It exits
nonzero on the first failed check. It works from any working directory when you
give the path to the script. Tests use disposable projects and stubbed rsync.

This runner and its tests are not copied into installed projects. They require
Git, PHP CLI 8.0+ and Bash; no server or downloaded tooling is needed. Installer
coverage includes first runs, reruns, force/reset, legacy and interrupted state, settings validation/merging,
read-only dry-run, symlink rejection, backups and deployment arguments.

`.github/workflows/scaffold.yml` runs this same command on pushes and pull
requests, using Linux and macOS runners with PHP 8.3. CI checks whitespace over
the pushed or proposed commit range as well as running the suites. For a new
branch with no previous tip, it checks the tip commit's whitespace; opening a
pull request checks the full proposed range. Actions are
pinned to commit hashes, checkout credentials are not retained, and repository
permissions are read-only. CI prepares the runtime before the offline checks;
it has no deployment step or deployment credentials.

The installed scaffold ships two gates so that "it works" is something you can check rather than something Claude asserts.

### Convention guard

The bundled Claude settings register `.claude/hooks/php_guard.php` after
`Write`, `Edit` and `MultiEdit` tool calls. It reports syntax errors, trailing
`?>`, `controllers/` subfolders, non-snake_case module folders, multiple classes
in one file, class/filename mismatches, empty `parent::__construct()` constructors,
and namespaces when no existing module uses them. It also flags `composer.json`,
paths under `vendor/`, and the Composer commands it checks for in PHP files.

The guard checks a subset of `AGENTS.md` conventions after a write; it does not
undo changes or cover files written through Bash. Files under `.claude/` are
skipped. Codex and other agents must invoke the guard explicitly for changed PHP
files, as directed by `AGENTS.md`. Guard exits are `0` clean and `2` violations.

If you change the guard, run its tests from the scaffold folder (they are not copied into projects):

```bash
php tests/php_guard_test.php
```

### HTTP test suite

```bash
php tests/run.php              # the whole suite
php tests/run.php welcome      # tests/welcome_test.php only
```

No Composer, no PHPUnit, no dependencies at all — just PHP and curl. Tests drive the real application over HTTP, which is the layer that actually matters in a Trongate app. Cookies persist across a run, so login flows work.

The base URL comes from `BASE_URL` in `config/config.php`, or override it:

```bash
TEST_BASE_URL=http://localhost:3000 php tests/run.php
```

Exit codes are `0` passed, `1` failed, and `2` could not run — the suite refuses to report a green result when it never reached your server.

Start from `tests/welcome_test.php`; it covers the shipped welcome module and shows the shape of every helper.

**What it cannot do:** the suite asserts status codes and body text. It cannot see appearance. A page can return 200, contain every expected word, and render completely unstyled. View and stylesheet changes need a human or a screenshot.

### Static analysis

Enable it once per project:

```bash
bash tools/install-phpstan.sh
```

This downloads the configured PHPStan version into `tools/phpstan.phar` if no
PHAR is present, then attempts to generate `phpstan-baseline.neon` for all
configured module paths. Existing custom modules are included; baseline entries
can suppress findings in those modules too. Rerunning the installer regenerates
the baseline, so review changes before accepting them.

The current installer tolerates baseline-generation failures and still prints
its completion message. That message does not establish a clean analysis; run
the wrapper below and inspect its exit code and findings.

```bash
bash tools/phpstan.sh                 # everything
bash tools/phpstan.sh modules/teams   # one module
```

Use the wrapper to set PHPStan's memory limit to 1G. A memory error is a failed
analysis, even if only one error is reported.

The `.phar` is dev tooling, not an application dependency — gitignored, never autoloaded, never deployed. `tools/` is not `vendor/`, and the no-Composer rule still holds for everything the application actually runs.

The wrapper exits `2` when the PHAR is missing; otherwise it forwards PHPStan's
exit status. Treat every nonzero status as a failed or unavailable check and
inspect the output. The convention guard's exit codes are described separately
above; its exit `2` means violations.

---

## Gauntlet Agents

Two agents in `.claude/agents/` support the Gauntlet Loop described in `AGENTS.md`:

| Agent | Role |
| --- | --- |
| `specifier` | Turns a spec into acceptance criteria precise enough to write a failing test from. Cannot touch application code. |
| `verifier` | Checks finished work against those criteria with fresh context. Runs the gates itself instead of trusting a report. Has no edit tools — it reports, you fix. |

There is deliberately no refactorer or architect agent. That work collapses into the verifier plus the automated gates, and five agents is a lot of overhead for a framework where most features are three files.

---

## How Claude Uses the Reference Files

Claude reads reference files in this order to keep token usage low:

1. `_reference/trongate-memory.md` - AI-optimized Trongate v2 reference, covers 95% of cases
2. `_reference/trongate-docs-repo/` - full official docs, only when the memory file doesn't cover something
3. `_reference/specs/` - feature requirements before planning
4. `_reference/plans/` - implementation plans before coding

---

## Requirements

- macOS with zsh (or any Unix with bash)
- Bash and standard Unix tools (tested with macOS Bash 3.2)
- PHP CLI 8.0+ (settings validation and scaffold tests)
- Git for reference documentation updates; use `--skip-docs` to work offline
- Claude Code or Codex for agent-assisted development
- A Trongate v2 project
- rsync and SSH only when using the deployment helper
- PHP curl extension and a running application for installed-project HTTP tests

---

## Acknowledgements

Thanks to [Dave Connelly](https://trongate.io) for building an AI-optimized framework and providing both the memory reference file and the [official docs repo](https://github.com/trongate/trongate-docs) that make this scaffold possible.
