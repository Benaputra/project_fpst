<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penomoran_surat_counters', function (Blueprint $table) {
            $table->id();
            $table->string('kode_unit', 64);
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['kode_unit', 'tahun']);
        });

        Schema::create('pengajuan_surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('program_studi_id')->constrained('program_studi')->cascadeOnDelete();
            $table->string('jenis_surat', 64)->index();
            $table->string('perihal');
            $table->string('tujuan_instansi');
            $table->string('alamat_instansi')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->text('keterangan_kebutuhan');
            $table->string('file_pendukung')->nullable();
            $table->string('status', 32)->default('diajukan')->index();
            $table->text('catatan_penolakan')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_pada')->nullable();
            $table->foreignId('diterbitkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diterbitkan_pada')->nullable();
            $table->string('nomor_surat_terbit')->nullable();
            $table->string('file_surat_terbit')->nullable();
            $table->string('verification_token', 64)->nullable()->unique();
            $table->timestamps();
        });

        Schema::table('surat', function (Blueprint $table) {
            $table->dropForeign(['pengajuan_skripsi_id']);
        });

        Schema::table('surat', function (Blueprint $table) {
            $table->unsignedBigInteger('pengajuan_skripsi_id')->nullable()->change();
            $table->foreign('pengajuan_skripsi_id')
                ->references('id')
                ->on('pengajuan_skripsi')
                ->cascadeOnDelete();

            $table->foreignId('pengajuan_surat_id')
                ->nullable()
                ->after('sidang_skripsi_id')
                ->constrained('pengajuan_surat')
                ->cascadeOnDelete();

            $table->string('verification_token', 64)->nullable()->unique()->after('status');
            $table->string('verification_hash', 64)->nullable()->after('verification_token');
            $table->foreignId('penanda_tangan_id')->nullable()->after('diterbitkan_oleh')->constrained('users')->nullOnDelete();
            $table->string('jabatan_penanda_tangan')->nullable()->after('penanda_tangan_id');
        });
    }

    public function down(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            $table->dropForeign(['pengajuan_surat_id']);
            $table->dropForeign(['penanda_tangan_id']);
            $table->dropColumn([
                'pengajuan_surat_id',
                'verification_token',
                'verification_hash',
                'penanda_tangan_id',
                'jabatan_penanda_tangan',
            ]);
        });

        Schema::dropIfExists('pengajuan_surat');
        Schema::dropIfExists('penomoran_surat_counters');
    }
};
