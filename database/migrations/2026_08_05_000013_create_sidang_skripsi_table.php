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
            $table->foreignId('skripsi_id')->unique();
            $table->string('penguji1_id', 20)->nullable();
            $table->string('penguji2_id', 20)->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->string('tempat')->nullable();
            $table->string('status', 20)->default('diajukan');
            $table->text('catatan_reject')->nullable();
            $table->foreignId('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->foreign('skripsi_id')->references('id')->on('skripsi')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('penguji1_id')->references('nidn')->on('dosen')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('penguji2_id')->references('nidn')->on('dosen')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidang_skripsi');
    }
};
