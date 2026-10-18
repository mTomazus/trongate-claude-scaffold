#!/usr/bin/env bash
# Trongate Scaffold Installer v2
# Usage: bash trongate-init.sh [--dry-run] [--force] [--reset-homepage] [--skip-docs]
# Run FROM a Trongate v2 project root. Place this script in the scaffold source.
# Existing files are preserved by default, including on first installation.
# Homepage files are protected even with --force.
# The opt-in --reset-homepage flag replaces them again (with backups).

set -euo pipefail

SCAFFOLD_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
TARGET_DIR="$(pwd -P)"
STATE_FILE="$TARGET_DIR/.trongate-init-state"
LEGACY_MARKER="$TARGET_DIR/_reference/trongate-memory.md"
DRY_RUN=0
FORCE=0
RESET_HOMEPAGE=0
SKIP_DOCS=0
FIRST_RUN=0
BACKED_UP=0
BACKUP_DIR=""
MERGED_SETTINGS=""

usage() {
  cat <<'EOF'
Usage: bash trongate-init.sh [options]

  --dry-run         Preview actions; do not modify any files or fetch docs
  --force           Refresh scaffold-managed files (back up replacements);
                    never replace custom homepage files on a rerun
  --reset-homepage  Explicitly replace Welcome.php, default_homepage.php and
                    modules/templates/views/public.php (backed up first)
  --skip-docs       Skip cloning/updating reference documentation
  -h, --help        Show this help
EOF
}

fail() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }
warn() { printf '  WARNING: %s\n' "$*" >&2; }
log() { printf '  %s\n' "$*"; }

for arg in "$@"; do
  case "$arg" in
    --dry-run) DRY_RUN=1 ;;
    --force) FORCE=1 ;;
    --reset-homepage) RESET_HOMEPAGE=1 ;;
    --skip-docs) SKIP_DOCS=1 ;;
    -h|--help) usage; exit 0 ;;
    *) fail "Unknown argument: $arg (see --help)" ;;
  esac
done

