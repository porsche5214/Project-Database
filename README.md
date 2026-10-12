# Term Project – E-commerce Web App (Laravel + PHP + Docker/Sail)

## สแต็กที่ใช้
- Laravel (PHP)
- MySQL
- Docker + Laravel Sail (ทุกคนได้ environment เดียวกัน ไม่ต้องลง PHP/MySQL เองในเครื่อง)
- Git + GitHub สำหรับทำงานร่วมกัน

สิ่งที่ทุกคนต้องลงในเครื่องตัวเองมีแค่ **Docker Desktop** และ **Git** เท่านั้น ไม่ต้องลง PHP หรือ MySQL เอง

---

## ขั้นตอนเริ่มต้น
สร้างโปรเจ็ค Laravel + Sail และ push ขึ้น repo นี้แล้ว สมาชิกทุกคนทำตามหัวข้อถัดไปได้เลย

> **Windows:** Sail ต้องรันใน WSL2 (Ubuntu) ให้ clone repo ไว้ในโฟลเดอร์ของ WSL เช่น `~/Project-Database` แล้วรันทุกคำสั่งใน terminal ของ Ubuntu (Docker Desktop → Settings → Resources → WSL integration เปิด Ubuntu ไว้)

---

## ขั้นตอนสำหรับสมาชิกทุกคน (เมื่อ repo พร้อมแล้ว)

ติดตั้งแค่ **Docker Desktop** และ **Git** ในเครื่อง ไม่ต้องลง PHP/MySQL เอง

```bash
git clone <URL_REPO>
cd Project-Database

# ติดตั้ง dependency ผ่าน Docker (ไม่ต้องมี PHP ในเครื่อง)
docker run --rm -v $(pwd):/var/www/html -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs

cp .env.example .env

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed   # สร้างตาราง + ข้อมูลตัวอย่าง
```

เปิดเบราว์เซอร์ที่ `http://localhost` (ทดสอบ DB: `http://localhost/api/products`)
ถ้า port 80 ชนกับโปรแกรมอื่น ให้ใส่ `APP_PORT=8000` ใน `.env` แล้วเปิด `http://localhost:8000`

บัญชีตัวอย่างจาก seeder (รหัส `password123`): `admin@naemak.test` (admin), `somchai@naemak.test` (member)

คำสั่งที่ใช้บ่อย (รันแทน `php artisan` / `composer` ตัวเดิม):
```bash
./vendor/bin/sail up -d          # เปิด container (ทำทุกครั้งที่เริ่มทำงาน)
./vendor/bin/sail down           # ปิด container
./vendor/bin/sail artisan ...    # แทน php artisan
./vendor/bin/sail composer ...   # แทน composer
./vendor/bin/sail npm ...        # แทน npm
./vendor/bin/sail artisan migrate:fresh --seed   # ล้าง DB แล้วสร้างใหม่ (หลังมีคนแก้ migration)
```

---

## ดูตารางด้วย DBeaver
1. New Database Connection → MySQL
2. Host `localhost`, Port `3306`, Database `ecommerce_project`, Username `sail`, Password `password` (ตาม `.env`)
3. แท็บ Driver properties: ตั้ง `allowPublicKeyRetrieval` = `true` และ `useSSL` = `false` (ไม่งั้น MySQL 8 จะ error "Public Key Retrieval is not allowed")
4. Test Connection → Finish
5. ดู ER diagram อัตโนมัติ: คลิกขวาที่ database `naemak` → View Diagram (ใช้ประกอบรายงานได้)

ต้องเปิด `sail up -d` ไว้ก่อนเสมอ ถ้าในเครื่องมี MySQL ตัวอื่นใช้ port 3306 อยู่ ให้ตั้ง `FORWARD_DB_PORT=3307` ใน `.env` แล้วใช้ port 3307 ใน DBeaver

---

## Database schema
migration อยู่ใน `database/migrations/2026_10_10_*` หนึ่งไฟล์ต่อหนึ่งตาราง (14 ตาราง) model อยู่ใน `app/Models/`

- ใช้ตาราง `member` แทน `users` ของ Laravel (model `Member` ใช้กับ `Auth::attempt()` ได้เลย รหัสผ่านเก็บใน `password_hash`)
- รูปสินค้าแยกเป็นตาราง `product_image` (สินค้าหนึ่งชิ้นมีหลายรูป มี `is_main` บอกรูปหลัก) เพราะ DBML เดิมใส่ไว้ใน `product` ทำให้มี PK สองตัว
- `rating.score` มี CHECK 1–5
- ตารางที่ PK เป็น composite (`cart_item`, `order_item`, `wishlist`, `rating`) ให้ update/delete ผ่าน `where(...)->update()/delete()` เพราะ Eloquent ไม่รองรับ composite PK
- ช่วงพัฒนาแก้ไฟล์ migration เดิมได้ แต่ต้องแจ้งกลุ่มให้ทุกคนรัน `sail artisan migrate:fresh --seed` (ข้อมูลใน DB เครื่องตัวเองจะถูกล้าง)

---

## วิธีทำงานร่วมกันด้วย Git (สำคัญมาก ต้องทำตามเพื่อไม่ให้โค้ดชนกัน)

- ห้าม push ตรงเข้า `main` — ทุกคนสร้าง branch ของตัวเองก่อนเริ่มงาน:
  ```bash
  git checkout -b feature/ชื่องาน
  ```
  เช่น `feature/cart-checkout`, `feature/product-page`, `feature/reviews`
- เสร็จงานแล้ว push branch แล้วเปิด Pull Request มาที่ `main` ให้เพื่อนช่วยดูก่อน merge
- ดึงโค้ดล่าสุดจาก `main` บ่อย ๆ ก่อนเริ่มงานแต่ละวัน:
  ```bash
  git checkout main
  git pull
  ```
- ถ้ามีการแก้ schema (เพิ่ม/แก้ migration) ให้แจ้งในกลุ่มทันที เพราะกระทบทุกคน
- ไฟล์ `docker-compose.yml` และ `.env` ไม่ควรแก้โดยไม่แจ้งกลุ่ม เพราะกระทบการรันของทุกคน

---

## การแบ่งงาน 5 คน

| คน | หน้าที่ |
|---|---|
| 1 | Database Design Lead — ดูแล schema/migration ทั้งหมด, ER/EER diagram, Relational Mapping |
| 2 | Backend: Auth + Cart + Checkout |
| 3 | Backend: Products page + ฟีเจอร์เสริม (Rating/Reviews) |
| 4 | Frontend/UI — wireframe และหน้าเว็บ (Blade views) |
| 5 | Wish List feature + รวมโค้ด (integration/QA) + พรีเซนเทชัน |

---

## สิ่งที่ต้องส่งท้ายเทอม
1. Database schema (ER, EER, UML diagram)
2. Relational Mapping
3. Low-fidelity prototype
4. ไฟล์พรีเซนเทชัน
5. Source code
