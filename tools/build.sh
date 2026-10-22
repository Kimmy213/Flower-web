#!/bin/bash
# รวม theme + ข้อมูลตัวอย่าง เป็น dist/my-memories.zip และสร้าง blueprint-local.json สำหรับรันในเครื่อง
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
THEME="$ROOT/theme/my-memories"
mkdir -p "$THEME/demo/images" "$ROOT/dist"
rm -f "$THEME/demo/images/"*.jpg
cp "$ROOT/images/"*.jpg "$THEME/demo/images/"
cp "$ROOT/data/products.json" "$THEME/demo/products.json"
rm -f "$ROOT/dist/my-memories.zip"
(cd "$ROOT/theme" && zip -qr -X "$ROOT/dist/my-memories.zip" my-memories -x '*.DS_Store')
python3 - "$ROOT" <<'PY'
import json, sys, hashlib
root = sys.argv[1]
bp = json.load(open(f"{root}/blueprint.json"))
# ใส่ ?v=<hash ของ zip> ให้ URL เปลี่ยนทุกครั้งที่ theme เปลี่ยน ไม่งั้นเบราว์เซอร์ใช้ zip เก่าจาก cache
ver = hashlib.sha256(open(f"{root}/dist/my-memories.zip", "rb").read()).hexdigest()[:10]
for s in bp["steps"]:
    if s.get("step") == "installTheme":
        s["themeData"]["url"] = s["themeData"]["url"].split("?")[0] + "?v=" + ver
json.dump(bp, open(f"{root}/blueprint.json", "w"), indent=2, ensure_ascii=False)
bp = json.load(open(f"{root}/blueprint.json"))
for i, s in enumerate(bp["steps"]):
    if s.get("step") == "installTheme":
        bp["steps"][i] = {"step": "activateTheme", "themeFolderName": "my-memories"}
json.dump(bp, open(f"{root}/blueprint-local.json", "w"), indent=2, ensure_ascii=False)
PY
echo "built $(du -h "$ROOT/dist/my-memories.zip" | cut -f1)"
