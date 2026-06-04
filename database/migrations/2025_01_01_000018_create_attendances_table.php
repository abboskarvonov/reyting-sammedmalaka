<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['on_time', 'late', 'excused', 'absent'])->default('absent');
            $table->time('check_in_time')->nullable();
            $table->time('expected_time')->nullable();
            $table->integer('late_minutes')->default(0);
            $table->text('reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['teacher_id', 'date']);
            $table->index(['teacher_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
