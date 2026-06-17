<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $fillable = [
        'nama_puskesmas',
        'teks_login',
        'logo',
        'warna_tema',
        'logos',
        'background_login',
        'tinggi_logo_utama',
        'posisi_form_login',
        'text_logo'
    ];
    
    protected $casts = [
        'logos' => 'array',
    ];
}