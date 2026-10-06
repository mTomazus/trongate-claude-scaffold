#!/bin/bash
# Usage: bash trongate-init.sh [--force]
# Run from the root of a new Trongate project
# Sets up the Claude scaffold, project files and verification gates
#
# Safe to re-run: once a project has been initialised, any file you have
# changed is kept. --force replaces those with the scaffold's version.

set -e

SCAFFOLD_DIR="$(cd "$(dirname "$0")" && pwd)"
TARGET_DIR="$(pwd)"

FORCE=0
if [ "${1:-}" = "--force" ]; then
  FORCE=1
elif [ -n "${1:-}" ]; then
  echo "Unknown argument '$1'. Usage: trongate-init [--force]" >&2
  exit 1
fi

# Everything below writes into the current folder, and deploy files into the
# one above it. Refuse to do that anywhere but a Trongate project root.
if [ ! -d "$TARGET_DIR/engine" ] || [ ! -d "$TARGET_DIR/modules" ]; then
  echo "ERROR: $TARGET_DIR is not a Trongate project root (no engine/ and modules/)." >&2
  echo "       cd into your Trongate project, then run this again." >&2
  exit 1
fi

# A project that already has the memory file has been initialised before.
# On a first run the stock Trongate files are replaced; after that, edits win.
FIRST_RUN=1
if [ -f "$TARGET_DIR/_reference/trongate-memory.md" ]; then
  FIRST_RUN=0
fi

# install_file <path relative to scaffold source> <path relative to project> [once]
#   - identical file already there: nothing to do
#   - "once": never replace an existing file (unless --force)
#   - otherwise replace on a first run, keep the user's version on a re-run
install_file() {
  local src="$1" rel="$2" once="${3:-}"
  local dest="$TARGET_DIR/$rel"

  if [ -f "$dest" ]; then
    if cmp -s "$src" "$dest"; then
      echo "  $rel is up to date"
      return
    fi
    if [ "$FORCE" -eq 0 ] && { [ -n "$once" ] || [ "$FIRST_RUN" -eq 0 ]; }; then
      echo "  $rel differs from the scaffold - kept yours (--force replaces it)"
      return
    fi
  fi

  mkdir -p "$(dirname "$dest")"
  cp "$src" "$dest"
  echo "  Wrote $rel"
}

echo "========================================"
echo "Trongate Claude Scaffold"
echo "========================================"
echo ""

# ============================================
# PART 1: Claude scaffold files
# ============================================

echo "Installing Claude scaffold files..."

# AGENTS.md is the manual (read by Codex and other agents); CLAUDE.md just
# imports it, because Claude Code only reads CLAUDE.md.
install_file "$SCAFFOLD_DIR/AGENTS.md" "AGENTS.md" once
install_file "$SCAFFOLD_DIR/CLAUDE.md" "CLAUDE.md" once
if ! grep -qxF '@AGENTS.md' "$TARGET_DIR/CLAUDE.md"; then
  echo "  NOTE: your CLAUDE.md does not import AGENTS.md, so Claude Code and other agents"
  echo "        read different manuals. Move anything you added into AGENTS.md, then make"
  echo "        CLAUDE.md contain just the line:  @AGENTS.md"
fi

install_file "$SCAFFOLD_DIR/_reference/trongate-memory.md" "_reference/trongate-memory.md"
install_file "$SCAFFOLD_DIR/_reference/gauntlet.md" "_reference/gauntlet.md"
install_file "$SCAFFOLD_DIR/_reference/specs/SPEC_TEMPLATE.md" "_reference/specs/SPEC_TEMPLATE.md"
install_file "$SCAFFOLD_DIR/_reference/plans/PLAN_TEMPLATE.md" "_reference/plans/PLAN_TEMPLATE.md"

for cmd in new-spec new-plan commit security-audit update-docs; do
  install_file "$SCAFFOLD_DIR/.claude/commands/$cmd.md" ".claude/commands/$cmd.md"
done

# Gauntlet agents (specifier + verifier)
install_file "$SCAFFOLD_DIR/.claude/agents/specifier.md" ".claude/agents/specifier.md"
install_file "$SCAFFOLD_DIR/.claude/agents/verifier.md" ".claude/agents/verifier.md"

