<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Helpers\AntropometriHelper;

class PemeriksaanBayi extends Model
{
    use HasFactory;

    protected $table = 'pemeriksaan_bayi';

    protected $fillable = [
        'pasien_id',
        'jadwal_id', 
        'petugas_id', 
        'tgl_periksa',
        'keterangan_umur',
        'usia_bulan',
        'berat_badan',
        'tinggi_badan',
        'cara_ukur',
        'lila',
        'lingkar_kepala',
        'status_gizi',
        'status_stunting',
        'status_bbtb',
        'zscore_bbu',
        'zscore_tbu',
        'zscore_bbtb',
        'kenaikan_bb',
        'keterangan_bb',
        'rambu_gizi',
        'titik_pertumbuhan',
    ];

    public function pasien(): BelongsTo 
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    // Relasi Baru ke Jadwal Posyandu
    public function jadwal(): BelongsTo 
    {
        return $this->belongsTo(JadwalPosyandu::class, 'jadwal_id');
    }

    // Relasi Baru ke Petugas (Users)
    public function petugas(): BelongsTo 
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    // Relasi Baru ke Intervensi Klinis (Pecahan kolom intervensi lama)
    public function intervensiKlinis(): HasOne
    {
        return $this->hasOne(PemeriksaanIntervensiKlinis::class, 'pemeriksaan_bayi_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if ($model->pasien_id && $model->usia_bulan !== null && $model->berat_badan) {
                $hasilKbm = AntropometriHelper::hitungKBM(
                    $model->pasien_id, 
                    $model->usia_bulan, 
                    (float) $model->berat_badan
                );
                $model->kenaikan_bb = $hasilKbm['kenaikan_bb'];
                $model->keterangan_bb = $hasilKbm['keterangan_bb'];
            }
        });

        static::updating(function ($model) {
            if ($model->pasien_id && $model->usia_bulan !== null && $model->berat_badan) {
                $hasilKbm = AntropometriHelper::hitungKBM(
                    $model->pasien_id, 
                    $model->usia_bulan, 
                    (float) $model->berat_badan
                );
                $model->kenaikan_bb = $hasilKbm['kenaikan_bb'];
                $model->keterangan_bb = $hasilKbm['keterangan_bb'];
            }
        });

        static::saving(function ($model) {
            $pasien = $model->pasien ?? Pasien::find($model->pasien_id);
            
            if ($pasien && $model->berat_badan && $model->tinggi_badan) {
                $tbKoreksi = (float) $model->tinggi_badan;
                if ($model->usia_bulan < 24 && $model->cara_ukur == 'berdiri') {
                    $tbKoreksi += 0.7;
                } elseif ($model->usia_bulan >= 24 && $model->cara_ukur == 'terlentang') {
                    $tbKoreksi -= 0.7;
                }

                $jk = $pasien->jenis_kelamin;
                $bb = $model->berat_badan;
                $umur = $model->usia_bulan;

                $model->zscore_bbu = AntropometriHelper::hitungZScoreBBU($jk, $umur, $bb);
                $model->status_gizi = AntropometriHelper::hitungBbu($jk, $umur, $bb);

                $model->zscore_tbu = AntropometriHelper::hitungZScoreTBU($jk, $umur, $tbKoreksi);
                $model->status_stunting = AntropometriHelper::hitungTbu($jk, $umur, $tbKoreksi);

                $model->zscore_bbtb = AntropometriHelper::hitungZScoreBBTB($jk, $tbKoreksi, $bb);
                $model->status_bbtb = AntropometriHelper::hitungBbtb($jk, $tbKoreksi, $bb);
            }
        });
    }
}