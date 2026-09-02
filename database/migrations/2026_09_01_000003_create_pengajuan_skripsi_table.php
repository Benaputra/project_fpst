<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_skripsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('program_studi_id')->constrained('program_studi')->cascadeOnDelete();
            $table->text('judul');
            $table->longText('abstrak')->nullable();
            
            // Berkas Persyaratan Mahasiswa
            $table->string('file_proposal')->nullable();
            $table->string('file_transkrip')->nullable();
            $table->string('file_bukti_bayar')->nullable();

            // Penetapan Pembimbing oleh Kaprodi
            $table->foreignId('pembimbing_1_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pembimbing_2_id')->nullable()->constrained('users')->nullOnDelete();

            // Dokumen SK Bimbingan oleh Admin
            $table->string('nomor_sk_bimbingan')->nullable();
            $table->date('tgl_sk_bimbingan')->nullable();
            $table->string('file_sk_bimbingan')->nullable();

            // Status Universal & Catatan
            $table->string('status')->default('diajukan'); // diajukan, diproses, selesai, ditolak
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_skripsi');
    }
};