# Reject symlinks and non-files before reading/writing an installer-managed path.
check_dest() {
  local dest="$TARGET_DIR/$1" parent="$TARGET_DIR" segment rest="$1"
  while [[ "$rest" == */* ]]; do
    segment="${rest%%/*}"
    rest="${rest#*/}"
    parent="$parent/$segment"
    [ ! -L "$parent" ] || fail "Refusing symlinked parent directory: $parent"
    [ ! -e "$parent" ] || [ -d "$parent" ] || fail "Expected a directory: $parent"
  done
  [ ! -L "$dest" ] || fail "Refusing symlink destination: $1"
  [ ! -e "$dest" ] || [ -f "$dest" ] || fail "Expected a regular file: $1"
}

require_source() {
  [ -f "$SCAFFOLD_DIR/$1" ] || fail "Missing scaffold source file: $1"
}

# All validation is done BEFORE the installer makes any change.
preflight() {
  [ -d "$TARGET_DIR/engine" ] && [ -d "$TARGET_DIR/modules" ] ||
    fail "Run this inside a Trongate project root (engine/ and modules/ required)."
  [ -d "$TARGET_DIR/modules/welcome/views" ] ||
    fail "Expected Trongate v2 modules/welcome/views/; refusing an unknown layout."
  [ -d "$TARGET_DIR/modules/templates/views" ] ||
    fail "Expected modules/templates/views/; refusing an unknown layout."
  command -v php >/dev/null 2>&1 || fail "PHP CLI is required to validate and merge Claude settings."
  for cmd in cmp cp mkdir mktemp grep chmod mv head date cat; do
    command -v "$cmd" >/dev/null 2>&1 || fail "Missing required command: $cmd"
  done
  [ -w "$TARGET_DIR" ] || fail "Project root is not writable: $TARGET_DIR"
  [ "$TARGET_DIR" != "$SCAFFOLD_DIR" ] || fail "Run from the Trongate project, not the scaffold source directory."

  local rel
  for rel in \
    AGENTS.md CLAUDE.md \
    _reference/trongate-memory.md _reference/gauntlet.md \
    _reference/specs/SPEC_TEMPLATE.md _reference/plans/PLAN_TEMPLATE.md \
    .claude/commands/new-spec.md .claude/commands/new-plan.md \
    .claude/commands/commit.md .claude/commands/security-audit.md \
    .claude/commands/update-docs.md \
    .claude/agents/specifier.md .claude/agents/verifier.md \
    .claude/hooks/php_guard.php .claude/settings.json \
    project-files/tests/run.php project-files/tests/welcome_test.php \
    project-files/phpstan.neon \
    project-files/tools/phpstan-bootstrap.php \
    project-files/tools/install-phpstan.sh project-files/tools/phpstan.sh \
    project-files/public/css/app.css \
    project-files/modules/templates/views/public.php \
    project-files/modules/welcome/Welcome.php \
    project-files/modules/welcome/views/default_homepage.php \
    project-files/modules/welcome/views/components_showcase.php \
    project-files/deploy.sh project-files/.deploy.env; do
    require_source "$rel"
  done

  for rel in \
    AGENTS.md CLAUDE.md _reference/trongate-memory.md _reference/gauntlet.md \
    _reference/specs/SPEC_TEMPLATE.md _reference/plans/PLAN_TEMPLATE.md \
    .claude/commands/new-spec.md .claude/commands/new-plan.md \
    .claude/commands/commit.md .claude/commands/security-audit.md \
    .claude/commands/update-docs.md \
    .claude/agents/specifier.md .claude/agents/verifier.md \
    .claude/hooks/php_guard.php .claude/settings.json \
    tests/run.php tests/welcome_test.php phpstan.neon \
    tools/phpstan-bootstrap.php tools/install-phpstan.sh tools/phpstan.sh \
    public/css/app.css \
    modules/templates/views/public.php modules/welcome/Welcome.php \
    modules/welcome/views/default_homepage.php \
    modules/welcome/views/components_showcase.php \
    deploy.sh .deploy.env .gitignore .trongate-init-state; do
    check_dest "$rel"
  done

  [ ! -L "$TARGET_DIR/.trongate-init-backup" ] || fail "Refusing symlinked backup directory."
  [ ! -e "$TARGET_DIR/.trongate-init-backup" ] || [ -d "$TARGET_DIR/.trongate-init-backup" ] || fail "Expected a backup directory."
  # Merge in memory, even during dry-run. Validate before any state/file writes.
  prepare_claude_settings

  grep -qF '# TRONGATE_DEPLOY_V2' "$SCAFFOLD_DIR/project-files/deploy.sh" ||
    fail "Your project-files/deploy.sh is the old parent-directory version. Replace it with the bundled v2 deploy template first."
  if [ -f "$STATE_FILE" ]; then
    grep -q '^trongate-init-v2:' "$STATE_FILE" ||
      fail "Unrecognized init state format; inspect .trongate-init-state before proceeding."
  fi
}

# Compatibility: a project created with v1 already contains the reference
# memory. Never reset its homepage merely because it lacks v2's state file.
read_state() {
  if [ -f "$STATE_FILE" ]; then
    case "$(head -n 1 "$STATE_FILE")" in
      'trongate-init-v2:complete') FIRST_RUN=0 ;;
      'trongate-init-v2:installing:new') FIRST_RUN=1 ;;
      'trongate-init-v2:installing:existing') FIRST_RUN=0 ;;
      *) fail "Unknown init state in $STATE_FILE" ;;
    esac
  elif [ -f "$LEGACY_MARKER" ]; then
    FIRST_RUN=0
    log "Detected v1 initialization; preserving existing homepage files."
  else
    FIRST_RUN=1
  fi
}

begin_state() {
  if [ "$DRY_RUN" -eq 1 ]; then
    log "[dry-run] Would record installation state"
    return
  fi
  local phase=existing
  [ "$FIRST_RUN" -eq 0 ] || phase=new
  # The in-progress state is recorded before copying _reference memory,
  # so an interrupted first run is not mistaken for a completed v1 install.
  local tmp
  tmp="$(mktemp "$TARGET_DIR/.trongate-init-state.XXXXXX")"
  printf 'trongate-init-v2:installing:%s\n' "$phase" > "$tmp"
  mv -f "$tmp" "$STATE_FILE"
}

finish_state() {
  if [ "$DRY_RUN" -eq 1 ]; then
    log "[dry-run] Would mark initialization complete"
    return
  fi
  local tmp
  tmp="$(mktemp "$TARGET_DIR/.trongate-init-state.XXXXXX")"
  printf 'trongate-init-v2:complete\n' > "$tmp"
  mv -f "$tmp" "$STATE_FILE"
}

ensure_backup_dir() {
  if [ -z "$BACKUP_DIR" ]; then
    mkdir -p "$TARGET_DIR/.trongate-init-backup"
    BACKUP_DIR="$(mktemp -d "$TARGET_DIR/.trongate-init-backup/$(date +%Y%m%d-%H%M%S)-XXXXXX")"
    chmod 700 "$BACKUP_DIR"
  fi
}

