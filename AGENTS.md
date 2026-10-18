# AGENTS.md — Trongate v2 Development Guide

## Purpose

This project uses **Trongate PHP v2**, not Laravel or CodeIgniter. Follow the existing project's conventions and Trongate's native patterns. Be autonomous for routine work, conservative with risky changes, and verify claims with evidence.

**Priority:** explicit user requirements > this file > relevant existing project patterns > reference documentation. If a requirement conflicts with a safety gate, obtain approval rather than bypassing the gate.

## 1. Understand Before Editing

- Inspect the relevant controller, model, views, routes, and similar working modules before changing code.
- Check existing database tables and relationships before proposing schema changes.
- Reuse the project's authentication, authorization, validation, escaping, and database patterns.
- Check `_reference/trongate-memory.md` for framework-specific behavior. Consult only the relevant part of `_reference/trongate-docs-repo/` when the memory file is insufficient.
- Check existing requirements in `_reference/specs/` and plans in `_reference/plans/` when applicable.
- Do not invent Trongate methods or assume Laravel equivalents exist. Verify uncertain APIs against project code or the references.
- Make reasonable, reversible decisions independently. Ask only when an ambiguity materially changes behavior, data, permissions, cost, or acceptance criteria.

## 2. Trongate Conventions

- Modules: `modules/[module_name]/`, using lowercase `snake_case`; no hyphens, PascalCase, or `_module` suffix.
- Controllers live **directly inside module folders**, not in `controllers/` subfolders.
- Controller filename and class must match, e.g. `modules/teams/Teams.php` contains `class Teams extends Trongate`.
- Models live beside controllers, e.g. `Teams_model.php` containing a class extending `Model`.
- Use `$this->db` for database operations; use raw PDO only when necessary and justified.
- Follow existing route conventions: `[base_url]/[module]/[method]/[param]` unless the application defines custom routing.
- Render views using the project's Trongate view/template methods, such as `$this->view(...)` and `$this->template(...)`.
- Views are plain PHP. Do not introduce Blade, Twig, Eloquent, Doctrine, Laravel facades, or a new autoloading architecture.
- Use `snake_case` for methods and variables; one class per file; omit trailing `?>` in PHP-only files.
- Avoid empty constructors that only call `parent::__construct()`; avoid namespaces unless already used in the project.
- Use the project's config (`config/config.php`) and URL helpers, not hardcoded base URLs.
- **Never edit `engine/` without explicit approval.**

## 3. Dependencies and Scope

- Prefer native Trongate functionality and small, dependency-free PHP solutions.
- **Do not add Composer, a `vendor/` directory, production packages, external services, or recurring costs without approval.**
- Do not reimplement security-sensitive or complex standards merely to avoid a dependency; explain the trade-off and request approval when needed.
- Keep changes surgical. Do not refactor, rename, format, or delete unrelated code.
- Preserve existing routes, public APIs, database semantics, and user data unless the task authorizes changing them.
- Favor the simplest implementation that satisfies the request. No speculative abstractions or unrequested features.

## 4. Choose the Smallest Appropriate Workflow

Classify by **risk and complexity**, not by the number of files or agents available.

| Tier | Typical work | Workflow |
| --- | --- | --- |
| **0 — Trivial** | Text, styling, labels, small mechanical edit | Implement → quick check → report |
| **1 — Standard** | Form, filtering, modal, contained bug fix or CRUD | Brief plan → implement → targeted tests → self-review |
| **2 — Complex** | Interacting workflows, nontrivial data changes, competition scheduling, substantial regression risk | Spec → implementation plan → bounded steps → tests → verification |
| **3 — Critical** | Production data, destructive migrations, security/permissions, billing, major architecture | Tier 2 process + explicit approval gates and deeper review |

For Tier 2–3:

1. Read `_reference/gauntlet.md` if it exists. Use `_reference/specs/FEATURE_NAME.md` and `_reference/plans/FEATURE_NAME_plan.md` (reuse existing files instead of duplicating them).
2. Define acceptance criteria, affected modules, constraints, and verification steps before implementation.
3. Implement in bounded increments. Use an independent verifier when one is genuinely available; otherwise perform an explicit self-review and **do not call it independent**.
4. Record essential evidence and unresolved risks in the plan. Avoid ceremonial logs or repeated review loops without new information.

Claude Code may use `/new-spec` and `/new-plan` when those commands exist. Codex or other agents can follow `.claude/commands/new-spec.md` and `.claude/commands/new-plan.md` if present. Do not depend on Claude-specific commands or hooks to complete work.

## 5. Verification That Matches the Change

**Never claim a test or check passed unless you ran it and observed the result.**

- **Every changed PHP file:** run `php -l path/to/file.php` (or an equivalent syntax check).
- **Changed module behavior:** add/update relevant `tests/[module]_test.php` tests where practical, then run `php tests/run.php module` for that module if the runner exists.
- **Changed PHP logic:** run targeted static analysis via `bash tools/phpstan.sh modules/module_name` when available.
- **Completed Tier 2–3 feature, release, or deployment candidate:** run `php tests/run.php` and `bash tools/phpstan.sh` when available.
- **UI/CSS changes:** inspect the rendered page or screenshot when possible. HTTP success alone does not verify visual quality.
- **Security-sensitive or data-changing work:** test expected behavior, invalid input, unauthorized access, and regression paths appropriate to the change.

Repository tool notes:

- `tests/run.php` performs HTTP tests; the application may need a running server. An unavailable suite is **not** a pass.
- `tools/phpstan.sh` is the preferred PHPStan entry point; do not invoke the `.phar` directly when the wrapper exists.
- Claude Code may run `.claude/hooks/php_guard.php` automatically. Other agents should invoke it for changed PHP files if present; it supplements, not replaces, tests.
- Keep PHPStan findings visible. Fix legitimate findings; do not silently add ignores or baseline entries to hide new defects.
- Report any missing tools, blocked runs, failures, or unverified behavior. For routine tasks, finish with that limitation disclosed; for critical changes, do not approve deployment on incomplete verification.

## 6. Safety and Approval Gates

**Get explicit approval before:**

- Destructive or irreversible database operations, or overwriting existing user data.
- Production deployment, live migrations, or modifications to production data.
- Editing Trongate `engine/` or making broad architectural changes.
- Changes to authentication, authorization, billing, or secrets **outside the agreed scope**.
- Introducing dependencies, third-party services, access credentials, or recurring costs.
- Expanding feature scope beyond what was requested.

For approved security/billing/permission work, implement within the authorized scope without repeatedly asking for the same approval. Never expose secrets in code, logs, tests, or responses. If requirements conflict or a critical safety assumption is unresolved, state the blocker and ask a focused question.

## 7. Completion Standard

Before reporting completion, confirm:

- The requested behavior and explicit acceptance criteria are addressed.
- Existing Trongate conventions and nearby patterns are preserved.
- Only necessary files were changed; no unrelated cleanup was slipped in.
- Appropriate available checks were run, with failures and limitations disclosed.
- Any migration, deployment, or risky action still requiring approval is clearly separated from completed local work.

Finish with a **short summary** of what changed, files touched, checks run with outcomes, and remaining risks. Do not describe unrun tests as green or unfinished work as complete.
