<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_skripsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_skripsi_id')->constrained('pengajuan_skripsi')->cascadeOnDelete();

            // Berkas Persyaratan Mahasiswa
            $table->string('file_naskah_seminar')->nullable();
            $table->string('file_acc_pembimbing')->nullable();
            $table->string('file_bukti_bayar_seminar')->nullable();
            $table->string('file_toefl')->nullable();

            // Penetapan Penguji oleh Kaprodi
            $table->foreignId('penguji_seminar_id')->nullable()->constrained('users')->nullOnDelete();

            // Penjadwalan oleh Admin
            $table->date('tgl_seminar')->nullable();
            $table->string('jam_seminar', 20)->nullable();
            $table->string('ruangan')->nullable();

            // Dokumen Undangan & SK oleh Admin
            $table->string('nomor_undangan_seminar')->nullable();
            $table->string('file_undangan_seminar')->nullable();
            $table->string('nomor_sk_seminar')->nullable();
            $table->string('file_sk_seminar')->nullable();

            // Hasil & Status Universal
            $table->decimal('nilai_seminar', 5, 2)->nullable();
            $table->string('status')->default('diajukan'); // diajukan, diproses, selesai, ditolak
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_skripsi');
    }
};
