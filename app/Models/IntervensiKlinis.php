<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntervensiKlinis extends Model
{
    protected $table = 'pemeriksaan_intervensi_klinis'; 

    protected $fillable = [
        'pemeriksaan_bayi_id',
        'rambu_gizi',
        'titik_pertumbuhan',
        'pitting_edema',
        'vitamin_a',
        'obat_cacing',
        'asi_eksklusif',
        'pmba',
        'sdidtk',
        'kelas_ibu',
        'menerima_mbg',
        'jenis_imunisasi',
        'catatan',
        'deteksi_tbc',
        'kie',
        'rujuk',
    ];

    protected $casts = [
        'jenis_imunisasi' => 'array',
        'vitamin_a' => 'boolean',
        'obat_cacing' => 'boolean',
        'asi_eksklusif' => 'boolean',
        'pmba' => 'boolean',
        'sdidtk' => 'boolean',
        'kelas_ibu' => 'boolean',
        'menerima_mbg' => 'boolean',
        'deteksi_tbc' => 'boolean',
        'kie' => 'boolean',
        'rujuk' => 'boolean',
    ];

    public function pemeriksaanBayi(): BelongsTo
    {
        return $this->belongsTo(PemeriksaanBayi::class, 'pemeriksaan_bayi_id');
    }
}