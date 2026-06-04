<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin    = User::where('role', 'admin')->first();
        $teachers = Teacher::all();

        // Status probability per teacher (on_time, late, excused, absent)
        $profiles = [
            0 => [92, 5, 2, 1],   // Dilnoza - excellent
            1 => [85, 10, 3, 2],  // Jasur - good
            2 => [88, 7, 3, 2],   // Malika - good
            3 => [80, 12, 4, 4],  // Bobur - average
            4 => [78, 14, 4, 4],  // Sarvinoz - average
            5 => [72, 18, 5, 5],  // Ulugbek - below avg
            6 => [82, 10, 5, 3],  // Nilufar - good
        ];

        $start = Carbon::now()->subDays(30);
        $end   = Carbon::now()->subDay();

        foreach ($teachers as $idx => $teacher) {
            $profile    = $profiles[$idx] ?? [80, 12, 4, 4];
            $statuses   = $this->buildStatusPool($profile);
            $statusIdx  = 0;

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                // Skip weekends
                if ($date->isWeekend()) continue;

                $status = $statuses[$statusIdx % count($statuses)];
                $statusIdx++;

                [$checkIn, $lateMins] = $this->getCheckInTime($status);

                Attendance::updateOrInsert(
                    ['teacher_id' => $teacher->id, 'date' => $date->toDateString()],
                    [
                        'status'        => $status,
                        'check_in_time' => $checkIn,
                        'expected_time' => '09:00:00',
                        'late_minutes'  => $lateMins,
                        'reason'        => $status === 'excused' ? 'Kasallik ta\'tili' : null,
                        'recorded_by'   => $admin->id,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]
                );
            }
        }
    }

    private function buildStatusPool(array $weights): array
    {
        $pool = [];
        $labels = ['on_time', 'late', 'excused', 'absent'];
        foreach ($labels as $i => $label) {
            for ($j = 0; $j < $weights[$i]; $j++) {
                $pool[] = $label;
            }
        }
        shuffle($pool);
        return $pool;
    }

    private function getCheckInTime(string $status): array
    {
        return match ($status) {
            'on_time' => [sprintf('08:%02d:00', rand(45, 59)), 0],
            'late'    => [
                sprintf('09:%02d:00', rand(5, 45)),
                rand(5, 45),
            ],
            default   => [null, 0],
        };
    }
}
