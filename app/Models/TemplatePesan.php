<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplatePesan extends Model
{
    protected $table = 'template_pesan';
    
    protected $guarded = [];


    public function jadwalPosyandu(): HasMany
    {
        return $this->hasMany(JadwalPosyandu::class, 'template_id');
    }
}