# Verification gates: the guard hook plus the zero-dependency test harness
install_file "$SCAFFOLD_DIR/.claude/hooks/php_guard.php" ".claude/hooks/php_guard.php"

# settings.json may already carry the user's own permissions - never clobber it
if [ -f "$TARGET_DIR/.claude/settings.json" ]; then
  if ! cmp -s "$SCAFFOLD_DIR/.claude/settings.json" "$TARGET_DIR/.claude/settings.json"; then
    echo "  .claude/settings.json already exists. Kept yours - merge the php_guard hook and deny rules by hand if you want them."
  fi
else
  install_file "$SCAFFOLD_DIR/.claude/settings.json" ".claude/settings.json"
fi

install_file "$SCAFFOLD_DIR/project-files/tests/run.php" "tests/run.php"
install_file "$SCAFFOLD_DIR/project-files/tests/welcome_test.php" "tests/welcome_test.php" once

# Add preferred defaults to .gitignore. Existing entries are never discarded.
touch "$TARGET_DIR/.gitignore"
gitignore_changed=0
for pattern in "config/" "config_*/" "_reference/trongate-docs-repo/" "tools/*.phar" ".DS_Store"; do
  if ! grep -qxF "$pattern" "$TARGET_DIR/.gitignore"; then
    if [ "$gitignore_changed" -eq 0 ]; then
      printf '\n# Added by trongate-init\n' >> "$TARGET_DIR/.gitignore"
      gitignore_changed=1
    fi
    echo "$pattern" >> "$TARGET_DIR/.gitignore"
  fi
done
if [ "$gitignore_changed" -eq 1 ]; then
  echo "  Updated .gitignore (existing entries preserved)"
else
  echo "  .gitignore already carries the preferred defaults"
fi

# Fetch latest Trongate docs. A network failure here should not abort the
# rest of the install, so it only warns.
echo ""
echo "Fetching latest Trongate docs..."
if [ -d "$TARGET_DIR/_reference/trongate-docs-repo" ]; then
  if (cd "$TARGET_DIR/_reference/trongate-docs-repo" && git pull); then
    echo "  Trongate docs updated."
  else
    echo "  WARNING: could not update the docs. Run /update-docs later."
  fi
else
  if git clone --depth=1 https://github.com/trongate/trongate-docs.git "$TARGET_DIR/_reference/trongate-docs-repo"; then
    echo "  Trongate docs cloned."
  else
    echo "  WARNING: could not clone the docs. Run /update-docs later."
  fi
fi

# ============================================
# PART 2: Project files
# ============================================
# The project-files/ folder in this scaffold mirrors the destination
# structure exactly. We just copy it on top of the project root.
# Styling is Trongate's own css/trongate.css - no build step required.

echo ""
echo "Installing project files..."

# Static analysis config. The .phar itself is downloaded on demand by
# tools/install-phpstan.sh so init stays fast and needs no extra network.
install_file "$SCAFFOLD_DIR/project-files/phpstan.neon" "phpstan.neon"
install_file "$SCAFFOLD_DIR/project-files/tools/phpstan-bootstrap.php" "tools/phpstan-bootstrap.php"
install_file "$SCAFFOLD_DIR/project-files/tools/install-phpstan.sh" "tools/install-phpstan.sh"
install_file "$SCAFFOLD_DIR/project-files/tools/phpstan.sh" "tools/phpstan.sh"
chmod +x "$TARGET_DIR/tools/install-phpstan.sh" "$TARGET_DIR/tools/phpstan.sh"

# Component styles that extend Trongate's native css/trongate.css
install_file "$SCAFFOLD_DIR/project-files/public/css/app.css" "public/css/app.css"

# Replace the public.php template
if [ -d "$TARGET_DIR/modules/templates/views" ]; then
  install_file "$SCAFFOLD_DIR/project-files/modules/templates/views/public.php" "modules/templates/views/public.php"
