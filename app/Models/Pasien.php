<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pasien extends Model
{
    protected $table = 'pasien';

    protected $fillable = [
        'is_arsip',
        'keterangan_pindah',
        'tgl_meninggal',
        'tempat_pemakaman',
        'penyebab_meninggal',
        'nik',
        'nik_hash',
        'no_kk',
        'nama',
        'jenis_kelamin',
        'tgl_lahir',
        'tempat_lahir',
        'alamat',
        'rt',
        'rw',
        'provinsi',
        'kabupaten',
        'kecamatan',
        'desa_kelurahan',
        'nama_puskesmas',
        'nama_posyandu',
        'no_hp',
        'nama_wali',
        'nama_ayah',
        'nik_ayah',
        'pendidikan_pekerjaan_ayah',
        'nama_ibu',
        'nik_ibu',
        'pendidikan_pekerjaan_ibu',
        'anak_ke',
        'usia_kehamilan',
        'berat_lahir',
        'panjang_lahir',
        'lingkar_kepala_lahir',
        'imd',
        'riwayat_asi',
        'buku_kia_bayi_kecil',
        'tatalaksana_bblr',
    ];
    
    protected $casts = [
        'nik' => 'encrypted',
        'no_kk' => 'encrypted',
        'no_hp' => 'encrypted',
        'nik_ibu' => 'encrypted',
        'nik_ayah' => 'encrypted',
        
    ];
    protected $guarded = [];

    public function latestPemeriksaan(): HasOne
    {
        return $this->hasOne(PemeriksaanBayi::class, 'pasien_id')->latestOfMany('id');
    }

    public function pemeriksaanBayi()
    {
        return $this->hasMany(PemeriksaanBayi::class, 'pasien_id');
    }

    protected static function booted()
    {
        static::saving(function ($pasien) {
            if ($pasien->isDirty('nik')) {
                $pasien->nik_hash = $pasien->nik ? hash_hmac('sha256', $pasien->nik, config('app.key')) : null;
            }
        });
    }
}