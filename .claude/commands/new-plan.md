---
description: Create an implementation plan in _reference/plans/ from an existing spec
argument-hint: "[spec filename without .md]"
---

Create a new implementation plan in `_reference/plans/` based on an existing spec.

Use the template at `_reference/plans/PLAN_TEMPLATE.md` as the structure.

Steps:
1. Read the spec at `_reference/specs/$ARGUMENTS.md`
2. Classify the work as Tier 0, 1, 2 or 3 using the rules in AGENTS.md §5, and record it
3. Fill in Acceptance Criteria. For Tier 2 and 3, delegate this to the `specifier` agent and use its output; carry its Open Questions into the plan
4. Fill in Forbidden Changes and Approval Gates
5. List every file that will be created or modified
6. Break the work into phases with checkbox tasks
7. Keep the Gauntlet Log section for Tier 2 and 3; delete it for Tier 0 and 1
8. Save the plan to `_reference/plans/$ARGUMENTS_plan.md`

Spec name: $ARGUMENTS
