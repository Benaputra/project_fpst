<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktivitas_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_type');
            $table->string('aksi', 100);
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->foreign('user_id')->references('id')->on('users')
                ->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_log');
    }
};
