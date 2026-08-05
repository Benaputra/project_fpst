<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->exists()) {
            throw new RuntimeException(
                'Migration role dihentikan: petakan role pengguna yang sudah ada terlebih dahulu.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
