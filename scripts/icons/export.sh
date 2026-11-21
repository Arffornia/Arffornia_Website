#!/usr/bin/env bash
# Renders every item of an installed Arffornia pack, named by item id, into OUT_DIR:
#   <size>/<namespace>/<path>.png for 36 and 128 px, plus manifest.json.
# Usage: scripts/icons/export.sh <pack-client-dir> <out-dir>
#   <pack-client-dir>: a launcher install (holds mods/, config/...). It is only read: the client
#   runs on a throwaway copy. Its mods/arffornia.jar must have the icon exporter (Mods#74 or later);
#   ARFFORNIA_JAR=<path> overrides it.
# Needs a display (DISPLAY), Java 21 and Nix (portablemc and the X/audio libraries LWJGL loads).
set -euo pipefail

if [[ $# -ne 2 ]]; then
  echo "usage: $0 <pack-client-dir> <out-dir>" >&2
  exit 2
fi
pack=$(realpath "$1")
out=$(realpath -m "$2")
[[ -d "$pack/mods" ]] || { echo "no mods/ in $pack" >&2; exit 1; }

cache="${XDG_CACHE_HOME:-$HOME/.cache}/arffornia/icon-export"
work=$(mktemp -d "${TMPDIR:-/tmp}/arffornia-icons.XXXXXX")
trap 'rm -rf "$work"' EXIT
mkdir -p "$cache/mc" "$out" "$work/client"

for dir in mods config defaultconfigs kubejs; do
  [[ -d "$pack/$dir" ]] && cp -a "$pack/$dir" "$work/client/"
done
[[ -f "$pack/options.txt" ]] && cp "$pack/options.txt" "$work/client/"
# A browser screen mod would start a Chromium for nothing.
rm -f "$work/client/mods"/webgui-*.jar
if [[ -n "${ARFFORNIA_JAR:-}" ]]; then
  cp "$ARFFORNIA_JAR" "$work/client/mods/arffornia.jar"
fi
if ! unzip -Z1 "$work/client/mods/arffornia.jar" | grep -c 'client/icon/ItemIconExport.class' > /dev/null; then
  echo "mods/arffornia.jar has no icon exporter: set ARFFORNIA_JAR to a newer build" >&2
  exit 1
fi

libs=/run/opengl-driver/lib
for pkg in openal libpulseaudio libx11 libxcursor libxinerama libxi libxrender \
  libxtst libxxf86vm libxext; do
  libs="$libs:$(nix build --no-link --print-out-paths "nixpkgs#$pkg" | tail -1)/lib"
done
java_bin=${JAVA_BIN:-$(command -v java)}

echo "Exporting icons from $pack to $out"
rm -f "$out/manifest.json"
LD_LIBRARY_PATH="$libs" DISPLAY="${DISPLAY:-:0}" nix shell nixpkgs#portablemc -c \
  portablemc --main-dir "$cache/mc" start neoforge::21.1.172 \
  --mc-dir "$work/client" --jvm "$java_bin" \
  --jvm-arg=-Xmx8G --jvm-arg="-Darffornia.exportItemIcons=$out" \
  --resolution 854x480 -u IconExport > "$work/run.log" 2>&1 || true

if [[ ! -f "$out/manifest.json" ]]; then
  echo "export failed, no manifest; client log tail:" >&2
  tail -n 40 "$work/client/logs/latest.log" >&2 || tail -n 40 "$work/run.log" >&2
  exit 1
fi
python3 - "$out/manifest.json" <<'EOF'
import json, sys
m = json.load(open(sys.argv[1]))
print(f"{m['count']} icons, {len(m['failed'])} failed to render, sizes {m['sizes']}")
EOF
