# Term Project – E-commerce Web App (Laravel + PHP + Docker/Sail)

## สแต็กที่ใช้
- Laravel (PHP)
- MySQL
- Docker + Laravel Sail (ทุกคนได้ environment เดียวกัน ไม่ต้องลง PHP/MySQL เองในเครื่อง)
- Git + GitHub สำหรับทำงานร่วมกัน

สิ่งที่ทุกคนต้องลงในเครื่องตัวเองมีแค่ **Docker Desktop** และ **Git** เท่านั้น ไม่ต้องลง PHP หรือ MySQL เอง

---

## ขั้นตอนเริ่มต้น (ทำครั้งเดียว โดยคนที่ 1 คนในกลุ่ม)

1. ติดตั้ง Laravel ใหม่ (ใช้ Docker รัน Composer โดยไม่ต้องมี PHP ในเครื่อง):
   ```bash
   docker run --rm -v $(pwd):/app composer create-project laravel/laravel ecommerce-app
   cd ecommerce-app
   ```
2. เพิ่ม Laravel Sail และติดตั้ง Docker setup:
   ```bash
   docker run --rm -v $(pwd):/var/www/html -w /var/www/html \
     laravelsail/php84-composer:latest \
     composer require laravel/sail --dev

   docker run --rm -v $(pwd):/var/www/html -w /var/www/html \
     laravelsail/php84-composer:latest \
     php artisan sail:install --with=mysql
   ```
   ขั้นตอนนี้จะสร้างไฟล์ `docker-compose.yml` ให้อัตโนมัติ (ไม่ต้องเขียนเอง)
3. คัดลอกไฟล์ migration จากโฟลเดอร์ `database/migrations` ที่แนบมานี้ ไปวางทับ/เพิ่มใน
   `ecommerce-app/database/migrations/`
4. แก้ `.env` ตามไฟล์ `.env.example` ที่แนบมา (ค่า DB ให้ตรงกับที่ Sail ใช้)
5. เปิดใช้งานและรัน migration:
   ```bash
   ./vendor/bin/sail up -d
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate
   ```
6. สร้าง repo บน GitHub แล้ว push โค้ดเริ่มต้นขึ้นไป:
   ```bash
   git init
   git add .
   git commit -m "Initial Laravel + Sail setup + schema migrations"
   git branch -M main
   git remote add origin <URL_REPO_ของคุณ>
   git push -u origin main
   ```
7. เชิญเพื่อนอีก 4 คนเป็น collaborator ใน repo (Settings > Collaborators)

---

## ขั้นตอนสำหรับสมาชิกทุกคน (เมื่อ repo พร้อมแล้ว)

ติดตั้งแค่ **Docker Desktop** และ **Git** ในเครื่อง ไม่ต้องลง PHP/MySQL เอง

```bash
git clone <URL_REPO>
cd ecommerce-app

# ติดตั้ง dependency ผ่าน Docker (ไม่ต้องมี PHP ในเครื่อง)
docker run --rm -v $(pwd):/var/www/html -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs

cp .env.example .env

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

เปิดเบราว์เซอร์ที่ `http://localhost`

คำสั่งที่ใช้บ่อย (รันแทน `php artisan` / `composer` ตัวเดิม):
```bash
./vendor/bin/sail up -d          # เปิด container (ทำทุกครั้งที่เริ่มทำงาน)
./vendor/bin/sail down           # ปิด container
./vendor/bin/sail artisan ...    # แทน php artisan
./vendor/bin/sail composer ...   # แทน composer
./vendor/bin/sail npm ...        # แทน npm
```

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