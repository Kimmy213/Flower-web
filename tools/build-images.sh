#!/bin/bash
# สร้างรูป placeholder ทั้งหมดลง images/ (ต้องมี Google Chrome และ Python + Pillow)
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$ROOT/.build/images"
CHROME="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
rm -rf "$TMP" && mkdir -p "$TMP" "$ROOT/images"
node "$ROOT/tools/gen-images.mjs" "$TMP"
while read -r name w h; do
  "$CHROME" --headless=new --disable-gpu --hide-scrollbars --user-data-dir="$ROOT/.build/chrome" \
    --window-size="$w,$h" --screenshot="$TMP/$name.png" "file://$TMP/$name.html" >/dev/null 2>&1
done < "$TMP/jobs.txt"
python3 - "$TMP" "$ROOT/images" <<'EOF'
import sys, glob, os
from PIL import Image
src, dst = sys.argv[1], sys.argv[2]
for f in glob.glob(os.path.join(src, "*.png")):
    Image.open(f).convert("RGB").save(os.path.join(dst, os.path.basename(f)[:-4] + ".jpg"), quality=84, optimize=True)
print(len(os.listdir(dst)), "images")
EOF
