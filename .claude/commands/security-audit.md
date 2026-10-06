Perform a security review of this Trongate PHP codebase.

Scan all files in the `modules/` directory and check for the following:

## Input & Output
- [ ] User input never trusted raw — always validated or sanitized before use
- [ ] Parameterized queries used for all database calls with user input (`query_bind`, not string concatenation)
- [ ] No raw `$_GET`, `$_POST`, or `$_REQUEST` passed directly into DB methods
- [ ] Output in views escaped with `htmlspecialchars()` or equivalent to prevent XSS

## Authentication & Authorization
- [ ] Protected methods verify a valid Trongate token before proceeding
- [ ] No sensitive methods publicly accessible without auth checks
- [ ] Token validation uses Trongate's built-in token system — no custom JWT or session hacks

## File Uploads
- [ ] File type validated server-side — not just by extension
- [ ] Uploaded files stored outside the web root or in a non-executable directory
- [ ] File size limits enforced

## Configuration
- [ ] No credentials, API keys, or secrets hardcoded in any module file
- [ ] Config values sourced from `config/config.php` only
- [ ] `config/` is in `.gitignore` or `~/.gitignore_global`

## General
- [ ] No `eval()`, `exec()`, `shell_exec()`, or `system()` calls unless explicitly required
- [ ] No direct inclusion of user-supplied file paths
- [ ] Error messages shown to users reveal no stack traces or system paths
- [ ] No commented-out debug code left in production files

For each issue found, report:
1. The file and line number
2. What the problem is
3. A recommended fix

If no issues are found in a category, mark it as clear. Summarise findings at the end.

$ARGUMENTS
