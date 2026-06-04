# 05 — Admin Panel (Filament v5)

## O'rnatish

```bash
composer require filament/filament:"^5.0"
php artisan filament:install --panels
```

Panel ID: `admin`, URL: `/admin`

```bash
# Admin foydalanuvchi yaratish
php artisan make:filament-user
```

---

## Panel sozlash

```php
// app/Providers/Filament/AdminPanelProvider.php
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(['primary' => Color::Blue])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([Dashboard::class])
            ->widgets([
                StatsOverviewWidget::class,
                TeacherRankingWidget::class,
                TaskStatsWidget::class,
                AttendanceChartWidget::class,
            ])
            ->middleware(['auth', 'verified'])
            ->authMiddleware([Authenticate::class])
            ->authGuard('web');
    }
}
```

---

## Resurslar (Resources)

### 1. TeacherResource

```php
// app/Filament/Resources/TeacherResource.php
class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = "O'qituvchilar";
    protected static ?string $modelLabel = "O'qituvchi";
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Shaxsiy ma\'lumotlar')->schema([
                TextInput::make('user.name')
                    ->label('Ism-familya')
                    ->required(),
                TextInput::make('user.email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(table: 'users', column: 'email', ignoreRecord: true),
                TextInput::make('user.password')
                    ->label('Parol')
                    ->password()
                    ->required(fn($context) => $context === 'create')
                    ->minLength(8)
                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn($state) => filled($state)),
            ])->columns(2),

            Section::make('Ish ma\'lumotlari')->schema([
                TextInput::make('employee_id')
                    ->label('Xodim ID-kodi')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('position')
                    ->label('Lavozim'),
                TextInput::make('department')
                    ->label("Bo'lim"),
                TextInput::make('phone')
                    ->label('Telefon')
                    ->tel(),
            ])->columns(2),

            Section::make('Fanlar')->schema([
                Select::make('subjects')
                    ->label('Biriktiriladigan fanlar')
                    ->relationship('subjects', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]),

            Section::make('Holat')->schema([
                Toggle::make('is_archived')
                    ->label('Arxivlangan')
                    ->default(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Ism-familya')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee_id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('department')
                    ->label("Bo'lim")
                    ->searchable(),
                TextColumn::make('subjects.name')
                    ->label('Fanlar')
                    ->badge()
                    ->separator(','),
                TextColumn::make('ratings_avg_total_score')
                    ->label('O\'rtacha ball')
                    ->avg('ratings', 'total_score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match(true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        default       => 'danger',
                    }),
                TextColumn::make('task_completion_rate')
                    ->label('Topshiriq %')
                    ->getStateUsing(fn($record) => $record->task_completion_rate . '%')
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_archived')
                    ->label('Arxiv')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('department')->label("Bo'lim"),
                TernaryFilter::make('is_archived')->label('Arxivlangan'),
                SelectFilter::make('subjects')
                    ->label('Fan')
                    ->relationship('subjects', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('archive')
                    ->label(fn($record) => $record->is_archived ? 'Arxivdan chiqarish' : 'Arxivlash')
                    ->icon('heroicon-o-archive-box')
                    ->action(fn($record) => $record->update(['is_archived' => !$record->is_archived]))
                    ->requiresConfirmation(),
                Action::make('qr')
                    ->label('QR-kod')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn($record) => route('teacher.qr.admin', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('ratings_avg_total_score', 'desc');
    }

    public static function getRelationManagers(): array
    {
        return [
            SubjectsRelationManager::class,
            TaskAssignmentsRelationManager::class,
            AttendancesRelationManager::class,
            RatingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'view'   => Pages\ViewTeacher::route('/{record}'),
            'edit'   => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }
}
```

---

### 2. StudentResource

