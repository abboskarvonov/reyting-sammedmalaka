# Staff Rating System

**Xodimlarni baholash va monitoring tizimi** — Laravel 13 + Filament v5 da qurilgan kompleks platforma.

---

## Tizim imkoniyatlari

### 1. Topshiriqlarni bajarish monitoring

- Xodimlar reytingi — topshiriq bajarish foiziga qarab
- Har bir xodim: jami topshiriqlar, bajarilgan, muddati o'tgan
- Status: `pending` → `in_progress` → `completed` / `overdue`

### 2. Davomat statistikasi

- Yil / oy / hafta kesimida monitoring
- Holatlar: **o'z vaqtida** | **kechikdi** | **sababli** | **sababsiz**
- Grafik va jadval ko'rinishida hisobot

### 3. O'qituvchi dars sifatini baholash (QR-kod)

- Har bir o'qituvchida shaxsiy QR-kod
- Tinglovchi skanerlaydi → ID kiritadi → baholaydi
- 5 ta mezon bo'yicha 1–5 ball (bir marta, qayta bloklangan)
- O'qituvchi + fan kesimida chuqur tahlil

---

## Foydalanuvchi rollari

| Rol     | Kirish        | Panel                        |
| ------- | ------------- | ---------------------------- |
| Admin   | Email + Parol | `/admin` (Filament v5)       |
| Teacher | Email + Parol | `/teacher/dashboard` (Blade) |
| Student | ID-kod        | `/student/dashboard` (Blade) |
| Guest   | —             | `/` (ochiq statistika)       |

---

## Texnologiyalar

|             |                                  |
| ----------- | -------------------------------- |
| Backend     | Laravel 13 (PHP 8.4)             |
| Admin panel | Filament v5                      |
| Frontend    | Blade + Alpine.js + Tailwind CSS |
| DB          | MySQL 8.0                        |
| Cache       | Redis                            |
| QR kod      | simplesoftwareio/simple-qrcode   |
| Excel       | maatwebsite/excel                |

---

## O'rnatish

```bash
# 1. Clone va o'rnatish
git clone https://github.com/your-org/staff-rating.git
cd staff-rating
composer install
npm install && npm run build

# 2. Muhit sozlamalari
cp .env.example .env
php artisan key:generate
# .env ni to'ldiring (DB, ACADEMIC_YEAR, SEMESTER ...)

# 3. Filament o'rnatish
php artisan filament:install --panels

# 4. Migratsiya va seed
php artisan migrate --seed

# 5. Storage link
php artisan storage:link

# 6. Development server
php artisan serve
```

---

## Birinchi admin yaratish

```bash
php artisan make:filament-user
```

---

## Muhim `.env` sozlamalari

```dotenv
ACADEMIC_YEAR=2024-2025
SEMESTER=1
```

Bu ikki qiymat har semestr boshida yangilanadi.

---

## Dokumentatsiya

`docs/` papkasida to'liq texnik dokumentatsiya:

| Fayl                        | Tavsif                                     |
| --------------------------- | ------------------------------------------ |
| `01-reja.md`                | Loyiha rejasi, texnologiyalar, arxitektura |
| `02-migratsiyalar.md`       | Barcha DB jadvallar (13 ta)                |
| `03-modellar.md`            | Modellar va Eloquent munosabatlar          |
| `04-autentifikatsiya.md`    | Auth tizimi va routes                      |
| `05-admin-panel.md`         | **Filament v5** Resources, Widgets, Pages  |
| `06-tinglovchi-baholash.md` | Baholash sahifasi va oqimi                 |
| `07-ochiq-statistika.md`    | Ochiq statistika va grafiklar              |
| `08-qr-kod.md`              | QR-kod generatsiya va ishlatish            |
| `09-excel-import.md`        | Excel import/export                        |
| `10-api.md`                 | REST API endpointlar                       |
| `11-deployment.md`          | Deploy va server sozlamalari               |

---

## Litsenziya

MIT
