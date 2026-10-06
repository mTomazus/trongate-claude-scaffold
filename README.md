# Trongate Claude Scaffold

A scaffold for working with [Trongate PHP v2](https://trongate.io) and Claude Code in VS Code. Run one command on any new Trongate project to get a fully configured Claude environment with reference docs, spec and plan templates, slash commands, and verification gates. Styling stays on Trongate's own `css/trongate.css` — no build step, no dependencies.

---

## What It Includes

```
trongate-claude-scaffold/
  AGENTS.md                          # The operating manual for Trongate projects (Codex and other agents read this)
  CLAUDE.md                          # One line, @AGENTS.md, so Claude Code reads the same manual
  trongate-init.sh                   # Setup script - run once per new project
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
      php_guard.php                  # Enforces AGENTS.md rules on every write
    commands/
      new-spec.md                    # /new-spec - create a feature spec
      new-plan.md                    # /new-plan - create a plan from a spec
      commit.md                      # /commit - stage, commit, and push
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
    deploy.sh                        # Deploy helper, installed one level above the project
    .deploy.env                      # Server settings deploy.sh reads, installed beside it
```

The `project-files/` folder mirrors the exact paths these files will land at in your Trongate project. The init script simply copies them across.

When you run `trongate-init`, it also clones the [official Trongate docs repo](https://github.com/trongate/trongate-docs) into `_reference/trongate-docs-repo/` for deep reference when needed.

### Claude Code and Codex

The manual lives in `AGENTS.md`, which Codex and most other coding agents read. Claude Code only reads `CLAUDE.md`, so that file holds a single line, `@AGENTS.md`, which imports the manual. Edit `AGENTS.md`; leave `CLAUDE.md` alone.

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

The script will:

**Claude scaffold:**
- Create `AGENTS.md` and `CLAUDE.md` at the project root
- Create `_reference/` with the memory file and templates
- Create `.claude/commands/` with all slash commands
- Create `.claude/settings.json` and `.claude/hooks/php_guard.php` (convention gate)
- Create `.claude/agents/` with the specifier and verifier agents
- Create `phpstan.neon` and `tools/` (static analysis, enabled with one command)
- Create `tests/` with the HTTP test runner and a worked example
- Merge preferred Trongate defaults into `.gitignore` (existing entries are kept)
- Clone the latest Trongate docs into `_reference/trongate-docs-repo/`

**Project files:**
- Copy `public/css/app.css` (plain CSS components extending `trongate.css`)
- Replace `modules/templates/views/public.php` to load both stylesheets
- Replace `modules/welcome/Welcome.php` to add the `/welcome/components` route
- Replace `modules/welcome/views/default_homepage.php` with an under-construction page
- Add `modules/welcome/views/components_showcase.php`
- Create `../deploy.sh` and `../.deploy.env` if they do not already exist (`DEPLOY_SOURCE` is set to this project's folder name)

The script refuses to run outside a Trongate project root (it checks for `engine/` and `modules/`). It is safe to re-run: on a project that has already been initialised, any file you have changed is kept and reported. `trongate-init --force` replaces those with the scaffold's version. `AGENTS.md`, `CLAUDE.md`, `tests/welcome_test.php` and `.claude/settings.json` are never replaced without `--force` (settings.json not even then).

---

## Deploying

`../deploy.sh` syncs the project folder named by `DEPLOY_SOURCE` to your server over rsync + SSH. All settings live in `../.deploy.env`. Fill in your server details, set up key auth once (`ssh-copy-id`), then:

```bash
../deploy.sh          # dry run - shows what would change
../deploy.sh --live   # real deploy, asks for confirmation
```

It never sends `config/`, `tests/`, `tools/`, `_reference/`, `.claude/`, PHPStan config or the deploy files themselves, and it refuses to sync a folder that is not a Trongate project. It uses `--delete`, so always look at the dry run first.

**Check before your first live deploy:** Trongate expects `public/` to be the only web-exposed folder. If your host points the domain at the folder you deploy into (e.g. `public_html`), make sure `config/`, `engine/` and `modules/` cannot be fetched over HTTP.

Visit your project in the browser to see the under-construction page, and `/welcome/components` for the component reference.

---

## Slash Commands

Open Claude Code in VS Code and use these from any project:

| Command | What it does |
| --- | --- |
| `/new-spec [feature name]` | Creates a spec file in `_reference/specs/` |
| `/new-plan [spec filename]` | Creates an implementation plan from a spec |
| `/commit` | Stages all changes, writes a commit message, and pushes |
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

The scaffold ships two gates so that "it works" is something you can check rather than something Claude asserts.

### Convention guard

`.claude/hooks/php_guard.php` runs automatically after every file Claude writes or edits. It rejects syntax errors, trailing `?>`, `controllers/` subfolders, non-snake_case module folders, multiple classes in one file, a class name that does not match its filename, empty `parent::__construct()` constructors, stray `namespace` declarations, and anything Composer-shaped.

These are the same rules already written in `AGENTS.md`. The difference is that a hook is enforced and prose is not.

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

That downloads a pinned `phpstan.phar` into `tools/` and generates `phpstan-baseline.neon`, which records the errors already present in Trongate's core modules so they never surface again. From then on the analyser only reports problems in code you wrote.

```bash
bash tools/phpstan.sh                 # everything
bash tools/phpstan.sh modules/teams   # one module
```

Always use the wrapper. At PHP's default 128M limit PHPStan dies partway through and reports the crash as a single error, which looks like a nearly-clean result.

The `.phar` is dev tooling, not an application dependency — gitignored, never autoloaded, never deployed. `tools/` is not `vendor/`, and the no-Composer rule still holds for everything the application actually runs.

Exit codes for both gates: `0` clean, `1` real findings, `2` could not run. **Exit 2 is never a pass.**

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
- Git
- [Claude Code](https://claude.ai/code) installed in VS Code
- A Trongate v2 project

---

## Acknowledgements

Thanks to [Dave Connelly](https://trongate.io) for building an AI-optimized framework and providing both the memory reference file and the [official docs repo](https://github.com/trongate/trongate-docs) that make this scaffold possible.