```php
// app/Filament/Resources/StudentResource.php
class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Tinglovchilar';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('student_id')
                ->label('ID-kod')
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('full_name')
                ->label('Ism-familya')
                ->required(),
            Select::make('group_id')
                ->label('Guruh')
                ->relationship('group', 'name')
                ->required()
                ->searchable()
                ->preload(),
            TextInput::make('phone')
                ->label('Telefon')
                ->tel(),
            Toggle::make('is_active')
                ->label('Faol')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_id')->label('ID-kod')->searchable(),
                TextColumn::make('full_name')->label('Ism-familya')->searchable()->sortable(),
                TextColumn::make('group.name')->label('Guruh')->badge()->sortable(),
                TextColumn::make('phone')->label('Telefon'),
                IconColumn::make('is_active')->label('Faol')->boolean(),
                TextColumn::make('ratings_count')
                    ->label('Baholashlar')
                    ->counts('ratings'),
            ])
            ->filters([
                SelectFilter::make('group_id')
                    ->label('Guruh')
                    ->relationship('group', 'name'),
                TernaryFilter::make('is_active')->label('Faol'),
            ])
            ->headerActions([
                ImportAction::make()
                    ->label('Excel import')
                    ->importer(StudentImporter::class),
                ExportAction::make()
                    ->label('Excel export')
                    ->exporter(StudentExporter::class),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit'   => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
```

---

### 3. SubjectResource

```php
// app/Filament/Resources/SubjectResource.php
class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Fanlar';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label('Fan nomi')->required(),
            TextInput::make('code')->label('Kod')->required()->unique(ignoreRecord: true),
            Textarea::make('description')->label('Tavsif'),
            Toggle::make('is_active')->label('Faol')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Fan nomi')->searchable()->sortable(),
                TextColumn::make('code')->label('Kod')->badge(),
                TextColumn::make('teachers_count')->label("O'qituvchilar")->counts('teachers'),
                TextColumn::make('ratings_avg_total_score')
                    ->label('O\'rtacha ball')
                    ->avg('ratings', 'total_score')
                    ->numeric(decimalPlaces: 2),
                IconColumn::make('is_active')->label('Faol')->boolean(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit'   => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
```

---

### 4. GroupResource

```php
// app/Filament/Resources/GroupResource.php
class GroupResource extends Resource
{
    protected static ?string $model = Group::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationLabel = 'Guruhlar';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label('Guruh nomi')->required(),
            TextInput::make('code')->label('Kod')->required()->unique(ignoreRecord: true),
            TextInput::make('year')->label("O'quv yili")->numeric(),
            Toggle::make('is_active')->label('Faol')->default(true),

            // Guruhga fan + o'qituvchi biriktirish
            Section::make('Fan va O\'qituvchi biriktirish')->schema([
                Repeater::make('subjectTeachers')
                    ->label('')
                    ->schema([
                        Select::make('subject_id')
                            ->label('Fan')
                            ->options(Subject::active()->pluck('name', 'id'))
                            ->required(),
                        Select::make('teacher_id')
                            ->label("O'qituvchi")
                            ->options(Teacher::active()->with('user')->get()->pluck('user.name', 'id'))
                            ->required(),
                        TextInput::make('academic_year')
                            ->label("O'quv yili")
                            ->default(config('app.academic_year'))
                            ->required(),
                        Select::make('semester')
                            ->label('Semestr')
                            ->options(['1' => '1-semestr', '2' => '2-semestr'])
                            ->default('1')
                            ->required(),
                    ])
                    ->columns(4)
                    ->addActionLabel('+ Fan biriktirish'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Guruh')->searchable()->sortable(),
                TextColumn::make('code')->label('Kod')->badge(),
                TextColumn::make('year')->label('Yil'),
                TextColumn::make('students_count')->label('Tinglovchilar')->counts('students'),
                IconColumn::make('is_active')->label('Faol')->boolean(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->filters([TernaryFilter::make('is_active')->label('Faol')]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit'   => Pages\EditGroup::route('/{record}/edit'),
        ];
    }
}
```

