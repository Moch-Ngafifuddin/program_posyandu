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
        Schema::table('pasien', function (Blueprint $table) {
            // 🟢 Tambahkan pengecekan ini agar aman jika kolom sudah ada
            if (!Schema::hasColumn('pasien', 'nik_hash')) {
                $table->string('nik_hash', 64)->nullable()->after('nik');
                $table->index('nik_hash');
            }
            
            // Mengubah tipe data kolom identitas menjadi TEXT untuk menampung hasil enkripsi AES-256
            $table->text('nik')->nullable()->change();
            $table->text('no_kk')->nullable()->change();
            $table->text('no_hp')->nullable()->change();
            $table->text('nik_ayah')->nullable()->change();
            $table->text('nik_ibu')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemeriksaan_bayi', function (Blueprint $table) {
            //
        });
    }
};
