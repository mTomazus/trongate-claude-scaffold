# AGENTS.md — Project Operating Manual

## Framework

This is a **Trongate PHP v2** application. Trongate has its own conventions that differ from Laravel, CodeIgniter, and generic PHP. Always follow Trongate patterns.

## Core Trongate Rules

- Modules live in `modules/[module_name]/`
- Module names use lowercase snake_case — no hyphens, no `_module` suffix, no PascalCase
- Controller sits directly in the module folder — no `controllers/` subfolder
- Controller file: UppercaseFirst snake_case (e.g. `Teams.php`)
- Class name matches filename without extension (e.g. `class Teams extends Trongate`)
- Model file sits at the same level as the controller: `Teams_model.php`, extends `Model`
- All DB work goes through `$this->db` — no raw PDO unless `$this->db` cannot handle it
- URLs follow: `[base_url]/[module]/[method]/[param]`
- Views rendered with `$this->view('file', $data)` or `$this->template('admin', $data)`
- No Eloquent, no Doctrine, no PSR-4 autoloading, no Laravel-style facades
- No edits to `engine/` unless explicitly asked — that is the Trongate core, not a safe extension point

## Third-Party Libraries

**No Composer for application code. Ever.**

If a package's functionality is needed, build it as a Trongate module instead. Never suggest `composer require`, and never introduce a `vendor/` folder.

The single carve-out is developer tooling that never runs in production: a pinned `.phar` in `tools/`, excluded from deploys, never autoloaded by the application. `tools/` is not `vendor/` — nothing under `modules/` may reference anything in it. If a tool has no `.phar` distribution, it does not go in this project.

## File Conventions

- No trailing `?>` in PHP files
- snake_case for methods and variables
- Views are plain PHP files — no Blade, no Twig
- Config lives in `config/config.php` — never hardcode base URLs
- One class per file
- No empty constructors that only call `parent::__construct()`
- No `namespace` declarations unless the project already uses them

## Reference Folder

Use these in order — only go deeper if the current level doesn't cover it:

1. `_reference/trongate-memory.md` — read this first. Dense AI-optimized reference covering core philosophy, architecture, request flow, constants, base classes, DB methods, helpers, security, and common gotchas. Covers 95% of cases.
2. `_reference/trongate-docs-repo/` — full official Trongate docs cloned from GitHub. Only reference when trongate-memory.md doesn't cover something. Key folders: `php_framework/`, `reference/`, `trongate_css/`, `trongate_mx/`
3. `_reference/specs/` — plain-language feature requirements (read before planning)
4. `_reference/plans/` — step-by-step implementation plans (read before coding)

---

## Workflow

1. Classify the task as Tier 0, 1, 2, or 3 using the Gauntlet Loop rules below. The tier decides which of the following steps apply.
2. **Tier 2 and 3:** write a spec first (`_reference/specs/FEATURE_NAME.md`) — use `/new-spec [feature name]` to generate from the template.
3. **Tier 2 and 3:** generate a plan from the spec (`_reference/plans/FEATURE_NAME_plan.md`) — use `/new-plan [spec filename]` to generate from the template.
4. **Tier 2 and 3:** define acceptance criteria, required checks, forbidden changes, and human approval gates before coding.
5. Code one bounded task at a time, following the plan where there is one.
6. Write or update `tests/[module]_test.php` as you go. A route with no test is not finished.
7. **Any task that changes PHP:** run `php tests/run.php` and `bash tools/phpstan.sh` before marking it complete, and paste both result lines as evidence.
8. **Tier 2 and 3:** run the Gauntlet Loop and maintain the Gauntlet Log in the plan.
9. **Tier 2 and 3:** mark a task complete only after its evidence passes independent verification.
10. Finish with a full-suite run and a concise report of changes, proof, deviations, and remaining risks.

`/new-spec` and `/new-plan` are Claude Code commands. Other agents: open `.claude/commands/new-spec.md` or `.claude/commands/new-plan.md` and follow its steps directly.

---

## Verification Tools

These exist so "verified" means something. Never describe work as tested without having run them.

### Convention guard (automatic)

`.claude/hooks/php_guard.php` runs after every Write and Edit. It fails on syntax errors, trailing `?>`, `controllers/` subfolders, non-snake_case module folders, multiple classes in one file, class name not matching filename, empty `parent::__construct()` constructors, stray `namespace` declarations, and any sign of Composer.

Under Claude Code you never invoke this — it runs as a hook. Other agents get no hook, so after writing or editing a file, run it yourself:

```bash
echo '{"tool_input":{"file_path":"'"$PWD/modules/teams/Teams.php"'"}}' | php .claude/hooks/php_guard.php
```

Exit `0` is clean; exit `2` lists the violations. When it reports a violation, fix the file before doing anything else — the rules it enforces are the ones written above, so a violation is always a real mistake.

### HTTP test suite

```bash
php tests/run.php              # the whole suite
php tests/run.php welcome      # tests/welcome_test.php only
```

Tests live in `tests/*_test.php` and drive the running application over real HTTP. No dependencies, no Composer. Cookies persist for a whole run, so login flows work.

Helpers: `tg_get`, `tg_post`, `assert_status`, `assert_body_contains`, `assert_body_lacks`, `assert_redirects_to`, `assert_same`, `assert_true`. Read `tests/welcome_test.php` for the shape.

Exit codes:

- `0` — everything passed
- `1` — at least one test failed
- `2` — the suite could not run (no server, no base URL, no curl)

