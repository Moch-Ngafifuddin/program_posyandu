<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasienRiwayatKelahiran extends Model
{
    protected $table = 'pasien_riwayat_kelahiran';

    protected $fillable = [
        'pasien_id',
        'anak_ke',
        'usia_kehamilan',
        'berat_lahir',
        'panjang_lahir',
        'lingkar_kepala_lahir',
        'imd',
        'riwayat_asi',
    ];


    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }
}