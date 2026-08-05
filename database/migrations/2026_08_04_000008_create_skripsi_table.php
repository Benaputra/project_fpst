<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skripsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_judul_id')->unique();
            $table->string('nim', 20)->unique();
            $table->text('judul');
            $table->string('pembimbing1_id', 20)->nullable();
            $table->string('pembimbing2_id', 20)->nullable();
            $table->string('status', 40)->default('menunggu_kesediaan_pembimbing');
            $table->timestamps();

            $table->foreign('pengajuan_judul_id')
                ->references('id')
                ->on('pengajuan_judul')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('nim')
                ->references('nim')
                ->on('mahasiswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('pembimbing1_id')
                ->references('nidn')
                ->on('dosen')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('pembimbing2_id')
                ->references('nidn')
                ->on('dosen')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skripsi');
    }
};
