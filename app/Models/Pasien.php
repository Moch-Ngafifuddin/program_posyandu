<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pasien extends Model
{
    protected $table = 'pasien';

    protected $fillable = [
        'posyandu_id',
        'nik',
        'nik_hash',
        'no_kk',
        'nama',
        'jenis_kelamin',
        'tgl_lahir',
        'tempat_lahir',
        'alamat',
        'rt',
        'rw',
        'no_hp',
        'nama_wali',
        'nama_ayah',
        'nik_ayah',
        'pendidikan_pekerjaan_ayah',
        'nama_ibu',
        'nik_ibu',
        'pendidikan_pekerjaan_ibu',
        'is_arsip',
    ];
    
    protected $casts = [
        'nik' => 'encrypted',
        'no_kk' => 'encrypted',
        'no_hp' => 'encrypted',
        'nik_ibu' => 'encrypted',
        'nik_ayah' => 'encrypted',
    ];

    // Relasi ke Master Posyandu
    public function posyandu(): BelongsTo
    {
        return $this->belongsTo(MasterPosyandu::class, 'posyandu_id');
    }

    // Relasi ke Riwayat Kelahiran (Tabel Terpisah Baru)
    public function riwayatKelahiran(): HasOne
    {
        return $this->hasOne(PasienRiwayatKelahiran::class, 'pasien_id');
    }

    // Relasi ke Kondisi Khusus (Tabel Terpisah Baru)
    public function kondisiKhusus(): HasOne
    {
        return $this->hasOne(PasienKondisiKhusus::class, 'pasien_id');
    }

    public function latestPemeriksaan(): HasOne
    {
        return $this->hasOne(PemeriksaanBayi::class, 'pasien_id')->latestOfMany('id');
    }

    public function pemeriksaanBayi(): HasMany
    {
        return $this->hasMany(PemeriksaanBayi::class, 'pasien_id');
    }

    protected static function booted()
    {
        static::saving(function ($pasien) {
            if ($pasien->isDirty('nik')) {
                $pasien->nik_hash = $pasien->nik ? hash_hmac('sha256', $pasien->nik, config('app.key')) : null;
            }
        });

        static::addGlobalScope('posyandu_filter', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                if ($user->posyandu_id) {
                    $builder->where('posyandu_id', $user->posyandu_id);
                }
            }
        });
    }
}