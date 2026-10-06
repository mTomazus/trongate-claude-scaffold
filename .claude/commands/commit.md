---
description: Review, stage and commit changes; pushes only when asked
argument-hint: "[push]"
---

Review all staged and unstaged changes in this project, then:

1. Run `git status` and `git diff` to see what changed
2. Stage the changes with `git add -A`, then run `git status --short` and show me the list
3. Stop and check the list before committing. If anything looks like it should not be committed — `config/`, `.deploy.env`, `.phar` files, credentials, `.DS_Store`, large binaries, or files unrelated to the work — unstage it and tell me why
4. Write a clear, concise commit message based on what actually changed
5. Follow this format:
   - First line: short summary under 60 characters (imperative tense, e.g. "Add team seeding questionnaire module")
   - Blank line
   - Bullet points covering any notable details, files added, or breaking changes
6. Run `git commit -m "[message]"`
7. Do NOT push. Only run `git push` if the arguments below say "push"

If there is nothing to commit, say so and stop.

$ARGUMENTS
