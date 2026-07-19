# =============================================================================
# sync_textures.sh
# -----------------------------------------------------------------------------
# The Item Icon Exporter mod writes images directly inside the website's
# public/images/item_textures/ directory, under a timestamped sub-folder:
#
#   public/images/item_textures/<TIMESTAMP>/<modid>/<itemname>_128x128.png
#
# This script finds the latest timestamp sub-folder and promotes its contents
# one level up so that the Laravel models can resolve them as:
#
#   public/images/item_textures/<modid>/<itemname>_128x128.png
#
# Usage:
#   ./sync_textures.sh [--dry-run] [--keep-timestamp-dir]
#
# Options:
#   --dry-run             Show what would happen without writing anything.
#   --keep-timestamp-dir  Do not delete the timestamp folder after syncing.
# =============================================================================

set -euo pipefail

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEXTURES_DIR="${SCRIPT_DIR}/public/images/item_textures"

# ---------------------------------------------------------------------------
# Parse arguments
# ---------------------------------------------------------------------------
DRY_RUN=false
KEEP_TIMESTAMP_DIR=false

for arg in "$@"; do
  case "$arg" in
    --dry-run)
      DRY_RUN=true
      echo "[INFO] Dry-run mode enabled — no files will be written."
      ;;
    --keep-timestamp-dir)
      KEEP_TIMESTAMP_DIR=true
      ;;
    *)
      echo "[ERROR] Unknown argument: $arg"
      echo "Usage: $0 [--dry-run] [--keep-timestamp-dir]"
      exit 1
      ;;
  esac
done

# ---------------------------------------------------------------------------
# Validate that the textures directory exists
# ---------------------------------------------------------------------------
if [[ ! -d "$TEXTURES_DIR" ]]; then
  echo "[ERROR] Textures directory not found: $TEXTURES_DIR"
  exit 1
fi

# ---------------------------------------------------------------------------
# Find the most recent timestamp sub-folder inside item_textures/
# Timestamp folders are all-numeric (e.g. 20260719212944).
# ---------------------------------------------------------------------------
LATEST_DIR=$(find "$TEXTURES_DIR" -mindepth 1 -maxdepth 1 -type d \
  -name '[0-9]*' | sort | tail -1)

if [[ -z "$LATEST_DIR" ]]; then
  echo "[ERROR] No timestamped export folder found inside:"
  echo "        $TEXTURES_DIR"
  echo "        Run the in-game export first (Numpad 8 or /arffornia_export)."
  exit 1
fi

TIMESTAMP=$(basename "$LATEST_DIR")
echo "[INFO] Latest export folder: $LATEST_DIR"

# Count source files for a quick sanity check
FILE_COUNT=$(find "$LATEST_DIR" -type f -name "*.png" | wc -l)
echo "[INFO] Found ${FILE_COUNT} PNG file(s) to promote."

if [[ "$FILE_COUNT" -eq 0 ]]; then
  echo "[WARNING] The timestamp folder contains no PNG files. Nothing to do."
  exit 0
fi

# ---------------------------------------------------------------------------
# Promote contents one level up using rsync.
#   Source : public/images/item_textures/<TIMESTAMP>/   (trailing slash = contents)
#   Dest   : public/images/item_textures/
#
#   -a            : archive (preserve permissions, timestamps, etc.)
#   -v            : verbose
#   --progress    : show per-file progress
#   --exclude     : skip other timestamp folders that might be present
# ---------------------------------------------------------------------------
RSYNC_OPTS=(-av --progress)

# Exclude all timestamp-looking directories so we don't nest them
RSYNC_OPTS+=(--exclude='[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]')

if $DRY_RUN; then
  RSYNC_OPTS+=(--dry-run)
fi

echo ""
echo "[INFO] Promoting contents of timestamp folder to textures root..."
echo "       SRC : ${LATEST_DIR}/"
echo "       DEST: ${TEXTURES_DIR}/"
echo ""

rsync "${RSYNC_OPTS[@]}" "${LATEST_DIR}/" "${TEXTURES_DIR}/"

# ---------------------------------------------------------------------------
# Remove the now-redundant timestamp folder (unless --keep-timestamp-dir)
# ---------------------------------------------------------------------------
if ! $DRY_RUN; then
  if $KEEP_TIMESTAMP_DIR; then
    echo "[INFO] Keeping timestamp folder as requested: $LATEST_DIR"
  else
    echo "[INFO] Removing timestamp folder: $LATEST_DIR"
    rm -rf "$LATEST_DIR"
    echo "[INFO] Timestamp folder removed."
  fi
fi

echo ""
echo "[SUCCESS] Done! ${FILE_COUNT} PNG file(s) are now available at:"
echo "          ${TEXTURES_DIR}/<modid>/<itemname>.png"
