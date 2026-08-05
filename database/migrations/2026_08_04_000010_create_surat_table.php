<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('suratable_id');
            $table->string('suratable_type');
            $table->foreignId('program_studi_id');
            $table->string('jenis_surat', 64);
            $table->string('no_surat')->unique();
            $table->string('tujuan_surat');
            $table->unsignedInteger('versi')->default(1);
            $table->string('status', 20)->default('draft');
            $table->string('file_path')->nullable();
            $table->char('file_hash', 64)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('signed_by', 20)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['suratable_type', 'suratable_id']);
            $table->unique([
                'suratable_type',
                'suratable_id',
                'jenis_surat',
                'versi',
            ], 'surat_subjek_jenis_versi_unique');
            $table->foreign('program_studi_id')
                ->references('id')
                ->on('program_studi')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('verified_by')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('signed_by')
                ->references('nidn')
                ->on('dosen')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE surat
            ADD CONSTRAINT surat_versi_positive CHECK (versi > 0),
            ADD CONSTRAINT surat_file_hash_sha256 CHECK (
                file_hash IS NULL OR file_hash REGEXP '^[0-9a-f]{64}$'
            )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};
