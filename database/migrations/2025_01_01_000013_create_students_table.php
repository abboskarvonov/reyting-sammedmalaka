<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();                 // ID-kod
            $table->string('full_name');                            // Ism-familiya
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->string('phone')->nullable();                    // Telefon
            $table->string('muassasa_nomi')->nullable();            // Muassasa nomi
            $table->string('diplom_raqam')->nullable();             // Diplom raqami
            $table->string('passport_seriya_raqam')->nullable();    // Pasport seriya/raqam
            $table->string('pinfl')->nullable();                    // PINFL (14 raqam)
            // O'qish sanasi guruh (groups.starts_at / ends_at) dan olinadi
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
