<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser; 
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable implements FilamentUser 
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



    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@posyandu.com') 
            || $this->meja_tugas === 'superadmin' 
            || !is_null($this->meja_pelayanan_id);
    }


    public function posyandu(): BelongsTo
    {
        return $this->belongsTo(MasterPosyandu::class, 'posyandu_id');
    }


    public function mejaPelayanan(): BelongsTo
    {
        return $this->belongsTo(MejaPelayanan::class, 'meja_pelayanan_id');
    }
}