**Exit code 2 is not a pass.** If the suite could not run, say so plainly and fix the reason. Reporting an unrun suite as green is the exact failure the Evidence Standard exists to prevent.

Every module with routes gets a test file. A feature is not done until its spec's functional requirements each have a test, and the whole suite is green — not just the new file.

**The suite cannot see appearance.** It asserts status codes and body text, nothing else. A page can return 200, contain the right words, and still render completely unstyled. Any change to a view or a stylesheet needs a human, or a rendered screenshot, before it counts as verified.

### Static analysis

```bash
bash tools/phpstan.sh                 # everything
bash tools/phpstan.sh modules/teams   # one module
```

Always go through the wrapper, never the `.phar` directly — it sets the memory limit, without which PHPStan dies partway through and reports the crash as a single error, which reads like a nearly-clean result.

Exit codes match the test suite: `0` clean, `1` real findings, `2` not installed (`bash tools/install-phpstan.sh` once, per project). **Exit 2 is not a pass.**

`phpstan-baseline.neon` records the errors that already exist in Trongate's core modules so they are never reported again. It is committed. Anything the analyser reports is therefore in code we wrote.

Fix what it reports. Do not silence it: no `@phpstan-ignore` comments, no new baseline entries, no casts or widened types added purely to make an error go away. If a finding is genuinely wrong, say so and explain why rather than suppressing it.

---

## Gauntlet Loop

**For complex or risky work, separate implementation from acceptance. Evidence passes the work — not the implementer's confidence.**

### When to Use It

Classify the task before starting:

- **Tier 0 — Trivial:** One-file or mechanical change with an obvious check. Implement, verify, report. Do not create ceremony.
- **Tier 1 — Standard:** Small, contained feature or bug fix. Plan briefly, implement, run targeted checks, self-review.
- **Tier 2 — Gauntlet:** Multi-file behavior, unclear interactions, data changes, authentication, billing, scheduling, or meaningful regression risk. Use an implementer plus an independent verifier.
- **Tier 3 — High risk:** Production data, permissions, security boundaries, destructive operations, or broad architectural changes. Use the Gauntlet Loop plus explicit human approval gates and specialist reviews where relevant.

Do not escalate a simple task merely because multiple agents are available.

For Tier 2 and Tier 3 work, read `_reference/gauntlet.md` before starting. It defines the orchestrator, implementer and verifier roles and the contract → implement → verify loop.

### Evidence Standard

Claims such as "looks good", "should work", or "tests pass" are not sufficient by themselves. Record:

- Exact commands or manual checks performed
- Relevant outputs and failures
- Files changed
- Acceptance criteria satisfied
- Deviations from the plan and why
- Remaining risks or unverified behavior

For Tier 2 and Tier 3 work, keep this evidence in the plan's Gauntlet Log section (see `_reference/plans/PLAN_TEMPLATE.md`).

### Stop and Escalation Rules

Stop and ask for human direction before:

- Destructive or irreversible database operations
- Production deployment or production data changes
- Editing `engine/`
- Changing authentication, authorization, billing, or secrets beyond the approved scope
- Adding a dependency, external service, or recurring cost
- Expanding the agreed feature scope

Also stop when:

- Requirements are contradictory or insufficient to define acceptance
- Required verification cannot be run
- The same failure repeats without a materially different hypothesis or new evidence
- The next action would trade correctness or safety for merely continuing the loop

Never use "keep going until perfect" as a completion rule. The loop ends when all explicit acceptance criteria pass, required evidence exists, and no unresolved blocking finding remains.

---

## General Guidelines

Behavioral guidelines to reduce common LLM coding mistakes. These bias toward caution over speed. For trivial tasks, use judgment.

### Think Before Coding

**Don't assume. Don't hide confusion. Surface tradeoffs.**

Before implementing:
- State your assumptions explicitly. If uncertain, ask.
- If multiple interpretations exist, present them — don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- If something is unclear, stop. Name what's confusing. Ask.

### Simplicity First

**Minimum code that solves the problem. Nothing speculative.**

- No features beyond what was asked.
- No abstractions for single-use code.
- No "flexibility" or "configurability" that wasn't requested.
- No error handling for impossible scenarios.
- No new dependencies for problems Trongate already handles natively.
- If you write 200 lines and it could be 50, rewrite it.

Ask yourself: "Would a senior engineer say this is overcomplicated?" If yes, simplify.

### Surgical Changes

**Touch only what you must. Clean up only your own mess.**

When editing existing code:
- Don't "improve" adjacent code, comments, or formatting.
- Don't refactor things that aren't broken.
- Match existing style, even if you'd do it differently.
- If you notice unrelated dead code, mention it — don't delete it.

When your changes create orphans:
- Remove imports/variables/functions that YOUR changes made unused.
- Don't remove pre-existing dead code unless asked.

The test: Every changed line should trace directly to the user's request.

### Goal-Driven Execution

**Define success criteria. Loop until verified.**

Transform tasks into verifiable goals:
- "Add validation" → "Write tests for invalid inputs, then make them pass"
- "Fix the bug" → "Write a test that reproduces it, then make it pass"
- "Refactor X" → "Ensure tests pass before and after"

For multi-step tasks, state a brief plan:
```
1. [Step] → verify: [check]
2. [Step] → verify: [check]
3. [Step] → verify: [check]
```

Strong success criteria let you loop independently. Weak criteria ("make it work") require constant clarification.
