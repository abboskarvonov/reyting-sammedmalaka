# 03 — Modellar va Munosabatlar

## Model ro'yxati

```
App\Models\
  User
  Teacher
  Student
  Group
  Subject
  Task
  TaskAssignment
  Attendance
  RatingQuestion
  Rating
  RatingAnswer
```

---

## 1. User modeli

```php
// app/Models/User.php
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['is_active' => 'boolean'];

    // Munosabatlar
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    // Scope-lar
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', 'admin');
    }

    public function scopeTeachers(Builder $query): Builder
    {
        return $query->where('role', 'teacher');
    }

    // Helper
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }
}
```

---

## 2. Teacher modeli

```php
// app/Models/Teacher.php
class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'employee_id', 'phone', 'position',
        'department', 'photo', 'qr_token', 'is_archived'
    ];

    protected $casts = ['is_archived' => 'boolean'];

    // Munosabatlar
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects');
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments')
            ->withPivot(['status', 'completion_percent', 'note', 'completed_at'])
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_subject_teacher')
            ->withPivot(['subject_id', 'academic_year', 'semester'])
            ->withTimestamps();
    }

    // Computed attributes
    public function getTaskCompletionRateAttribute(): float
    {
        $total = $this->taskAssignments()->count();
        if ($total === 0) return 0;
        $completed = $this->taskAssignments()->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 1);
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->ratings()->avg('total_score') ?? 0, 2);
    }

    // Scope-lar
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeWithTaskRanking(Builder $query): Builder
    {
        return $query->withCount([
            'taskAssignments',
            'taskAssignments as completed_tasks_count' => fn($q) =>
                $q->where('status', 'completed')
        ]);
    }
}
```

---

## 3. Student modeli

```php
// app/Models/Student.php
class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['student_id', 'full_name', 'group_id', 'phone', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    // Tinglovchi qaysi fanlarda baholaganini tekshirish
    public function hasRated(int $teacherId, int $subjectId, string $year, string $semester): bool
    {
        return $this->ratings()
            ->where('teacher_id', $teacherId)
            ->where('subject_id', $subjectId)
            ->where('academic_year', $year)
            ->where('semester', $semester)
            ->exists();
    }

    // Tinglovchi o'z guruhida qaysi fanlarni o'qiydi
    public function availableSubjectsForRating(string $year, string $semester)
    {
        return GroupSubjectTeacher::where('group_id', $this->group_id)
            ->where('academic_year', $year)
            ->where('semester', $semester)
            ->with(['subject', 'teacher.user'])
            ->get()
            ->filter(fn($gst) => !$this->hasRated(
                $gst->teacher_id, $gst->subject_id, $year, $semester
            ));
    }
}
```

---

## 4. Group modeli

```php
// app/Models/Group.php
class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'year', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'group_subject_teacher')
            ->withPivot(['teacher_id', 'academic_year', 'semester'])
            ->withTimestamps();
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'group_subject_teacher')
            ->withPivot(['subject_id', 'academic_year', 'semester'])
            ->withTimestamps();
    }
}
```

---

## 5. Subject modeli

```php
// app/Models/Subject.php
class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subjects');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function getAverageScoreAttribute(): float
    {
        return round($this->ratings()->avg('total_score') ?? 0, 2);
    }
}
```

---

## 6. Task va TaskAssignment modellari

```php
// app/Models/Task.php
class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'description', 'created_by', 'due_date', 'priority', 'is_active'];

    protected $casts = [
        'due_date'  => 'date',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'task_assignments')
            ->withPivot(['status', 'completion_percent', 'note', 'completed_at'])
            ->withTimestamps();
    }

    public function getCompletionRateAttribute(): float
    {
        $total = $this->assignments()->count();
        if ($total === 0) return 0;
        $completed = $this->assignments()->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 1);
    }
}

// app/Models/TaskAssignment.php
class TaskAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id', 'teacher_id', 'status',
        'completion_percent', 'note', 'completed_at'
    ];

    protected $casts = ['completed_at' => 'datetime'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    // Status o'zgartirishda avtomatik vaqt belgilash
    protected static function booted(): void
    {
        static::updating(function (TaskAssignment $assignment) {
            if ($assignment->isDirty('status') && $assignment->status === 'completed') {
                $assignment->completed_at = now();
                $assignment->completion_percent = 100;
            }
        });
    }
}
```

---

## 7. Attendance modeli

```php
// app/Models/Attendance.php
class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id', 'date', 'status', 'check_in_time',
        'expected_time', 'late_minutes', 'reason', 'recorded_by'
    ];

    protected $casts = ['date' => 'date'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Statistika uchun scope-lar
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('date', $year);
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function scopeForWeek(Builder $query, Carbon $startOfWeek): Builder
    {
        return $query->whereBetween('date', [
            $startOfWeek->startOfWeek(),
            $startOfWeek->endOfWeek()
        ]);
    }

    // Statistika helper
    public static function getStats(int $teacherId, string $period = 'year', $date = null): array
    {
        $date = $date ?? now();
        $query = static::where('teacher_id', $teacherId);

        match ($period) {
            'year'  => $query->forYear($date->year),
            'month' => $query->forMonth($date->year, $date->month),
            'week'  => $query->forWeek($date),
        };

        return [
            'on_time' => $query->clone()->where('status', 'on_time')->count(),
            'late'    => $query->clone()->where('status', 'late')->count(),
            'excused' => $query->clone()->where('status', 'excused')->count(),
            'absent'  => $query->clone()->where('status', 'absent')->count(),
            'total'   => $query->count(),
        ];
    }
}
```

---

## 8. Rating va RatingAnswer modellari

```php
// app/Models/Rating.php
class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id', 'subject_id', 'student_id',
        'academic_year', 'semester', 'total_score', 'comment', 'ip_address'
    ];

    protected $casts = ['total_score' => 'decimal:2'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(RatingAnswer::class);
    }

    // Umumiy ballni hisoblash va saqlash
    public function recalculateTotalScore(): void
    {
        $this->total_score = $this->answers()->avg('score') ?? 0;
        $this->save();
    }
}

// app/Models/RatingAnswer.php
class RatingAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['rating_id', 'question_id', 'score'];

    public function rating(): BelongsTo
    {
        return $this->belongsTo(Rating::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(RatingQuestion::class, 'question_id');
    }
}
```

---

## Munosabatlar xaritasi

```
User
  └─ hasOne ──► Teacher
                  ├─ belongsToMany ──► Subject (teacher_subjects)
                  ├─ hasMany ──────► TaskAssignment ──► Task
                  ├─ hasMany ──────► Attendance
                  ├─ hasMany ──────► Rating
                  └─ belongsToMany ──► Group (group_subject_teacher)

Group
  └─ hasMany ──► Student
                  └─ hasMany ──► Rating
                                   └─ hasMany ──► RatingAnswer ──► RatingQuestion
```
