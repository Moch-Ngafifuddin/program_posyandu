<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'akses_menu',
        'meja_tugas',
        'provinsi',
        'kabupaten_kota',
        'kecamatan',
        'desa_kelurahan',
        'nama_puskesmas',
        'nama_posyandu',
        'meja_pelayanan_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected static function booted()
        {
            static::saving(function ($user) {
                if ($user->meja_pelayanan_id) {
                    $meja = \App\Models\MejaPelayanan::find($user->meja_pelayanan_id);
                    if ($meja) {
                        $user->meja_tugas = $meja->kode_meja;
                    }
                } else {
                    $user->meja_tugas = null;
                }
            });
        }


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'akses_menu' => 'array', //
        ];
    }

    public function mejaPelayanan()
    {
        return $this->belongsTo(MejaPelayanan::class, 'meja_pelayanan_id');
    }
}