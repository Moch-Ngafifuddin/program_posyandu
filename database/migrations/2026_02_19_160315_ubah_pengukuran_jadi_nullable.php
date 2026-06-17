<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hanya ubah jika tabel pemeriksaan_bayi ada
        if (Schema::hasTable('pemeriksaan_bayi')) {
            Schema::table('pemeriksaan_bayi', function (Blueprint $table) {
                $table->decimal('berat_badan', 5, 2)->nullable()->change();
                $table->decimal('tinggi_badan', 5, 2)->nullable()->change();
                $table->decimal('lingkar_kepala', 5, 2)->nullable()->change();
            });
        }

        // PROTEKSI: Jika tabel remaja sudah dihapus, blok ini akan dilewati dengan aman tanpa error!
        if (Schema::hasTable('pemeriksaan_remaja')) {
            Schema::table('pemeriksaan_remaja', function (Blueprint $table) {
                $table->decimal('berat_badan', 5, 2)->nullable()->change();
                $table->decimal('tinggi_badan', 5, 2)->nullable()->change();
            });
        }

        // PROTEKSI: Jika tabel lansia sudah dihapus, blok ini akan dilewati dengan aman tanpa error!
        if (Schema::hasTable('pemeriksaan_lansia')) {
            Schema::table('pemeriksaan_lansia', function (Blueprint $table) {
                $table->decimal('berat_badan', 5, 2)->nullable()->change();
                $table->decimal('tinggi_badan', 5, 2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Tidak perlu diisi untuk down() dalam kasus ini
    }
};