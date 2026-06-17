<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasienKondisiKhusus extends Model
{
    protected $table = 'pasien_kondisi_khusus';

    protected $fillable = [
        'pasien_id',
        'keterangan_pindah',
        'tgl_meninggal',
        'tempat_pemakaman',
        'penyebab_meninggal',
    ];

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }
}