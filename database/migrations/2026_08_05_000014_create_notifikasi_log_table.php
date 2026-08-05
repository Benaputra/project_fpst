<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notifiable_id');
            $table->string('notifiable_type');
            $table->string('jenis', 64);
            $table->string('status_kirim', 16);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['notifiable_type', 'notifiable_id', 'jenis'], 'notifikasi_subjek_jenis_unique');
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_log');
    }
};
