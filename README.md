# My Memories Flower Shop — WordPress showcase

เว็บตัวอย่างสำหรับร้าน My Memories Flower Shop ทำด้วย **WordPress + WooCommerce** เปิดดูได้ผ่าน **WordPress Playground** (WordPress ที่รันในเบราว์เซอร์) โดยยังไม่ต้องมี hosting หรือโดเมน

**เปิดดู:** https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Kimmy213/Flower-web/main/blueprint.json

ครั้งแรกจะโหลดราว 30–60 วินาที เพราะเบราว์เซอร์ต้องติดตั้ง WordPress, WooCommerce และข้อมูลตัวอย่างใหม่ทุกครั้งที่เปิด ถ้าแก้อะไรบนหน้านั้น จะหายเมื่อปิดแท็บ

## มีอะไรบ้าง

| ส่วน | รายละเอียด |
|---|---|
| หน้า (9 หน้าตาม brief ของร้าน) | Home, Event & Party, Event, Party, Our Customers, Client, Celebrity, Organization, Brand |
| สินค้า | หน้า All Collections + หมวด Fresh Flower / Artificial Flower / Fruit / Occasion (หมวดไม่นับเป็นหน้า) |
| ตัวกรอง | หมวด, หมวดย่อย, ประเภท (ช่อ กล่อง กระเช้า แจกัน พวงหรีด พวงมาลัย ชุดของขวัญ) และเรียงตามราคา |
| สั่งซื้อ | ปิดตะกร้า ใช้ปุ่ม "สั่งทาง LINE" (ส่งชื่อและราคาสินค้าไปในแชท) และลิงก์ LINE Shopping |
| Footer | ที่อยู่, เวลาเปิด, LINE, Instagram, LINE Shopping, Google Map, quick links |

รูปสินค้าและราคาเป็น **ข้อมูลตัวอย่าง** (รูปวาด) ทั้งหมด

## โครงสร้าง

```
blueprint.json          บอก Playground ให้ติดตั้ง WooCommerce + theme แล้วรัน demo/setup.php
theme/my-memories/      block theme
  functions.php         ข้อมูลร้าน (mm_shop), ปุ่ม LINE, ตัวกรอง, footer
  assets/theme.css      สไตล์ทั้งหมด
  demo/setup.php        สร้างหมวด สินค้า หน้า และเมนู
data/products.json      รายการสินค้าตัวอย่าง
tools/                  สร้างรูป placeholder และ zip theme
dist/my-memories.zip    theme ที่ Playground โหลดไปติดตั้ง
```

## แก้แล้วดูในเครื่อง

```bash
./tools/build.sh
npx @wp-playground/cli@3.1.0 server --port=9400 --blueprint=blueprint-local.json --mount="$PWD/theme/my-memories:/wordpress/wp-content/themes/my-memories"
```

เปิด http://127.0.0.1:9400 (แก้ PHP/CSS ใน `theme/` แล้ว refresh ได้เลย ส่วนข้อมูลใน `demo/` ต้องรันใหม่) ถ้าจะให้ลิงก์ Playground เห็นของใหม่ ต้องรัน `./tools/build.sh` แล้ว commit + push `dist/my-memories.zip`

## ข้อควรรู้

- attribute ประเภทสินค้าใช้ slug `arrangement` (ตัวกรองคือ `?filter_arrangement=box`) เพราะ WooCommerce จอง slug `type` ไว้
- header ห้ามใช้ `backdrop-filter` เพราะจะขังเมนูมือถือไว้ในกรอบ header
- CLI เวอร์ชันล่าสุดต้องใช้ Node 24 เครื่องนี้มี Node 22 จึง pin ไว้ที่ `@wp-playground/cli@3.1.0`
