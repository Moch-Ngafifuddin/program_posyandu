<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\MasterPosyandu;

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
        'provinsi',
        'kabupaten',
        'kecamatan',
        'desa_kelurahan',
        'nama_puskesmas',
        'nama_posyandu',
    ];
    
    protected $casts = [
        'nik' => 'encrypted',
        'no_kk' => 'encrypted',
        'no_hp' => 'encrypted',
        'nik_ibu' => 'encrypted',
        'nik_ayah' => 'encrypted',
    ];

    public function posyandu(): BelongsTo
    {
        return $this->belongsTo(MasterPosyandu::class, 'posyandu_id');
        
    }

    public function riwayatKelahiran(): HasOne
    {
        return $this->hasOne(PasienRiwayatKelahiran::class, 'pasien_id');
    }

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
        static::addGlobalScope('posyandu_filter', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                if ($user->posyandu_id) {
                    $builder->where('posyandu_id', $user->posyandu_id);
                }
            }
        });

        static::saving(function ($pasien) {
            $nikMentah = $pasien->getAttributes()['nik'] ?? null;

            if ($nikMentah) {
                $pasien->nik_hash = hash_hmac('sha256', $nikMentah, config('app.key'));
            } else {
                $pasien->nik_hash = '-'; 
            }
        });
    }
}