---

### 5. TaskResource

```php
// app/Filament/Resources/TaskResource.php
class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Topshiriqlar';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->label('Topshiriq nomi')->required()->columnSpanFull(),
            RichEditor::make('description')->label('Tavsif')->columnSpanFull(),
            DatePicker::make('due_date')->label('Muddat'),
            Select::make('priority')
                ->label('Muhimlik')
                ->options(['low' => 'Past', 'medium' => "O'rta", 'high' => 'Yuqori'])
                ->default('medium')
                ->required(),

            Section::make('Tayinlash')->schema([
                Select::make('teachers')
                    ->label("O'qituvchilar")
                    ->relationship(
                        name: 'teachers',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn($query) => $query->with('user')
                    )
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->user->name)
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required(),
            ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Nomi')->searchable()->limit(40),
                BadgeColumn::make('priority')
                    ->label('Muhimlik')
                    ->colors([
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger'  => 'high',
                    ])
                    ->formatStateUsing(fn($state) => match($state) {
                        'low'    => 'Past',
                        'medium' => "O'rta",
                        'high'   => 'Yuqori',
                    }),
                TextColumn::make('due_date')->label('Muddat')->date('d.m.Y')->sortable(),
                TextColumn::make('completion_rate')
                    ->label('Bajarilish')
                    ->getStateUsing(fn($record) => $record->completion_rate . '%')
                    ->badge()
                    ->color(fn($state) => (int)$state >= 80 ? 'success' : ((int)$state >= 50 ? 'warning' : 'danger')),
                TextColumn::make('assignments_count')
                    ->label('Xodimlar')
                    ->counts('assignments'),
            ])
            ->filters([
                SelectFilter::make('priority')
                    ->label('Muhimlik')
                    ->options(['low' => 'Past', 'medium' => "O'rta", 'high' => 'Yuqori']),
                Filter::make('overdue')
                    ->label('Muddati o\'tgan')
                    ->query(fn($query) => $query->where('due_date', '<', now())),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelationManagers(): array
    {
        return [TaskAssignmentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view'   => Pages\ViewTask::route('/{record}'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
```

---

### 6. AttendanceResource

```php
// app/Filament/Resources/AttendanceResource.php
class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Davomat';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('teacher_id')
                ->label("O'qituvchi")
                ->relationship(
                    name: 'teacher',
                    titleAttribute: 'id',
                    modifyQueryUsing: fn($query) => $query->with('user')
                )
                ->getOptionLabelFromRecordUsing(fn($r) => $r->user->name)
                ->required()
                ->searchable(),
            DatePicker::make('date')->label('Sana')->required()->default(today()),
            Select::make('status')
                ->label('Holat')
                ->options([
                    'on_time' => "O'z vaqtida",
                    'late'    => 'Kechikdi',
                    'excused' => 'Sababli kelmadi',
                    'absent'  => 'Sababsiz kelmadi',
                ])
                ->required()
                ->reactive(),
            TimePicker::make('check_in_time')
                ->label('Kirish vaqti')
                ->visible(fn($get) => in_array($get('status'), ['on_time', 'late'])),
            TextInput::make('late_minutes')
                ->label('Kechikish (daqiqa)')
                ->numeric()
                ->visible(fn($get) => $get('status') === 'late'),
            Textarea::make('reason')
                ->label('Sabab')
                ->visible(fn($get) => $get('status') === 'excused'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teacher.user.name')->label("O'qituvchi")->searchable()->sortable(),
                TextColumn::make('date')->label('Sana')->date('d.m.Y')->sortable(),
                BadgeColumn::make('status')
                    ->label('Holat')
                    ->colors([
                        'success' => 'on_time',
                        'warning' => 'late',
                        'info'    => 'excused',
                        'danger'  => 'absent',
                    ])
                    ->formatStateUsing(fn($state) => match($state) {
                        'on_time' => "O'z vaqtida",
                        'late'    => 'Kechikdi',
                        'excused' => 'Sababli',
                        'absent'  => 'Sababsiz',
                    }),
                TextColumn::make('check_in_time')->label('Kirish vaqti'),
                TextColumn::make('late_minutes')->label('Kechikish (daq.)'),
            ])
            ->filters([
                SelectFilter::make('teacher_id')
                    ->label("O'qituvchi")
                    ->relationship('teacher.user', 'name'),
                SelectFilter::make('status')
                    ->label('Holat')
                    ->options([
                        'on_time' => "O'z vaqtida",
                        'late'    => 'Kechikdi',
                        'excused' => 'Sababli',
                        'absent'  => 'Sababsiz',
                    ]),
                Filter::make('date')
                    ->form([
                        DatePicker::make('from')->label('Dan'),
                        DatePicker::make('to')->label('Gacha'),
                    ])
                    ->query(fn($query, $data) =>
                        $query
                            ->when($data['from'], fn($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['to'],   fn($q) => $q->whereDate('date', '<=', $data['to']))
                    ),
            ])
            ->headerActions([
                Action::make('bulk_attendance')
                    ->label('Ommaviy kiritish')
                    ->icon('heroicon-o-table-cells')
                    ->url(route('filament.admin.pages.bulk-attendance')),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit'   => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
```

