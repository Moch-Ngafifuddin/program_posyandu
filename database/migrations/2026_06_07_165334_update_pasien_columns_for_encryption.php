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
        // 1. Hapus Unique Constraint pada NIK jika ada (karena tipe TEXT tidak bisa jadi UNIQUE key biasa)
        try {
            Schema::table('pasien', function (Blueprint $table) {
                $table->dropUnique(['nik']); 
            });
        } catch (\Exception $e) {
            // Abaikan jika indeks unique 'nik' memang sudah tidak ada
        }

        // 2. Ubah kolom identitas menjadi TEXT agar bisa menampung string hasil enkripsi Laravel
        Schema::table('pasien', function (Blueprint $table) {
            $table->text('nik')->nullable()->change();
            $table->text('no_kk')->nullable()->change();
            $table->text('no_hp')->nullable()->change();
            $table->text('nik_ibu')->nullable()->change();
            $table->text('nik_ayah')->nullable()->change();
        });

        // 3. Tambahkan nik_hash HANYA jika kolom tersebut belum ada di tabel pasien
        if (!Schema::hasColumn('pasien', 'nik_hash')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->string('nik_hash', 64)->nullable()->unique();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            // Kebalikan dari up jika di-rollback (opsional)
        });
    }
};