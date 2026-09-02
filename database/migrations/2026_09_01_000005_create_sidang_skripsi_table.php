<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidang_skripsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_skripsi_id')->constrained('pengajuan_skripsi')->cascadeOnDelete();

            // Berkas Persyaratan Mahasiswa
            $table->string('file_naskah_sidang')->nullable();
            $table->string('file_acc_sidang')->nullable();
            $table->string('file_bebas_revisi_seminar')->nullable();
            $table->string('file_bukti_bayar_sidang')->nullable();

            // Penetapan 2 Orang Penguji oleh Kaprodi
            $table->foreignId('penguji_1_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('penguji_2_id')->nullable()->constrained('users')->nullOnDelete();

            // Penjadwalan oleh Admin
            $table->date('tgl_sidang')->nullable();
            $table->string('jam_sidang', 20)->nullable();
            $table->string('ruangan')->nullable();

            // Dokumen Undangan & SK oleh Admin
            $table->string('nomor_undangan_sidang')->nullable();
            $table->string('file_undangan_sidang')->nullable();
            $table->string('nomor_sk_sidang')->nullable();
            $table->string('file_sk_sidang')->nullable();

            // Hasil & Status Universal
            $table->decimal('nilai_sidang', 5, 2)->nullable();
            $table->string('status')->default('diajukan'); // diajukan, diproses, selesai, ditolak
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidang_skripsi');
    }
};
