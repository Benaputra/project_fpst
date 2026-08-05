<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documentable_id');
            $table->string('documentable_type');
            $table->string('jenis', 64);
            $table->unsignedInteger('versi')->default(1);
            $table->string('file_path');
            $table->char('file_hash', 64);
            $table->string('status', 32)->default('diunggah');
            $table->foreignId('uploaded_by');
            $table->timestamp('uploaded_at');
            $table->foreignId('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();

            $table->index(['documentable_type', 'documentable_id']);
            $table->unique([
                'documentable_type',
                'documentable_id',
                'jenis',
                'versi',
            ], 'dokumen_subjek_jenis_versi_unique');
            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('verified_by')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE dokumen_pengajuan
            ADD CONSTRAINT dokumen_pengajuan_versi_positive CHECK (versi > 0),
            ADD CONSTRAINT dokumen_pengajuan_file_hash_sha256 CHECK (
                file_hash REGEXP '^[0-9a-f]{64}$'
            )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_pengajuan');
    }
};
