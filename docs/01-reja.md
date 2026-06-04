# 01 — Loyiha Rejasi

## Loyiha nomi

**Staff Rating System** — Xodimlarni baholash va monitoring tizimi

---

## Maqsad

Xodimlar (ayniqsa o'qituvchilar) faoliyatini kompleks baholash: topshiriqlarni bajarish ko'rsatkichi, davomati va dars o'tish sifatini yagona platformada nazorat qilish.

---

## Asosiy modullar

| #   | Modul              | Tavsif                                                      |
| --- | ------------------ | ----------------------------------------------------------- |
| 1   | **Task Tracking**  | Xodimlar topshiriqlarini bajarish ko'rsatkichi va reytingi  |
| 2   | **Attendance**     | Davomatni yil/oy/hafta kesimida monitoring qilish           |
| 3   | **Teacher Rating** | QR-kod orqali tinglovchilar tomonidan o'qituvchini baholash |
| 4   | **Admin Panel**    | Barcha ma'lumotlarni boshqarish markazi                     |
| 5   | **Public Stats**   | Login talab qilinmaydigan ochiq statistika sahifasi         |

---

## Foydalanuvchi rollari

```
SuperAdmin
  └── Admin (Menejer)
        ├── Teacher (O'qituvchi)
        └── Student/Listener (Tinglovchi)
```

| Rol       | Kirish        | Imkoniyatlar                             |
| --------- | ------------- | ---------------------------------------- |
| `admin`   | Email + parol | Barcha panel, ma'lumot kiritish, hisobot |
| `teacher` | Email + parol | O'z topshiriqlari, davomati, QR-kod      |
| `student` | ID-kod        | Faqat baholash sahifasi                  |
| `guest`   | —             | Faqat ochiq statistika                   |

---

## Texnologiyalar

| Qatlam              | Texnologiya                        |
| ------------------- | ---------------------------------- |
| Backend             | Laravel 13 (PHP 8.4)               |
| Admin panel         | Filament v5 (Resource/Widget/Page) |
| Frontend (public)   | Blade + Alpine.js + Tailwind CSS   |
| Frontend (student)  | Blade + Alpine.js + Tailwind CSS   |
| Grafiklar (admin)   | Filament Charts Widget             |
| Grafiklar (ochiq)   | Chart.js                           |
| DB                  | MySQL 8.0                          |
| QR kod              | `simplesoftwareio/simple-qrcode`   |
| Excel import/export | `maatwebsite/excel`                |
| Auth (admin)        | Filament Auth (built-in)           |
| Auth (API)          | Laravel Sanctum                    |
| Auth (student)      | Session (ID-kod)                   |
| Cache               | Redis (statistika uchun)           |

---

## Loyiha tuzilmasi (papkalar)

```
app/
  Filament/                    ← Filament v5 admin panel
    Resources/
      TeacherResource.php
      StudentResource.php
      SubjectResource.php
      GroupResource.php
      TaskResource.php
      AttendanceResource.php
      RatingResource.php
    Resources/
      TeacherResource/
        Pages/
          ListTeachers.php
          CreateTeacher.php
          EditTeacher.php
          ViewTeacher.php       ← o'qituvchi profil + reyting
        RelationManagers/
          SubjectsRelationManager.php
          TasksRelationManager.php
      AttendanceResource/
        Pages/
          BulkAttendance.php    ← maxsus sahifa
    Widgets/
      StatsOverviewWidget.php   ← dashboard kartalar
      TeacherRankingWidget.php  ← reyting jadvali
      TaskStatsWidget.php       ← topshiriqlar holati
      AttendanceChartWidget.php ← davomat grafigi
    Pages/
      Dashboard.php

  Http/
    Controllers/
      Auth/           ← tinglovchi autentifikatsiya
      Public/         ← ochiq statistika
      Student/        ← baholash sahifasi
      Teacher/        ← o'qituvchi QR-kod
      Api/            ← REST API
    Middleware/
      EnsureStudent.php

  Models/
    User.php
    Teacher.php
    Student.php
    Group.php
    Subject.php
    Task.php
    TaskAssignment.php
    Attendance.php
    Rating.php
    RatingQuestion.php
    RatingAnswer.php

  Services/
    RatingService.php
    AttendanceService.php
    QrCodeService.php

  Exports/
  Imports/

resources/views/
  teacher/
  student/
  public/
  layouts/

docs/                 ← bu dokumentatsiya
```

---

## Bosqichma-bosqich ishlab chiqish rejasi

| Bosqich | Tavsif                            | Fayl                        |
| ------- | --------------------------------- | --------------------------- |
| 1       | Ma'lumotlar bazasi migratsiyalari | `02-migratsiyalar.md`       |
| 2       | Modellar va munosabatlar          | `03-modellar.md`            |
| 3       | Autentifikatsiya tizimi           | `04-autentifikatsiya.md`    |
| 4       | Admin panel                       | `05-admin-panel.md`         |
| 5       | Tinglovchi baholash sahifasi      | `06-tinglovchi-baholash.md` |
| 6       | Ochiq statistika sahifasi         | `07-ochiq-statistika.md`    |
| 7       | QR-kod tizimi                     | `08-qr-kod.md`              |
| 8       | Excel import/export               | `09-excel-import.md`        |
| 9       | API endpointlar                   | `10-api.md`                 |
| 10      | Deploy va muhit sozlamalari       | `11-deployment.md`          |

---

## Muhim qoidalar

- Har bir o'qituvchi faqat **bir marta** baholanadi (bir tinglovchi — bir fan — bir baho)
- Tinglovchi baholashda o'z **ID-kodi** bilan tasdiqlanadi
- QR-kod skanerlanganda tinglovchidan ID so'raladi
- Barcha baholash natijalari **anonim** ko'rsatiladi (faqat admin kimligini ko'radi)
- Davomatni faqat admin yoki mas'ul xodim kiritadi
- Topshiriqlarni admin tayinlaydi, xodim bajarilganligini belgilaydi
