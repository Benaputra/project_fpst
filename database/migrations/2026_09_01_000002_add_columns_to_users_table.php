<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nomor_induk')->nullable()->unique()->after('email'); // NIM atau NIP/NIDN
            $table->string('role')->default('mahasiswa')->after('nomor_induk');
            $table->foreignId('program_studi_id')->nullable()->after('role')->constrained('program_studi')->nullOnDelete();
            $table->string('no_hp')->nullable()->after('program_studi_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['program_studi_id']);
            $table->dropColumn(['nomor_induk', 'role', 'program_studi_id', 'no_hp']);
        });
    }
};
