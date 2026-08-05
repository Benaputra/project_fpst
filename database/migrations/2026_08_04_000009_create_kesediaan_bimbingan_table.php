<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kesediaan_bimbingan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skripsi_id');
            $table->string('dosen_id', 20);
            $table->string('peran', 16);
            $table->unsignedInteger('siklus');
            $table->string('status', 32)->default('ditunjuk');
            $table->string('hasil', 20)->nullable();
            $table->text('catatan_mahasiswa')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->timestamps();

            $table->unique(['skripsi_id', 'peran', 'siklus']);
            $table->foreign('skripsi_id')
                ->references('id')
                ->on('skripsi')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('dosen_id')
                ->references('nidn')
                ->on('dosen')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('diverifikasi_oleh')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE kesediaan_bimbingan
            ADD CONSTRAINT kesediaan_bimbingan_siklus_positive CHECK (siklus > 0)
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('kesediaan_bimbingan');
    }
};
