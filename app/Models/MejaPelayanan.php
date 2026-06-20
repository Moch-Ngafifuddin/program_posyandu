<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MejaPelayanan extends Model
{
    protected $table = 'meja_pelayanan';
    protected $fillable = ['kode_meja', 'nama_meja', 'deskripsi'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'meja_pelayanan_id');
    }
}