---

### 7. RatingResource (faqat ko'rish)

```php
// app/Filament/Resources/RatingResource.php
class RatingResource extends Resource
{
    protected static ?string $model = Rating::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationLabel = 'Baholash natijalari';
    protected static ?int $navigationSort = 7;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Sana')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('teacher.user.name')->label("O'qituvchi")->searchable(),
                TextColumn::make('subject.name')->label('Fan')->searchable(),
                TextColumn::make('student.group.name')->label('Guruh')->badge(),
                TextColumn::make('total_score')
                    ->label('Ball')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color(fn($state) => match(true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        default       => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('academic_year')->label("O'quv yili"),
                TextColumn::make('semester')->label('Semestr')->badge(),
            ])
            ->filters([
                SelectFilter::make('teacher_id')
                    ->label("O'qituvchi")
                    ->relationship('teacher.user', 'name'),
                SelectFilter::make('subject_id')
                    ->label('Fan')
                    ->relationship('subject', 'name'),
                SelectFilter::make('academic_year')
                    ->label("O'quv yili")
                    ->options(Rating::distinct()->pluck('academic_year', 'academic_year')),
                SelectFilter::make('semester')
                    ->label('Semestr')
                    ->options(['1' => '1-semestr', '2' => '2-semestr']),
            ])
            ->headerActions([
                ExportAction::make()->label('Excel export')->exporter(RatingExporter::class),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // Faqat ko'rish (create/edit yo'q)
    public static function canCreate(): bool  { return false; }
    public static function canEdit($record): bool { return false; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRatings::route('/'),
        ];
    }
}
```

---

## Widgets (Dashboard)

### StatsOverviewWidget

```php
// app/Filament/Widgets/StatsOverviewWidget.php
class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make("O'qituvchilar", Teacher::active()->count())
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),
            Stat::make('Tinglovchilar', Student::where('is_active', true)->count())
                ->icon('heroicon-o-users')
                ->color('info'),
            Stat::make('Baholashlar', Rating::count())
                ->icon('heroicon-o-star')
                ->color('success'),
            Stat::make('Topshiriqlar bajarilishi',
                TaskAssignment::count() > 0
                    ? round(TaskAssignment::where('status', 'completed')->count() / TaskAssignment::count() * 100) . '%'
                    : '0%'
            )
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning'),
        ];
    }
}
```

### TeacherRankingWidget

