<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_skripsi', function (Blueprint $table) {
            $table->unsignedSmallInteger('kuota_penonton')->default(35)->after('ruangan');
            $table->unsignedSmallInteger('kuota_penyanggah')->default(2)->after('kuota_penonton');
            $table->boolean('pendaftaran_audiens_dibuka')->default(true)->after('kuota_penyanggah');
            $table->string('token_presensi', 48)->nullable()->unique()->after('pendaftaran_audiens_dibuka');

            $table->string('hasil_evaluasi', 32)->nullable()->after('nilai_seminar');
            $table->json('rincian_nilai')->nullable()->after('hasil_evaluasi');
            $table->text('catatan_revisi')->nullable()->after('catatan');
            $table->date('tenggat_revisi')->nullable()->after('catatan_revisi');
            $table->timestamp('bebas_revisi_at')->nullable()->after('tenggat_revisi');
            $table->foreignId('bebas_revisi_oleh')->nullable()->after('bebas_revisi_at')->constrained('users')->nullOnDelete();

            $table->index(['tgl_seminar', 'status']);
        });

        Schema::table('sidang_skripsi', function (Blueprint $table) {
            $table->string('hasil_evaluasi', 32)->nullable()->after('nilai_sidang');
            $table->string('predikat_yudisium', 64)->nullable()->after('hasil_evaluasi');
            $table->json('rincian_nilai')->nullable()->after('predikat_yudisium');
            $table->text('catatan_revisi')->nullable()->after('catatan');
            $table->date('tenggat_revisi')->nullable()->after('catatan_revisi');
            $table->timestamp('bebas_revisi_at')->nullable()->after('tenggat_revisi');
            $table->foreignId('bebas_revisi_oleh')->nullable()->after('bebas_revisi_at')->constrained('users')->nullOnDelete();

            $table->index(['tgl_sidang', 'status']);
        });

        Schema::create('seminar_audiens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_skripsi_id')->constrained('seminar_skripsi')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('users')->cascadeOnDelete();
            $table->string('peran', 24)->default('peserta');
            $table->string('status', 24)->default('terdaftar');
            $table->timestamp('waktu_daftar')->nullable();
            $table->timestamp('waktu_presensi')->nullable();
            $table->foreignId('divalidasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->unique(['seminar_skripsi_id', 'mahasiswa_id'], 'seminar_audiens_unique_sesi_mhs');
            $table->index(['mahasiswa_id', 'status', 'peran'], 'seminar_audiens_mhs_status_peran_idx');
            $table->index(['seminar_skripsi_id', 'status'], 'seminar_audiens_sesi_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_audiens');

        Schema::table('sidang_skripsi', function (Blueprint $table) {
            $table->dropIndex(['tgl_sidang', 'status']);
            $table->dropForeign(['bebas_revisi_oleh']);
            $table->dropColumn([
                'hasil_evaluasi',
                'predikat_yudisium',
                'rincian_nilai',
                'catatan_revisi',
                'tenggat_revisi',
                'bebas_revisi_at',
                'bebas_revisi_oleh',
            ]);
        });

        Schema::table('seminar_skripsi', function (Blueprint $table) {
            $table->dropIndex(['tgl_seminar', 'status']);
            $table->dropForeign(['bebas_revisi_oleh']);
            $table->dropColumn([
                'kuota_penonton',
                'kuota_penyanggah',
                'pendaftaran_audiens_dibuka',
                'token_presensi',
                'hasil_evaluasi',
                'rincian_nilai',
                'catatan_revisi',
                'tenggat_revisi',
                'bebas_revisi_at',
                'bebas_revisi_oleh',
            ]);
        });
    }
};
