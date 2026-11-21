#!/usr/bin/env bash
# Uploads an icon export (scripts/icons/export.sh) to the `item-icons` Garage bucket, served at
# https://<website>/icons/ (route in TheGostsniperfr/infra, k8s/config/garage-s3).
# Usage: scripts/icons/publish.sh <export-dir>
# Order matters for clients: new and changed PNGs first, then the fallbacks, then manifest.json,
# and only then icons that left the pack are deleted.
# The upload key is read from Garage through kubectl at run time, never stored or printed.
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "usage: $0 <export-dir>" >&2
  exit 2
fi
dir=$(realpath "$1")
[[ -f "$dir/manifest.json" ]] || { echo "no manifest.json in $dir" >&2; exit 1; }
context=${KUBE_CONTEXT:-main-k8s-admin@kubernetes}
bucket=${ICONS_BUCKET:-item-icons}

python3 - "$dir/manifest.json" <<'EOF'
import json, sys
m = json.load(open(sys.argv[1]))
if m.get("format") != 1 or not m.get("items"):
    sys.exit("manifest.json: unknown format or no items")
EOF

sizes=$(python3 -c 'import json,sys; print(" ".join(map(str, json.load(open(sys.argv[1]))["sizes"])))' "$dir/manifest.json")
for size in $sizes; do
  fallback="$dir/$size/_fallback.png"
  if [[ ! -f "$fallback" ]]; then
    radius=$((size / 6)); inset=$((size / 8)); stroke=$(((size + 31) / 32))
    nix shell nixpkgs#imagemagick -c magick -size "${size}x${size}" xc:none \
      -fill '#8a8f9833' -stroke '#8a8f98' -strokewidth "$stroke" \
      -draw "roundrectangle $inset,$inset $((size - inset - 1)),$((size - inset - 1)) $radius,$radius" \
      "$fallback"
  fi
done

key_info=$(kubectl --context "$context" -n garage-s3 exec garage-0 -c garage -- \
  /garage key info item-icons-key --show-secret 2>/dev/null)
export RCLONE_CONFIG_ICONS_TYPE=s3
export RCLONE_CONFIG_ICONS_PROVIDER=Other
export RCLONE_CONFIG_ICONS_ENDPOINT=${S3_ENDPOINT:-https://s3.arffornia.com}
export RCLONE_CONFIG_ICONS_REGION=garage
export RCLONE_CONFIG_ICONS_FORCE_PATH_STYLE=true
RCLONE_CONFIG_ICONS_ACCESS_KEY_ID=$(awk -F': *' '/^Key ID/ {print $2}' <<<"$key_info")
RCLONE_CONFIG_ICONS_SECRET_ACCESS_KEY=$(awk -F': *' '/^Secret key/ {print $2}' <<<"$key_info")
export RCLONE_CONFIG_ICONS_ACCESS_KEY_ID RCLONE_CONFIG_ICONS_SECRET_ACCESS_KEY
unset key_info
[[ -n "$RCLONE_CONFIG_ICONS_SECRET_ACCESS_KEY" ]] || { echo "cannot read the item-icons-key from Garage" >&2; exit 1; }

rclone() { nix shell nixpkgs#rclone -c rclone "$@"; }
common=(--checksum --transfers 32 --checkers 32 --fast-list --stats-one-line --stats 30s)
rclone copy "$dir" "icons:$bucket" "${common[@]}" --exclude manifest.json --exclude '_fallback.png'
rclone copy "$dir" "icons:$bucket" "${common[@]}" --include '_fallback.png'
rclone copyto "$dir/manifest.json" "icons:$bucket/manifest.json" --checksum
rclone sync "$dir" "icons:$bucket" "${common[@]}"
echo "Published $(python3 -c 'import json,sys; print(json.load(open(sys.argv[1]))["count"])' "$dir/manifest.json") icons to $bucket"
