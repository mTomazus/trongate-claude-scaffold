# Plan: [Feature Name]

**Date:** YYYY-MM-DD
**Spec:** `_reference/specs/[SPEC_FILE].md`
**Status:** In Progress | Complete | Blocked
**Tier:** 0 | 1 | 2 | 3 (see AGENTS.md §5)

## Summary
One paragraph describing what this plan implements and why.

## Acceptance Criteria
One observable outcome per criterion, each with the check that proves it. For Tier 2 and 3, generate these with the `specifier` agent.

- AC-1 — 
  Check: 

## Forbidden Changes and Approval Gates
What this plan must not touch, and what needs a human yes before it happens (see AGENTS.md Stop and Escalation Rules).

- 

## Branch
Before starting any tasks, create a new branch under the `feature/` subfolder.

- **Branch name:** `feature/[short-feature-slug]`
- Create it from an up-to-date `main` (or your base branch):
  ```bash
  git checkout main
  git pull
  git checkout -b feature/[short-feature-slug]
  ```
- All work for this plan happens on this branch. Do not commit directly to the base branch.

## Affected Files
List every file that will be created or modified.

- `modules/[module]/[Module].php` — 
- `modules/[module]/[Module]_model.php` — 
- `modules/[module]/views/[view].php` — 
- `config/config.php` — 

## Tasks

### Phase 1: [e.g. Database]
- [ ] Task 1
- [ ] Task 2

### Phase 2: [e.g. Controller]
- [ ] Task 1
- [ ] Task 2

### Phase 3: [e.g. Views]
- [ ] Task 1
- [ ] Task 2

### Phase 4: Verification
- [ ] Write `tests/[module]_test.php` covering every functional requirement in the spec
- [ ] Cover each edge case from the spec with its own test, not a manual click-through
- [ ] `php tests/run.php` exits 0 — paste the summary line here as evidence
- [ ] `bash tools/phpstan.sh` exits 0 — paste the result line here as evidence
- [ ] If this touched a view or stylesheet, render the page and look at it (the suite cannot)
- [ ] Full suite green, not just the new file (this is the regression check)
- [ ] Anything genuinely untestable over HTTP: name it, say why, and record how it was checked instead

## Notes
Anything Claude or the developer should know while implementing this plan.

- Once Phase 4 passes, merge this branch via pull request into the base branch, then delete the feature branch.
-

## Gauntlet Log
Required for Tier 2 and 3. Delete this section for Tier 0 and 1.

### Assumptions
- 

### Changed Files
- `path/to/file.php` — reason

### Verification Evidence
- `command` → result

### Verifier Findings
- [ ] Finding → required proof

### Deviations and Risks
- 
