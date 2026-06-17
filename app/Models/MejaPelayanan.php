<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MejaPelayanan extends Model
{
    protected $table = 'meja_pelayanan';
    protected $fillable = ['kode_meja', 'nama_meja', 'deskripsi'];
}