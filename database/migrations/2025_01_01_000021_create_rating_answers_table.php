<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rating_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained('rating_questions')->onDelete('cascade');
            $table->integer('score');
            $table->timestamps();
            $table->unique(['rating_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_answers');
    }
};
