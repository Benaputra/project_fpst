<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_judul', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 20)->unique();
            $table->text('judul');
            $table->string('status', 32)->default('diajukan');
            $table->text('catatan_reject')->nullable();
            $table->string('diverifikasi_oleh', 20)->nullable();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->timestamps();

            $table->foreign('nim')
                ->references('nim')
                ->on('mahasiswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('diverifikasi_oleh')
                ->references('nidn')
                ->on('dosen')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_judul');
    }
};
