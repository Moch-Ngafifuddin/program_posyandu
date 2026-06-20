<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalPosyandu extends Model
{
    protected $table = 'jadwal_posyandu';

    protected $fillable = [
        'posyandu_id', 
        'judul_agenda',
        'tempat_acara',
        'kategori_target',
        'tanggal_acara',
        'waktu_acara',
        'jam_kirim_pesan',
        'template_id', 
        'isi_pesan',
        'is_aktif',
    ];

    public function templatePesan(): BelongsTo
    {
        return $this->belongsTo(TemplatePesan::class, 'template_id');
    }

    public function posyandu(): BelongsTo
    {
        return $this->belongsTo(MasterPosyandu::class, 'posyandu_id');
    }
}