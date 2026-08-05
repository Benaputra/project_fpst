<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->string('nim', 20)->primary();
            $table->string('nama');
            $table->foreignId('program_studi_id')
                ->constrained('program_studi')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('no_hp', 20);
            $table->string('tempat_lahir', 100);
            $table->date('tanggal_lahir');
            $table->unsignedSmallInteger('angkatan');
            $table->string('pembimbing_akademik_id', 20);
            $table->foreign('pembimbing_akademik_id')
                ->references('nidn')
                ->on('dosen')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
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
        Schema::dropIfExists('mahasiswa');
    }
};
