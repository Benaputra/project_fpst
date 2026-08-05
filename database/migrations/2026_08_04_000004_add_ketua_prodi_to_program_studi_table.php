<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_studi', function (Blueprint $table) {
            $table->string('ketua_prodi_id', 20)->nullable()->after('nama');
            $table->foreign('ketua_prodi_id')
                ->references('nidn')
                ->on('dosen')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('program_studi', function (Blueprint $table) {
            $table->dropForeign(['ketua_prodi_id']);
            $table->dropColumn('ketua_prodi_id');
        });
    }
};