```php
// app/Filament/Widgets/TeacherRankingWidget.php
class TeacherRankingWidget extends TableWidget
{
    protected static ?string $heading = "O'qituvchilar Reytingi (Top 10)";
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Teacher::active()
                    ->with('user')
                    ->withAvg('ratings', 'total_score')
                    ->withCount('ratings')
                    ->having('ratings_avg_total_score', '>', 0)
                    ->orderByDesc('ratings_avg_total_score')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('user.name')->label('O\'qituvchi'),
                TextColumn::make('department')->label("Bo'lim"),
                TextColumn::make('ratings_avg_total_score')
                    ->label('Ball')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color(fn($state) => match(true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        default       => 'danger',
                    }),
                TextColumn::make('ratings_count')->label('Baholashlar'),
            ]);
    }
}
```

### AttendanceChartWidget

```php
// app/Filament/Widgets/AttendanceChartWidget.php
class AttendanceChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Bu oy davomati';

    protected function getData(): array
    {
        $stats = Attendance::whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'datasets' => [[
                'label' => 'Soni',
                'data'  => [
                    $stats['on_time'] ?? 0,
                    $stats['late']    ?? 0,
                    $stats['excused'] ?? 0,
                    $stats['absent']  ?? 0,
                ],
                'backgroundColor' => ['#22c55e', '#f59e0b', '#3b82f6', '#ef4444'],
            ]],
            'labels' => ["O'z vaqtida", 'Kechikdi', 'Sababli', 'Sababsiz'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
```

---

## Maxsus sahifalar (Pages)

### BulkAttendance — ommaviy davomat kiritish

```php
// app/Filament/Pages/BulkAttendance.php
class BulkAttendance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $title = 'Ommaviy Davomat Kiritish';
    protected static ?string $navigationLabel = 'Ommaviy davomat';
    protected static ?string $navigationGroup = 'Davomat';
    protected static string $view = 'filament.pages.bulk-attendance';

    public ?string $date = null;
    public array $attendances = [];

    public function mount(): void
    {
        $this->date = today()->toDateString();
        $this->loadTeachers();
    }

    public function loadTeachers(): void
    {
        $this->attendances = Teacher::active()
            ->with('user')
            ->get()
            ->map(fn($teacher) => [
                'teacher_id'    => $teacher->id,
                'name'          => $teacher->user->name,
                'status'        => 'on_time',
                'check_in_time' => null,
                'reason'        => null,
            ])
            ->toArray();
    }

    public function save(): void
    {
        foreach ($this->attendances as $data) {
            Attendance::updateOrCreate(
                ['teacher_id' => $data['teacher_id'], 'date' => $this->date],
                [
                    'status'        => $data['status'],
                    'check_in_time' => $data['check_in_time'],
                    'reason'        => $data['reason'],
                    'recorded_by'   => auth()->id(),
                ]
            );
        }

        Notification::make()
            ->title('Davomat saqlandi')
            ->success()
            ->send();
    }
}
```

---

## Importers (Filament v5 native import)

```php
// app/Filament/Imports/StudentImporter.php
class StudentImporter extends Importer
{
    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('student_id')->label('ID-kod')->requiredMapping()->rules(['required', 'unique:students,student_id']),
            ImportColumn::make('full_name')->label('Ism-familya')->requiredMapping()->rules(['required']),
            ImportColumn::make('phone')->label('Telefon'),
        ];
    }

    protected function beforeCreate(): void
    {
        $this->record->group_id = $this->options['group_id'];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('group_id')
                ->label('Guruh')
                ->options(Group::pluck('name', 'id'))
                ->required(),
        ];
    }
}
```

---

## Navigatsiya guruhlari

```php
// TeacherResource, StudentResource, GroupResource, SubjectResource
protected static ?string $navigationGroup = "Ta'lim";

// TaskResource
protected static ?string $navigationGroup = 'Topshiriqlar';

// AttendanceResource, BulkAttendance page
protected static ?string $navigationGroup = 'Davomat';

// RatingResource
protected static ?string $navigationGroup = 'Baholash';
```
