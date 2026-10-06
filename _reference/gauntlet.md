# Gauntlet Loop — Roles and Loop

Read this before starting Tier 2 or Tier 3 work. The tier definitions, Evidence Standard and Stop and Escalation Rules live in `AGENTS.md` §5 and still apply.

## Roles

Two agents are installed for this: `specifier` and `verifier` (see `.claude/agents/`). There is deliberately no separate refactorer or architect agent — that work collapses into the verifier plus the automated gates.

Keep these roles separate:

1. **Orchestrator** — you, the main session
   - Reads the spec, plan, constraints, and relevant reference material.
   - Delegates acceptance criteria to the `specifier` agent for Tier 2 and Tier 3 work.
   - Defines acceptance criteria and required proof before implementation.
   - Delegates bounded tasks and adjudicates verifier findings.
   - Does not implement while acting as the independent judge.

2. **Implementer** — you, or a delegated agent
   - Makes the smallest change that satisfies the assigned criteria.
   - Runs the required checks and records factual evidence.
   - Reports assumptions, deviations, changed files, and unresolved risks.
   - Never declares its own work accepted.

3. **Blind Verifier** — the `verifier` agent
   - Starts with fresh context.
   - Receives the spec, acceptance criteria, and the diff. **Do not pass it the implementer's self-assessment** — that is the thing it exists to check.
   - Runs the gates itself rather than trusting reported output.
   - Looks for unmet criteria, regressions, security issues, unnecessary scope, and violations of `AGENTS.md`.
   - Returns concrete findings with file/line references and a reproducible check.
   - Has no edit tools. It reports; the implementer fixes.

If independent subagents are unavailable, perform a distinct verification pass after implementation. Re-read the spec and inspect the diff as if reviewing someone else's work. Do not treat this as equivalent to a truly blind review.

## The Loop

```text
contract → implement → verify → fix findings → fresh re-verification → final integration check
```

1. **Contract**
   - Freeze the task boundary.
   - List acceptance criteria, forbidden changes, required tests, and approval gates.
   - Identify the smallest independently judgeable work units.

2. **Implement**
   - Complete one bounded unit at a time.
   - Keep changes surgical and follow the approved plan.
   - Record evidence in the plan's Gauntlet Log.

3. **Verify**
   - Use observable evidence: tests, static checks, rendered output, screenshots, database queries, logs, or exact reproduction steps.
   - Compare against the acceptance criteria, not against the previous attempt.
   - A passing test is necessary but not sufficient when behavior, security, or UI must also be inspected.

4. **Remediate**
   - Convert every valid finding into a bounded fix.
   - Do not widen scope while fixing findings.
   - Reject a finding only with specific evidence.

5. **Re-verify**
   - Prefer a fresh verifier for materially changed work.
   - Re-run failed checks and the relevant regression suite.
   - Continue only while each iteration produces new evidence or measurable progress.

6. **Finish**
   - Complete a final integrated review after all individual units pass.
   - Report criteria passed, checks run, deviations, open risks, and any human actions still required.
