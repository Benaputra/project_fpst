<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penugasan_dosen', function (Blueprint $table) {
            $table->id();
            $table->morphs('assignable'); // assignable_type (PengajuanSkripsi, SeminarSkripsi, SidangSkripsi) & assignable_id
            $table->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
            $table->string('peran'); // 'pembimbing_1', 'pembimbing_2', 'penguji_seminar', 'penguji_1', 'penguji_2'
            $table->string('status')->default('menunggu'); // 'menunggu', 'disetujui', 'ditolak', 'dibatalkan'
            $table->text('alasan_penolakan')->nullable();
            $table->foreignId('rekomendasi_dosen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ditugaskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_mandat_admin_utama')->default(false);
            $table->timestamp('direspon_pada')->nullable();
            $table->timestamps();

            $table->index(['dosen_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penugasan_dosen');
    }
};