backup_file() {
  local rel="$1"
  if [ "$DRY_RUN" -eq 1 ]; then
    log "[dry-run] Would back up $rel before replacement"
    return
  fi
  ensure_backup_dir
  mkdir -p "$(dirname "$BACKUP_DIR/$rel")"
  cp -p "$TARGET_DIR/$rel" "$BACKUP_DIR/$rel"
  BACKED_UP=$((BACKED_UP + 1))
}

# Policies: managed (--force only), once (first creation only),
# force-only (create if missing, or refresh only with --force),
# homepage (--reset-homepage only, even with --force).
install_file() {
  local src="$1" rel="$2" policy="${3:-managed}"
  local dest="$TARGET_DIR/$rel"
  check_dest "$rel"
  if [ -f "$dest" ]; then
    if cmp -s "$src" "$dest"; then
      log "$rel up to date"
      return
    fi
    if [ "$policy" = once ]; then
      log "$rel kept (user-owned; never overwritten by init)"
      return
    fi
    if [ "$policy" = force-only ] && [ "$FORCE" -eq 0 ]; then
      log "$rel kept (use --force to update the deploy helper)"
      return
    fi
    if [ "$policy" = homepage ]; then
      if [ "$RESET_HOMEPAGE" -eq 0 ]; then
        log "$rel kept (use --reset-homepage to replace)"
        return
      fi
    elif [ "$FORCE" -eq 0 ]; then
      log "$rel kept (customized; --force refreshes scaffold files)"
      return
    fi
    backup_file "$rel"
  fi
  if [ "$DRY_RUN" -eq 1 ]; then
    log "[dry-run] Would install $rel"
    return
  fi
  mkdir -p "$(dirname "$dest")"
  cp "$src" "$dest"
  case "$rel" in
    deploy.sh|tools/*.sh) chmod +x "$dest" ;;
  esac
  log "Installed $rel"
}

prepare_claude_settings() {
  MERGED_SETTINGS="$(php -r '
    try {
      $files = array_slice($argv, 1);
      $settings = [];
      foreach ($files as $file) {
        $data = is_file($file)
          ? json_decode(file_get_contents($file), false, 512, JSON_THROW_ON_ERROR)
          : new stdClass();
        if (!is_object($data)) throw new RuntimeException("Settings root must be an object");
        foreach (["permissions", "hooks"] as $key) {
          if (property_exists($data, $key) && !is_object($data->$key)) {
            throw new RuntimeException("{$key} must be an object");
          }
        }
        foreach (["allow", "ask", "deny"] as $key) {
          if (isset($data->permissions) && property_exists($data->permissions, $key)) {
            if (!is_array($data->permissions->$key)) throw new RuntimeException("permissions.{$key} must be an array");
            foreach ($data->permissions->$key as $rule) {
              if (!is_string($rule)) throw new RuntimeException("Permission rules must be strings");
            }
          }
        }
        foreach (($data->hooks ?? new stdClass()) as $event => $entries) {
          if (!is_array($entries)) throw new RuntimeException("hooks.{$event} must be an array");
        }
        $settings[] = $data;
      }
      [$source, $target] = $settings;
      $target->permissions ??= new stdClass();
      $target->permissions->deny ??= [];
      foreach (($source->permissions->deny ?? []) as $rule) {
        if (!in_array($rule, $target->permissions->deny, true)) $target->permissions->deny[] = $rule;
      }
      $target->hooks ??= new stdClass();
      foreach (($source->hooks ?? new stdClass()) as $event => $entries) {
        $target->hooks->$event ??= [];
        foreach ($entries as $entry) {
          if (!in_array($entry, $target->hooks->$event)) $target->hooks->{$event}[] = $entry;
        }
      }
      $encoded = json_encode($target, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
      if (is_file($files[1])) {
        $original = file_get_contents($files[1]);
        if (json_decode($original) == $target) $encoded = $original;
      }
      echo $encoded;
    } catch (Throwable $e) {
      fwrite(STDERR, "Claude settings validation/merge failed: " . $e->getMessage() . "\n");
      exit(1);
    }
  ' "$SCAFFOLD_DIR/.claude/settings.json" "$TARGET_DIR/.claude/settings.json")" || fail "Settings validation failed."
}

install_claude_settings() {
  local rel='.claude/settings.json' dest="$TARGET_DIR/.claude/settings.json" staged
  if [ ! -f "$dest" ]; then
    install_file "$SCAFFOLD_DIR/.claude/settings.json" "$rel" once
  elif [ "$(cat "$dest")" = "$MERGED_SETTINGS" ]; then
    log "$rel already includes required hooks and deny rules"
  elif [ "$DRY_RUN" -eq 1 ]; then
    log "[dry-run] Would merge scaffold hooks/deny rules into $rel"
  else
    backup_file "$rel"
    staged="$(mktemp "$TARGET_DIR/.claude/.settings.XXXXXX")"
    printf '%s\n' "$MERGED_SETTINGS" > "$staged"
    chmod 600 "$staged"
    mv -f "$staged" "$dest"
    log "Merged required Claude hooks and deny rules into $rel"
  fi
}

update_gitignore() {
  local dest="$TARGET_DIR/.gitignore" pattern changes=() 
  local patterns=("_reference/trongate-docs-repo/" "tools/*.phar" ".DS_Store" ".trongate-init-backup/" ".deploy.env" "config/*.local.php" "config_*/**/*.local.php")
  for pattern in "${patterns[@]}"; do
    if [ ! -f "$dest" ] || ! grep -qxF "$pattern" "$dest"; then
      changes+=("$pattern")
    fi
  done
  if [ "${#changes[@]}" -eq 0 ]; then
    log ".gitignore includes preferred defaults"
  elif [ "$DRY_RUN" -eq 1 ]; then
    log "[dry-run] Would add ${#changes[@]} ignore entries to .gitignore"
  else
    # Appends only, existing patterns are retained as requested.
    if [ -f "$dest" ]; then backup_file '.gitignore'; fi
    { printf '\n# Added by trongate-init v2\n'; printf '%s\n' "${changes[@]}"; } >> "$dest"
    log "Updated .gitignore (existing entries preserved)"
  fi
  if [ -f "$dest" ] && grep -qxF 'config/' "$dest"; then
    warn "Your preexisting .gitignore excludes config/ entirely; ensure new clones receive safe config examples."
  fi
  if command -v git >/dev/null 2>&1 && git -C "$TARGET_DIR" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    if git -C "$TARGET_DIR" ls-files --error-unmatch .deploy.env >/dev/null 2>&1; then
      warn ".deploy.env is already tracked by Git! Untrack it separately and rotate any exposed credentials."
    fi
  fi
}

secure_deploy_env() {
  local dest="$TARGET_DIR/.deploy.env"
  if [ "$DRY_RUN" -eq 1 ]; then
    log "[dry-run] Would restrict .deploy.env to owner-only permissions (600)"
  else
    chmod 600 "$dest"
    log "Set .deploy.env permissions to 600"
  fi
  # Legacy v1 environment variables cannot be mapped without seeing the old
  # server-specific template. Preserve and warn instead of guessing.
  if [ -f "$dest" ] && ! grep -q '^DEPLOY_REMOTE_PATH=' "$dest"; then
    warn ".deploy.env is not in v2 format (needs DEPLOY_HOST, DEPLOY_USER and DEPLOY_REMOTE_PATH). Review it before deployment."
  fi
}

update_docs() {
  [ "$SKIP_DOCS" -eq 0 ] || { log "Reference docs skipped (--skip-docs)"; return; }
  local docs="$TARGET_DIR/_reference/trongate-docs-repo"
  if [ -L "$docs" ]; then
    warn "Docs directory is a symlink; refusing to update it"
    return
  fi
  if [ "$DRY_RUN" -eq 1 ]; then
    log "[dry-run] Would clone/update reference docs"
    return
  fi
  if ! command -v git >/dev/null 2>&1; then
    warn "git is unavailable; reference docs not updated"
  elif [ -d "$docs/.git" ]; then
    if git -C "$docs" pull --ff-only; then log "Trongate docs updated";
    else warn "Could not update docs; run /update-docs later"; fi
  elif [ -e "$docs" ]; then
    warn "$docs exists but is not a Git checkout; docs untouched"
  elif git clone --depth=1 https://github.com/trongate/trongate-docs.git "$docs"; then
    log "Trongate docs cloned"
  else
    warn "Could not clone docs; run /update-docs later"
  fi
}

preflight
read_state

printf '\n========================================\nTrongate Scaffold Installer v2%s\n========================================\n' "$([ "$DRY_RUN" -eq 1 ] && printf ' (dry-run)' || true)"
if [ "$FIRST_RUN" -eq 1 ]; then
  log 'Mode: first installation (existing files preserved)'
else
  log 'Mode: rerun (homepage will be preserved unless --reset-homepage)'
fi
begin_state

echo ''
echo 'Installing AI instructions and reference files...'
install_file "$SCAFFOLD_DIR/AGENTS.md" 'AGENTS.md' once
install_file "$SCAFFOLD_DIR/CLAUDE.md" 'CLAUDE.md' once
if [ -f "$TARGET_DIR/CLAUDE.md" ] && ! grep -qxF '@AGENTS.md' "$TARGET_DIR/CLAUDE.md"; then
  warn 'CLAUDE.md does not contain an exact @AGENTS.md import; review AI instruction consistency.'
fi
install_file "$SCAFFOLD_DIR/_reference/trongate-memory.md" '_reference/trongate-memory.md'
install_file "$SCAFFOLD_DIR/_reference/gauntlet.md" '_reference/gauntlet.md'
install_file "$SCAFFOLD_DIR/_reference/specs/SPEC_TEMPLATE.md" '_reference/specs/SPEC_TEMPLATE.md'
install_file "$SCAFFOLD_DIR/_reference/plans/PLAN_TEMPLATE.md" '_reference/plans/PLAN_TEMPLATE.md'
for cmd in new-spec new-plan commit security-audit update-docs; do
  install_file "$SCAFFOLD_DIR/.claude/commands/$cmd.md" ".claude/commands/$cmd.md"
done
install_file "$SCAFFOLD_DIR/.claude/agents/specifier.md" '.claude/agents/specifier.md'
install_file "$SCAFFOLD_DIR/.claude/agents/verifier.md" '.claude/agents/verifier.md'
install_file "$SCAFFOLD_DIR/.claude/hooks/php_guard.php" '.claude/hooks/php_guard.php'
install_claude_settings
install_file "$SCAFFOLD_DIR/project-files/tests/run.php" 'tests/run.php'
install_file "$SCAFFOLD_DIR/project-files/tests/welcome_test.php" 'tests/welcome_test.php' once

echo ''
echo 'Installing project tooling and UI...'
install_file "$SCAFFOLD_DIR/project-files/phpstan.neon" 'phpstan.neon'
install_file "$SCAFFOLD_DIR/project-files/tools/phpstan-bootstrap.php" 'tools/phpstan-bootstrap.php'
install_file "$SCAFFOLD_DIR/project-files/tools/install-phpstan.sh" 'tools/install-phpstan.sh'
install_file "$SCAFFOLD_DIR/project-files/tools/phpstan.sh" 'tools/phpstan.sh'
install_file "$SCAFFOLD_DIR/project-files/public/css/app.css" 'public/css/app.css'
install_file "$SCAFFOLD_DIR/project-files/modules/templates/views/public.php" 'modules/templates/views/public.php' homepage
install_file "$SCAFFOLD_DIR/project-files/modules/welcome/Welcome.php" 'modules/welcome/Welcome.php' homepage
install_file "$SCAFFOLD_DIR/project-files/modules/welcome/views/default_homepage.php" 'modules/welcome/views/default_homepage.php' homepage
install_file "$SCAFFOLD_DIR/project-files/modules/welcome/views/components_showcase.php" 'modules/welcome/views/components_showcase.php'

echo ''
echo 'Installing project-local deployment helpers...'
install_file "$SCAFFOLD_DIR/project-files/deploy.sh" 'deploy.sh' force-only
install_file "$SCAFFOLD_DIR/project-files/.deploy.env" '.deploy.env' once
secure_deploy_env
if [ -f "$TARGET_DIR/deploy.sh" ] && ! grep -qF '# TRONGATE_DEPLOY_V2' "$TARGET_DIR/deploy.sh"; then
  warn 'Existing project-local deploy.sh is not v2; review it before use (or replace manually).'
fi
update_gitignore

echo ''
echo 'Updating reference documentation...'
update_docs
finish_state

printf '\n========================================\n'
if [ "$DRY_RUN" -eq 1 ]; then
  echo 'Dry-run complete: no project files changed.'
else
  echo 'Trongate scaffold v2 initialization complete.'
fi
printf '========================================\n'
if [ "$BACKED_UP" -gt 0 ]; then
  printf 'Backed up %s file(s) to %s\n' "$BACKED_UP" "${BACKUP_DIR#$TARGET_DIR/}/"
fi
printf '%s\n' 'Next:' '  1. Run php tests/run.php (requires your local server).' '  2. Run bash tools/install-phpstan.sh to install PHPStan.' '  3. Customize modules/welcome/views/default_homepage.php.' '  4. Edit .deploy.env and run ./deploy.sh (dry-run by default).' '  5. Use --reset-homepage only when you explicitly want to restore the demo.'
