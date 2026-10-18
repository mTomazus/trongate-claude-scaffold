#!/usr/bin/env bash
# Scaffold repository only; not copied into installed Trongate projects.
# Usage: bash tools/check-scaffold.sh [base-revision]
set -euo pipefail

ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"
cd "$ROOT"

if [ "$#" -gt 1 ]; then
  echo 'Usage: bash tools/check-scaffold.sh [base-revision]' >&2
  exit 2
fi
for command in git php; do
  command -v "$command" >/dev/null 2>&1 || { echo "Missing required command: $command" >&2; exit 2; }
done
php -r 'exit(PHP_VERSION_ID >= 80000 ? 0 : 2);' || { echo 'PHP CLI 8.0+ is required.' >&2; exit 2; }

echo 'Checking whitespace...'
git diff --check
git diff --cached --check
if [ -n "${1:-}" ]; then
  base="$1"
  # New branches have no previous tip. Check the new tip's patch; a PR checks
  # the full proposed range against its base, without auditing legacy whitespace.
  if [ "$base" = '0000000000000000000000000000000000000000' ]; then
    git show --check --format= HEAD --
  else
    git diff --check "$base" HEAD --
  fi
fi

echo 'Checking active PHP and Bash syntax...'
# Include tracked and new files, but omit historical copies at the repo root.
while IFS= read -r -d '' file; do
  [ -f "$file" ] || continue
  case "$file" in
    *.php) php -l "$file" ;;
    *.sh) bash -n "$file" ;;
  esac
done < <(git ls-files --cached --others --exclude-standard -z -- \
  trongate-init.sh project-files/ .claude/hooks/ tests/ tools/)
bash -n project-files/.deploy.env

php tests/installer_test.php
php tests/php_guard_test.php
echo 'Scaffold checks passed.'
