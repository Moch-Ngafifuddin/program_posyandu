<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Helpers\AntropometriHelper;

class PemeriksaanBayi extends Model
{
    use HasFactory;
    protected $table = 'pemeriksaan_bayi';
    protected $casts = ['jenis_imunisasi' => 'array'];
    protected $fillable = [
        'pasien_id',
        'tgl_periksa',
        'keterangan_umur',
        'usia_bulan',
        'berat_badan',
        'tinggi_badan',
        'cara_ukur',
        'lila',
        'lingkar_lengan',
        'lingkar_kepala',
        'status_gizi',
        'status_stunting',
        'status_bbtb',
        'zscore_bbu',
        'zscore_tbu',
        'zscore_bbtb',
        'kenaikan_bb',
        'keterangan_bb',
        'pitting_edema',
        'vitamin_a',
        'obat_cacing',
        'jenis_imunisasi',
        'asi_eksklusif',
        'pmba',
        'sdidtk',
        'rambu_gizi',
        'titik_pertumbuhan',
        'deteksi_tbc',
        'kie',
        'rujuk',
        'kelas_ibu',
        'menerima_mbg',
        'catatan',
    ];
    public function pasien(): BelongsTo {
        return $this->belongsTo(Pasien::class, 'pasien_id');
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

        // Otomatis hitung kembali jika data berat badan diedit/diubah
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
            //$pasien = Pasien::find($model->pasien_id);
            $pasien = $model->pasien ?? Pasien::find($model->pasien_id);
            
            if ($pasien && $model->berat_badan && $model->tinggi_badan) {
                // Koreksi toleransi Cara Ukur berdasarkan PMK No.2 Tahun 2020
                // Anak < 24 bulan diukur berdiri: + 0.7 cm
                // Anak >= 24 bulan diukur terlentang: - 0.7 cm
                $tbKoreksi = (float) $model->tinggi_badan;
                if ($model->usia_bulan < 24 && $model->cara_ukur == 'berdiri') {
                    $tbKoreksi += 0.7;
                } elseif ($model->usia_bulan >= 24 && $model->cara_ukur == 'terlentang') {
                    $tbKoreksi -= 0.7;
                }

                $jk = $pasien->jenis_kelamin;
                $bb = $model->berat_badan;
                $umur = $model->usia_bulan;

                // Hitung BB/U
                $model->zscore_bbu = AntropometriHelper::hitungZScoreBBU($jk, $umur, $bb);
                $model->status_gizi = AntropometriHelper::hitungBbu($jk, $umur, $bb);

                // Hitung TB/U
                $model->zscore_tbu = AntropometriHelper::hitungZScoreTBU($jk, $umur, $tbKoreksi);
                $model->status_stunting = AntropometriHelper::hitungTbu($jk, $umur, $tbKoreksi);

                // Hitung BB/TB
                $model->zscore_bbtb = AntropometriHelper::hitungZScoreBBTB($jk, $tbKoreksi, $bb);
                $model->status_bbtb = AntropometriHelper::hitungBbtb($jk, $tbKoreksi, $bb);
            }
        });
    }
}