<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterPosyandu extends Model
{
    // Mengarahkan ke nama tabel induk posyandu di database baru
    protected $table = 'master_posyandu';

    protected $fillable = [
        'nama_posyandu',
        'nama_puskesmas',
        'provinsi',
        'kabupaten_kota',
        'kecamatan',
        'desa_kelurahan',
    ];

    /**
     * Relasi ke data users/kader di posyandu ini
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'posyandu_id');
    }

    /**
     * Relasi ke data anak/pasien di posyandu ini
     */
    public function pasien(): HasMany
    {
        return $this->hasMany(Pasien::class, 'posyandu_id');
    }

    /**
     * Relasi ke jadwal agenda kegiatan posyandu ini
     */
    public function jadwalPosyandu(): HasMany
    {
        return $this->hasMany(JadwalPosyandu::class, 'posyandu_id');
    }
}