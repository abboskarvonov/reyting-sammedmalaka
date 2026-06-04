<?php

namespace Database\Seeders;

use App\Models\Direction;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $directions = [
            ['name' => 'Kardiologiya',  'unique_key' => 'KRD'],
            ['name' => 'Jarrohlik',     'unique_key' => 'JRR'],
            ['name' => 'Pediatriya',    'unique_key' => 'PDT'],
            ['name' => 'Stomatologiya', 'unique_key' => 'STM'],
            ['name' => 'Nevrologiya',   'unique_key' => 'NVR'],
            ['name' => 'Ginekologiya',  'unique_key' => 'GNK'],
            ['name' => 'Labaratoriya',  'unique_key' => 'LBR'],
        ];

        foreach ($directions as $d) {
            Direction::updateOrCreate(['unique_key' => $d['unique_key']], $d);
        }
    }
}