else
  echo "  WARNING: modules/templates/views/ not found. Skipping public.php replacement."
  echo "           Stock Trongate templates already load css/trongate.css."
fi

# Replace the welcome module: controller + under-construction page + showcase view
if [ -d "$TARGET_DIR/modules/welcome/views" ]; then
  # Controller sits directly in the module folder (Trongate v2 - no controllers/ subfolder)
  install_file "$SCAFFOLD_DIR/project-files/modules/welcome/Welcome.php" "modules/welcome/Welcome.php"
  install_file "$SCAFFOLD_DIR/project-files/modules/welcome/views/default_homepage.php" "modules/welcome/views/default_homepage.php"
  install_file "$SCAFFOLD_DIR/project-files/modules/welcome/views/components_showcase.php" "modules/welcome/views/components_showcase.php"
else
  echo "  WARNING: modules/welcome/views/ not found. Skipping welcome module replacement."
fi

# Deploy helper sits one level ABOVE the project root so it is never synced or
# served. That is outside the directory this script was pointed at, so both
# files are only ever created, never overwritten - .deploy.env holds real
# server details.
PARENT_DIR="$(dirname "$TARGET_DIR")"
PROJECT_NAME="$(basename "$TARGET_DIR")"

if [ -f "$PARENT_DIR/deploy.sh" ]; then
  if cmp -s "$SCAFFOLD_DIR/project-files/deploy.sh" "$PARENT_DIR/deploy.sh"; then
    echo "  ../deploy.sh is up to date"
  elif ! grep -q 'DEPLOY_SOURCE' "$PARENT_DIR/deploy.sh"; then
    echo "  WARNING: ../deploy.sh is an old version that syncs the folder it sits in"
    echo "           (the PARENT of this project) to the server. Do not run it."
    echo "           Copy any server details into ../.deploy.env, then replace it with:"
    echo "           cp \"$SCAFFOLD_DIR/project-files/deploy.sh\" \"$PARENT_DIR/deploy.sh\""
  else
    echo "  ../deploy.sh already exists. Leaving it alone."
  fi
else
  cp "$SCAFFOLD_DIR/project-files/deploy.sh" "$PARENT_DIR/deploy.sh"
  chmod +x "$PARENT_DIR/deploy.sh"
  echo "  Created ../deploy.sh"
fi

if [ -f "$PARENT_DIR/.deploy.env" ]; then
  echo "  ../.deploy.env already exists. Leaving it alone."
  if ! grep -q '^DEPLOY_SOURCE=' "$PARENT_DIR/.deploy.env"; then
    echo "  WARNING: ../.deploy.env has no DEPLOY_SOURCE line. Add:  DEPLOY_SOURCE=$PROJECT_NAME"
  fi
else
  sed "s/^DEPLOY_SOURCE=.*/DEPLOY_SOURCE=$PROJECT_NAME/" \
    "$SCAFFOLD_DIR/project-files/.deploy.env" > "$PARENT_DIR/.deploy.env"
  echo "  Created ../.deploy.env - fill in your server details"
fi

# ============================================
# DONE
# ============================================

echo ""
echo "========================================"
echo "Setup complete."
echo "========================================"
echo ""
echo "Your Trongate project is ready with:"
echo "  - Claude scaffold (AGENTS.md + CLAUDE.md, slash commands, reference docs)"
echo "  - Trongate's native css/trongate.css plus public/css/app.css - no build step"
echo "  - Under-construction homepage (shown by default)"
echo "  - Component showcase at /welcome/components"
echo "  - Verification gates: tests/run.php, php_guard hook, PHPStan config"
echo "  - ../deploy.sh, configured by ../.deploy.env"
echo ""
echo "Next steps:"
echo "  1. Run 'php tests/run.php' to confirm the app answers (needs your local server up)"
echo "  2. Run 'bash tools/install-phpstan.sh' once to enable static analysis"
echo "  3. Visit /welcome/components to see the component reference"
echo "  4. Edit the under-construction page: update the contact email"
echo "     and company name in modules/welcome/views/default_homepage.php"
echo "  5. Fill in your server details in ../.deploy.env, then dry-run ../deploy.sh"
echo ""
