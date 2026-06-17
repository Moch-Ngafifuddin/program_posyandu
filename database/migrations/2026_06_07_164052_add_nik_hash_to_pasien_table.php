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
        // PENCEGAHAN ERROR: Cek dulu apakah kolom 'nik_hash' sudah ada di tabel pasien
        if (!Schema::hasColumn('pasien', 'nik_hash')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->string('nik_hash', 64)->nullable()->index(); 
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            // Rollback aman: Hapus kolom jika memang ada
            if (Schema::hasColumn('pasien', 'nik_hash')) {
                $table->dropColumn('nik_hash');
            }
        });
    }
};