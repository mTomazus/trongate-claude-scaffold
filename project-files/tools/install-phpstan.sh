#!/bin/bash
# Enables static analysis for this Trongate project.
#
#   bash tools/install-phpstan.sh
#
# Downloads a pinned phpstan.phar into tools/ and generates a baseline so the
# errors that already exist in Trongate's core modules are recorded once and
# never reported again. From then on you only see problems in code you wrote.
#
# The .phar is dev tooling, not an application dependency: it is gitignored,
# never autoloaded, and never deployed. tools/ is not vendor/.

set -e

PHPSTAN_VERSION="2.2.8"
PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PHAR="$PROJECT_DIR/tools/phpstan.phar"

if [ ! -f "$PROJECT_DIR/phpstan.neon" ]; then
  echo "ERROR: phpstan.neon not found at the project root."
  echo "Run this from a project set up by trongate-init."
  exit 1
fi

if [ -f "$PHAR" ]; then
  echo "phpstan.phar already present ($(php "$PHAR" --version 2>/dev/null || echo 'unknown version'))"
else
  echo "Downloading PHPStan $PHPSTAN_VERSION..."
  curl -fsSL -o "$PHAR" \
    "https://github.com/phpstan/phpstan/releases/download/${PHPSTAN_VERSION}/phpstan.phar"
  echo "  Saved to tools/phpstan.phar"
fi

# The config includes the baseline, so it must exist before the first run.
if [ ! -f "$PROJECT_DIR/phpstan-baseline.neon" ]; then
  echo "parameters:" > "$PROJECT_DIR/phpstan-baseline.neon"
fi

echo ""
echo "Generating baseline for existing Trongate core errors..."
cd "$PROJECT_DIR"
php "$PHAR" analyse -c phpstan.neon --no-progress --memory-limit=1G \
  --generate-baseline=phpstan-baseline.neon || true

echo ""
echo "Done. Analysis now runs clean:"
echo "  bash tools/phpstan.sh"
echo ""
echo "Commit phpstan-baseline.neon. Do not commit tools/phpstan.phar."
