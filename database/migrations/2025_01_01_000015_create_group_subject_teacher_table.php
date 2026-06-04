<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guruh → Yo'nalish bog'liq jadvali (o'qituvchi bu yerda yo'q — u teacher_directions orqali biriktiriladi)
        Schema::create('group_direction_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('direction_id')->constrained()->onDelete('cascade');
            $table->unique(['group_id', 'direction_id'], 'gd_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_direction_teacher');
    }
};
