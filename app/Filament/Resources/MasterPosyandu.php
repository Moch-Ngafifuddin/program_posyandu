<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterPosyandu extends Model
{
    protected $table = 'master_posyandu';

    protected $fillable = [
        'nama_posyandu',
        'nama_puskesmas',
        'provinsi',
        'kabupaten_kota',
        'kecamatan',
        'desa_kelurahan',
    ];


    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'posyandu_id');
    }


    public function pasien(): HasMany
    {
        return $this->hasMany(Pasien::class, 'posyandu_id');
    }


    public function jadwalPosyandu(): HasMany
    {
        return $this->hasMany(JadwalPosyandu::class, 'posyandu_id');
    }
}