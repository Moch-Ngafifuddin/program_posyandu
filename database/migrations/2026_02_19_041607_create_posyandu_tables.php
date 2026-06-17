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
        // 🟢 FIX UTAMA: Kondisi 'if' harus berada di dalam tanda kurung kurawal method up()
        if (!Schema::hasTable('pasien')) {
            Schema::create('pasien', function (Blueprint $table) {
                $table->id();
                
                // HAPUS ->unique() dari kolom text 'nik' untuk menghindari error MySQL 1170
                $table->text('nik'); 
                
                // PINDAHKAN sifat unik ke kolom nik_hash (Tipe string aman untuk unique index)
                $table->string('nik_hash', 64)->unique()->comment('Hash SHA256 dari NIK untuk validasi duplikasi data terenkripsi');
                
                $table->text('no_kk')->nullable();
                $table->string('nama');
                $table->enum('jenis_kelamin', ['L', 'P']);
                $table->date('tgl_lahir');
                $table->string('tempat_lahir');
                $table->text('alamat');
                $table->text('no_hp')->nullable();
                $table->string('nama_wali')->nullable();
                
                // Kolom tambahan pelengkap
                $table->string('nama_ayah')->nullable();
                $table->text('nik_ayah')->nullable();
                $table->string('pendidikan_pekerjaan_ayah')->nullable();
                $table->string('nama_ibu')->nullable();
                $table->text('nik_ibu')->nullable();
                $table->string('pendidikan_pekerjaan_ibu')->nullable();
                $table->integer('anak_ke')->nullable();
                $table->decimal('berat_lahir', 6, 2)->nullable();
                $table->decimal('panjang_lahir', 5, 2)->nullable();
                $table->boolean('imd')->default(false);
                $table->string('riwayat_asi')->nullable();
                $table->timestamps();
            });
        } // 🟢 FIX KEDUA: Batasan 'if' untuk tabel pasien selesai di sini

        // 🟢 Tambahkan proteksi yang sama untuk tabel pemeriksaan_bayi agar anti-bentrok
        if (!Schema::hasTable('pemeriksaan_bayi')) {
            Schema::create('pemeriksaan_bayi', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pasien_id')->constrained('pasien')->cascadeOnDelete();
                $table->date('tgl_periksa');
                $table->string('keterangan_umur')->nullable();
                $table->integer('usia_bulan')->nullable();
                $table->decimal('berat_badan', 5, 2)->nullable();
                $table->decimal('tinggi_badan', 5, 2)->nullable();
                $table->string('cara_ukur', 20)->nullable(); 
                $table->decimal('lila', 5, 2)->nullable();
                $table->decimal('lingkar_lengan', 5, 2)->nullable();
                $table->decimal('lingkar_kepala', 5, 2)->nullable();
                
                $table->string('status_gizi')->nullable();      
                $table->string('status_stunting')->nullable();  
                $table->string('status_bbtb')->nullable();      
                $table->string('zscore_bbu')->nullable();
                $table->string('zscore_tbu')->nullable();
                $table->string('zscore_bbtb')->nullable();
                
                $table->decimal('kenaikan_bb', 5, 2)->nullable();
                $table->string('keterangan_bb')->nullable(); 
                
                $table->boolean('pitting_edema')->default(false);
                $table->boolean('vitamin_a')->default(false);
                $table->boolean('obat_cacing')->default(false);
                $table->string('jenis_imunisasi')->nullable();
                $table->boolean('asi_eksklusif')->default(false);
                $table->boolean('pmba')->default(false);
                $table->boolean('sdidtk')->default(false);
                $table->string('rambu_gizi')->nullable();
                $table->string('titik_pertumbuhan')->nullable();
                $table->boolean('deteksi_tbc')->default(false);
                $table->boolean('kie')->default(false);
                $table->boolean('rujuk')->default(false);
                $table->boolean('kelas_ibu')->default(false);
                $table->boolean('menerima_mbg')->default(false);
                
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_bayi');
        Schema::dropIfExists('pasien');
    }
};