<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // MODUL 2: KATALOG LOKASI PRAKTIK LAPANG & ALUR PL END-TO-END
        // ============================================================
        Schema::create('lokasi_pl', function (Blueprint $table) {
            $table->id();
            $table->string('nama_instansi');
            $table->string('sektor', 16)->default('AGT/AGB'); // AGT, AGB, atau AGT/AGB
            $table->string('alamat');
            $table->string('kabupaten_kota', 120)->nullable();
            $table->string('kontak_pic', 120)->nullable();
            $table->string('telepon_pic', 40)->nullable();
            $table->unsignedSmallInteger('kuota_periode')->default(10);
            $table->string('status_verifikasi', 32)->default('terverifikasi');
            $table->foreignId('diusulkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();

            $table->index(['status_verifikasi', 'sektor'], 'fpst_lokasi_pl_status_sektor_idx');
        });

        Schema::create('praktik_lapang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('program_studi_id')->constrained('program_studi');
            $table->foreignId('lokasi_pl_id')->constrained('lokasi_pl');
            $table->string('periode_akademik', 40);
            $table->unsignedSmallInteger('sks_ditempuh');
            $table->string('judul_kegiatan', 255);
            $table->string('file_transkrip_sks')->nullable();
            $table->foreignId('pengajuan_surat_id')->nullable()->constrained('pengajuan_surat')->nullOnDelete();
            $table->string('file_surat_balasan')->nullable();
            $table->foreignId('dpl_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nomor_sk_dpl', 100)->nullable();
            $table->string('file_sk_dpl')->nullable();
            $table->date('tgl_mulai')->nullable();
            $table->date('tgl_selesai')->nullable();
            $table->string('file_laporan_akhir')->nullable();
            $table->date('tgl_seminar_pl')->nullable();
            $table->string('jam_seminar_pl', 50)->nullable();
            $table->string('ruangan_seminar_pl', 100)->nullable();
            $table->decimal('nilai_pembimbing_lapang', 5, 2)->nullable();
            $table->decimal('nilai_dpl', 5, 2)->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->string('file_sertifikat')->nullable();
            $table->string('status', 32)->default('diajukan');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['mahasiswa_id', 'status'], 'fpst_pl_mhs_status_idx');
            $table->index(['program_studi_id', 'status'], 'fpst_pl_prodi_status_idx');
            $table->index(['lokasi_pl_id', 'periode_akademik', 'status'], 'fpst_pl_lokasi_periode_idx');
            $table->index(['dpl_id', 'status'], 'fpst_pl_dpl_status_idx');
            $table->index(['tgl_seminar_pl', 'status'], 'fpst_pl_seminar_idx');
        });

        Schema::create('logbook_pl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('praktik_lapang_id')->constrained('praktik_lapang')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('aktivitas', 255);
            $table->text('uraian');
            $table->string('file_dokumentasi')->nullable();
            $table->boolean('diverifikasi_dpl')->default(false);
            $table->timestamp('diverifikasi_at')->nullable();
            $table->string('catatan_dpl')->nullable();
            $table->timestamps();

            $table->index(['praktik_lapang_id', 'tanggal'], 'fpst_logbook_pl_tgl_idx');
        });

        if (! Schema::hasColumn('surat', 'praktik_lapang_id')) {
            Schema::table('surat', function (Blueprint $table) {
                $table->foreignId('praktik_lapang_id')
                    ->nullable()
                    ->after('pengajuan_surat_id')
                    ->constrained('praktik_lapang')
                    ->nullOnDelete();
            });
        }

        // ============================================================
        // FASE 4: COMPOSITE DATABASE INDEXING UNTUK BEBAN PEAK
        // ============================================================
        $this->addIndexIfMissing('pengajuan_skripsi', ['program_studi_id', 'status'], 'fpst_skripsi_prodi_status_idx');
        $this->addIndexIfMissing('seminar_skripsi', ['tgl_seminar', 'status'], 'fpst_seminar_tgl_status_idx');
        $this->addIndexIfMissing('sidang_skripsi', ['tgl_sidang', 'status'], 'fpst_sidang_tgl_status_idx');
        $this->addIndexIfMissing('notifikasi', ['user_id', 'dibaca'], 'fpst_notifikasi_user_dibaca_idx');
        $this->addIndexIfMissing('pengajuan_surat', ['program_studi_id', 'status'], 'fpst_pengajuan_surat_prodi_status_idx');
        $this->addIndexIfMissing('aktivitas_log', ['user_id', 'created_at'], 'fpst_aktivitas_user_created_idx');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('aktivitas_log', 'fpst_aktivitas_user_created_idx');
        $this->dropIndexIfExists('pengajuan_surat', 'fpst_pengajuan_surat_prodi_status_idx');
        $this->dropIndexIfExists('notifikasi', 'fpst_notifikasi_user_dibaca_idx');
        $this->dropIndexIfExists('sidang_skripsi', 'fpst_sidang_tgl_status_idx');
        $this->dropIndexIfExists('seminar_skripsi', 'fpst_seminar_tgl_status_idx');
        $this->dropIndexIfExists('pengajuan_skripsi', 'fpst_skripsi_prodi_status_idx');

        if (Schema::hasColumn('surat', 'praktik_lapang_id')) {
            Schema::table('surat', function (Blueprint $table) {
                $table->dropConstrainedForeignId('praktik_lapang_id');
            });
        }

        Schema::dropIfExists('logbook_pl');
        Schema::dropIfExists('praktik_lapang');
        Schema::dropIfExists('lokasi_pl');
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }
};
