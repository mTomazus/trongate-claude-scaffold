#!/bin/bash
# Runs static analysis. Always use this rather than calling the .phar directly.
#
# The memory limit is the reason this wrapper exists: at PHP's default 128M
# PHPStan dies partway through and reports the crash as a single error, which
# reads like a nearly-clean result. That is a false pass.
#
#   bash tools/phpstan.sh                 analyse everything
#   bash tools/phpstan.sh modules/teams   analyse one module

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PHAR="$PROJECT_DIR/tools/phpstan.phar"

if [ ! -f "$PHAR" ]; then
  echo "PHPStan is not installed. Run:  bash tools/install-phpstan.sh"
  exit 2
fi

cd "$PROJECT_DIR"
exec php "$PHAR" analyse -c phpstan.neon --no-progress --memory-limit=1G "$@"
