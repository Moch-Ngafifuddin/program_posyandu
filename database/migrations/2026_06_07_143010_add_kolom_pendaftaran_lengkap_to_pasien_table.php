<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('pasien', function (Blueprint $table) {
        // 🟢 Cek dulu, jika kolom nama_ayah BELUM ADA, baru tambahkan
        if (!Schema::hasColumn('pasien', 'nama_ayah')) {
            $table->string('nama_ayah')->nullable()->after('nama_wali');
        }

        // 🟢 Lakukan hal yang sama untuk kolom-kolom lain di bawahnya jika ada yang duplikat
        if (!Schema::hasColumn('pasien', 'nik_ayah')) {
            $table->text('nik_ayah')->nullable();
        }

        if (!Schema::hasColumn('pasien', 'pendidikan_pekerjaan_ayah')) {
            $table->string('pendidikan_pekerjaan_ayah')->nullable();
        }

        if (!Schema::hasColumn('pasien', 'nama_ibu')) {
            $table->string('nama_ibu')->nullable();
        }

        if (!Schema::hasColumn('pasien', 'nik_ibu')) {
            $table->text('nik_ibu')->nullable();
        }

        if (!Schema::hasColumn('pasien', 'pendidikan_pekerjaan_ibu')) {
            $table->string('pendidikan_pekerjaan_ibu')->nullable();
        }
    });
}

    public function down(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->dropColumn([
                'usia_kehamilan', 'lingkar_kepala_lahir', 'buku_kia_bayi_kecil', 
                'tatalaksana_bblr', 'provinsi', 'kabupaten', 'kecamatan', 
                'desa_kelurahan', 'nama_puskesmas', 'nama_posyandu', 'rt', 'rw'
            ]);
            $table->string('nik', 16)->nullable(false)->change();
        });
    }
};