# 02 — Ma'lumotlar Bazasi Migratsiyalari

## Jadvallar ro'yxati

```
users                  ← tizim foydalanuvchilari (admin, teacher)
teachers               ← o'qituvchilar profili
students               ← tinglovchilar (ID-kod bilan)
groups                 ← guruhlar
subjects               ← fanlar / yo'nalishlar
teacher_subjects       ← o'qituvchi ↔ fan (pivot)
group_subject_teacher  ← guruh + fan + o'qituvchi uchlik aloqasi
tasks                  ← topshiriqlar
task_assignments       ← topshiriq ↔ xodim (bajarish holati)
attendances            ← davomat yozuvlari
rating_questions       ← baholash savollari/mezonlari
ratings                ← tinglovchi baholash sessiyasi
rating_answers         ← har bir savolga berilgan ball
```

---

## 1. `users` jadvali

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->enum('role', ['admin', 'teacher'])->default('teacher');
    $table->boolean('is_active')->default(true);
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
```

---

## 2. `teachers` jadvali

```php
Schema::create('teachers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('employee_id')->unique();       // xodim ID-kodi
    $table->string('phone')->nullable();
    $table->string('position')->nullable();         // lavozim
    $table->string('department')->nullable();       // bo'lim
    $table->string('photo')->nullable();
    $table->string('qr_token')->unique();           // QR-kod uchun token
    $table->boolean('is_archived')->default(false);
    $table->timestamps();
});
```

---

## 3. `students` jadvali

```php
Schema::create('students', function (Blueprint $table) {
    $table->id();
    $table->string('student_id')->unique();         // tinglovchi ID-kodi (baholashda ishlatiladi)
    $table->string('full_name');
    $table->foreignId('group_id')->constrained()->onDelete('cascade');
    $table->string('phone')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

---

## 4. `groups` jadvali

```php
Schema::create('groups', function (Blueprint $table) {
    $table->id();
    $table->string('name');                         // "Guruh A-101"
    $table->string('code')->unique();               // qisqa kod
    $table->integer('year')->nullable();            // o'quv yili
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

---

## 5. `subjects` jadvali

```php
Schema::create('subjects', function (Blueprint $table) {
    $table->id();
    $table->string('name');                         // fan nomi
    $table->string('code')->unique();               // fan kodi
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

---

## 6. `teacher_subjects` pivot jadvali

```php
Schema::create('teacher_subjects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
    $table->foreignId('subject_id')->constrained()->onDelete('cascade');
    $table->unique(['teacher_id', 'subject_id']);
    $table->timestamps();
});
```

---

## 7. `group_subject_teacher` jadvali

```php
// Qaysi guruh, qaysi fanni, qaysi o'qituvchidan o'qiydi
Schema::create('group_subject_teacher', function (Blueprint $table) {
    $table->id();
    $table->foreignId('group_id')->constrained()->onDelete('cascade');
    $table->foreignId('subject_id')->constrained()->onDelete('cascade');
    $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
    $table->string('academic_year');               // "2024-2025"
    $table->enum('semester', ['1', '2'])->default('1');
    $table->unique(['group_id', 'subject_id', 'teacher_id', 'academic_year', 'semester']);
    $table->timestamps();
});
```

---

## 8. `tasks` jadvali

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
    $table->date('due_date')->nullable();
    $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

---

## 9. `task_assignments` jadvali

```php
Schema::create('task_assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('task_id')->constrained()->onDelete('cascade');
    $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
    $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');
    $table->integer('completion_percent')->default(0);  // 0-100
    $table->text('note')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    $table->unique(['task_id', 'teacher_id']);
});
```

---

## 10. `attendances` jadvali

```php
Schema::create('attendances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
    $table->date('date');
    $table->enum('status', ['on_time', 'late', 'excused', 'absent'])->default('absent');
    // on_time = o'z vaqtida, late = kechikdi, excused = sababli kelmadi, absent = sababsiz
    $table->time('check_in_time')->nullable();
    $table->time('expected_time')->nullable();      // kelishi kerak bo'lgan vaqt
    $table->integer('late_minutes')->default(0);
    $table->text('reason')->nullable();             // sababli yo'qligi uchun sabab
    $table->foreignId('recorded_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->unique(['teacher_id', 'date']);
});
```

---

## 11. `rating_questions` jadvali

```php
Schema::create('rating_questions', function (Blueprint $table) {
    $table->id();
    $table->string('question');
    $table->integer('max_score')->default(5);       // 1-5 yoki 1-10
    $table->integer('order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### Boshlang'ich savollar (Seeder):

| # | Savol | Max ball |
|---|-------|----------|
| 1 | O'qituvchi mavzuni tushunarli tushuntira oladimi? | 5 |
| 2 | O'qituvchi darsga tayyorgarlik ko'rganmi? | 5 |
| 3 | O'qituvchi tinglovchilarga hurmat bilan muomala qiladimi? | 5 |
| 4 | Dars qiziqarli va interaktiv o'tdimi? | 5 |
| 5 | O'qituvchi savollaringizga aniq javob bera oladimi? | 5 |

---

## 12. `ratings` jadvali (baholash sessiyasi)

```php
Schema::create('ratings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
    $table->foreignId('subject_id')->constrained()->onDelete('cascade');
    $table->foreignId('student_id')->constrained()->onDelete('cascade');
    $table->string('academic_year');
    $table->enum('semester', ['1', '2'])->default('1');
    $table->decimal('total_score', 5, 2)->default(0); // hisoblangan umumiy ball
    $table->text('comment')->nullable();
    $table->string('ip_address')->nullable();
    $table->timestamps();
    // Bir tinglovchi bir fan uchun bir marta baholaydi
    $table->unique(['teacher_id', 'subject_id', 'student_id', 'academic_year', 'semester']);
});
```

---

## 13. `rating_answers` jadvali

```php
Schema::create('rating_answers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rating_id')->constrained()->onDelete('cascade');
    $table->foreignId('question_id')->constrained('rating_questions')->onDelete('cascade');
    $table->integer('score');                       // berilgan ball
    $table->timestamps();
    $table->unique(['rating_id', 'question_id']);
});
```

---

## Migratsiyalarni ishga tushirish tartibi

```bash
# Barcha migratsiyalarni ishga tushirish
php artisan migrate

# Boshlang'ich ma'lumotlarni yuklash
php artisan db:seed

# Qaytarish (rollback)
php artisan migrate:rollback

# Yangilash (development uchun)
php artisan migrate:fresh --seed
```

---

## Indekslar (performance uchun)

```php
// attendances jadvalida tezkor qidiruv uchun
$table->index(['teacher_id', 'date']);
$table->index(['date', 'status']);

// ratings jadvalida
$table->index(['teacher_id', 'subject_id']);
$table->index(['academic_year', 'semester']);

// task_assignments jadvalida
$table->index(['teacher_id', 'status']);
```

---

## ERD (Entity Relationship Diagram)

```
users ──────────── teachers ──────────── teacher_subjects ──── subjects
                      │                                            │
                      │                                            │
              task_assignments ──── tasks              group_subject_teacher
                      │                                    │         │
                  attendances                           groups    teachers
                                                          │
                                                       students
                                                          │
                                                       ratings ──── rating_answers
                                                                        │
                                                                 rating_questions
```
