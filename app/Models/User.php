<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser; // Ambil kontrak Filament
use Filament\Panel; // Jalur kelas Panel
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser // Tambahkan implements FilamentUser
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'posyandu_id',
        'meja_pelayanan_id',
        'name',
        'email',
        'password',
        'meja_tugas',
        'akses_menu',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'akses_menu' => 'json',
    ];

    /**
     * Mengatur hak akses user untuk masuk ke Panel Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Berikan izin akses jika user memiliki email admin atau kolom meja_tugas berisi 'Admin'
        return str_ends_with($this->email, '@posyandu.com') || $this->meja_tugas === 'Admin';
    }
}