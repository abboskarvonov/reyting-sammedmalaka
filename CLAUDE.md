# CLAUDE.md — Staff Rating Loyihasi

## Loyiha haqida

Laravel 13 + Filament v5 da qurilgan **Staff Rating System** — xodimlarni baholash va monitoring tizimi.
Asosiy maqsad: o'qituvchilar faoliyatini topshiriq bajarish, davomat va dars sifati bo'yicha kompleks baholash.

## Texnologiyalar

- **Backend:** Laravel 13, PHP 8.4
- **Admin panel:** Filament v5 (Resource, Widget, Page, Importer)
- **Frontend (public/student):** Blade + Alpine.js + Tailwind CSS
- **Grafiklar (admin):** Filament ChartWidget
- **Grafiklar (ochiq):** Chart.js
- **DB:** MySQL 8.0
- **Cache:** Redis
- **Auth (admin):** Filament built-in auth (`/admin/login`)
- **Auth (API):** Laravel Sanctum
- **Auth (student):** Session (ID-kod)
- **QR:** `simplesoftwareio/simple-qrcode`
- **Excel:** `maatwebsite/excel`

## Foydalanuvchi rollari

| Rol       | Kirish        | Panel                         |
| --------- | ------------- | ----------------------------- |
| `admin`   | Email + parol | `/admin` (Filament)           |
| `teacher` | Email + parol | `/teacher/dashboard` (Blade)  |
| `student` | ID-kod        | `/student/dashboard` (Blade)  |
| Guest     | —             | `/` (ochiq statistika, Blade) |

## Asosiy buyruqlar

```bash
# Development
php artisan serve
npm run dev

# Filament
php artisan make:filament-user          # admin yaratish
php artisan make:filament-resource Teacher --generate   # resource
php artisan make:filament-widget StatsOverview --stats  # widget

# Ma'lumotlar bazasi
php artisan migrate:fresh --seed

# Cache tozalash (development)
php artisan optimize:clear

# Queue
php artisan queue:work

# Test
php artisan test
```

## Muhim konfiguratsiyalar

```dotenv
# .env da o'quv yili va semestrni belgilash (har semestr yangilanadi)
ACADEMIC_YEAR=2024-2025
SEMESTER=1
```

## Papka tuzilmasi (asosiy)

```
app/Filament/                  ← Filament v5 admin panel
  Resources/
    TeacherResource.php        ← O'qituvchilar (CRUD + reyting)
    StudentResource.php        ← Tinglovchilar (CRUD + import)
    SubjectResource.php        ← Fanlar
    GroupResource.php          ← Guruhlar + fan biriktirish
    TaskResource.php           ← Topshiriqlar
    AttendanceResource.php     ← Davomat
    RatingResource.php         ← Baholash natijalari (read-only)
  Widgets/
    StatsOverviewWidget.php
    TeacherRankingWidget.php
    TaskStatsWidget.php
    AttendanceChartWidget.php
  Pages/
    Dashboard.php
    BulkAttendance.php         ← Ommaviy davomat kiritish
  Imports/
    StudentImporter.php        ← Filament native importer
  Exports/
    RatingExporter.php

app/Http/Controllers/
  Auth/          ← StudentAuthController (ID-kod login)
  Teacher/       ← TeacherDashboard, TeacherQr
  Student/       ← StudentDashboard, StudentRating
  Public/        ← PublicStatsController (ochiq statistika)
  Api/           ← REST API (Sanctum)

app/Models/      ← 11 ta model
app/Services/    ← QrCodeService, AttendanceService

resources/views/
  teacher/       ← O'qituvchi panel (Blade)
  student/       ← Baholash sahifasi (Blade)
  public/        ← Ochiq statistika (Blade)
  filament/      ← Filament custom views

docs/            ← To'liq texnik dokumentatsiya (01–11.md)
```

## Dokumentatsiya

- `01-reja.md` — Loyiha rejasi va arxitektura
- `02-migratsiyalar.md` — 13 ta jadval (migratsiya kodi + ERD)
- `03-modellar.md` — Modellar va munosabatlar
- `04-autentifikatsiya.md` — Auth va routes (student + teacher)
- `05-admin-panel.md` — **Filament v5** Resources, Widgets, Pages
- `06-tinglovchi-baholash.md` — Baholash oqimi (Blade)
- `07-ochiq-statistika.md` — Ochiq statistika (Blade + Chart.js)
- `08-qr-kod.md` — QR-kod tizimi
- `09-excel-import.md` — Excel import/export
- `10-api.md` — REST API endpointlar
- `11-deployment.md` — Deploy va server sozlamalari

## Muhim qoidalar (biznes logika)

1. Tinglovchi bir fan — bir o'qituvchi uchun **faqat bir marta** baholaydi
2. Baholashda tinglovchi **ID-kodi** bilan tasdiqlanadi (parol yo'q)
3. QR-kod skanerlanganda → tinglovchi login sahifasiga yo'naltiriladi
4. Davomat faqat **admin** Filament panel orqali kiritadi
5. Topshiriqlarni faqat **admin** yaratadi va tayinlaydi
6. Ochiq statistikada individual tinglovchi ma'lumotlari ko'rsatilmaydi
7. O'qituvchi arxivlanganda uning QR kodi ishlamay qoladi

## Keng tarqalgan xatolar

```bash
# Filament cache muammosi
php artisan filament:cache-components

# QR kod generatsiya xatosi (GD extension)
sudo apt install php8.4-gd && sudo service php8.4-fpm restart

# Excel import xatosi (memory)
# php.ini: memory_limit = 256M

# Migration xatosi - foreign key tartib
php artisan migrate:fresh --seed
```
