<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->index();
            $table->string('jenis_surat'); // sk_bimbingan, undangan_seminar, sk_seminar, undangan_sidang, sk_sidang
            $table->string('nama_surat');
            $table->foreignId('pengajuan_skripsi_id')->constrained('pengajuan_skripsi')->cascadeOnDelete();
            $table->foreignId('seminar_skripsi_id')->nullable()->constrained('seminar_skripsi')->cascadeOnDelete();
            $table->foreignId('sidang_skripsi_id')->nullable()->constrained('sidang_skripsi')->cascadeOnDelete();
            $table->foreignId('program_studi_id')->constrained('program_studi')->cascadeOnDelete();
            $table->date('tgl_surat')->nullable();
            $table->string('file_surat')->nullable();
            $table->unsignedInteger('versi')->default(1);
            $table->string('status')->default('aktif'); // aktif, diperbarui
            $table->foreignId('diterbitkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};
