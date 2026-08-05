<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dosen', function (Blueprint $table) {
            $table->string('nidn', 20)->primary();
            $table->string('nama');
            $table->string('nuptk', 30);
            $table->foreignId('program_studi_id')
                ->constrained('program_studi')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('no_hp', 20);
            $table->string('tempat_lahir', 100);
            $table->date('tanggal_lahir');
            $table->string('jabatan_fungsional', 100);
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosen');
    }
};
