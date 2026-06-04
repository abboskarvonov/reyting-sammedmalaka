<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->text('note')->nullable();           // admin izohi
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['task_id', 'teacher_id']);
            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
    }
};
