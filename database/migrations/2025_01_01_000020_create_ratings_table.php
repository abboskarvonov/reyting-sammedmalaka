<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('direction_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('academic_year');
            $table->enum('semester', ['1', '2'])->default('1');
            $table->decimal('total_score', 5, 2)->default(0);
            $table->text('comment')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->unique(
                ['teacher_id', 'direction_id', 'student_id', 'academic_year', 'semester'],
                'ratings_unique'
            );
            $table->index(['teacher_id', 'direction_id']);
            $table->index(['academic_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
