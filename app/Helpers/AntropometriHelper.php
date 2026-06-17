<?php

namespace App\Helpers;

use App\Models\MasterBbu;
use App\Models\MasterTbu;
use App\Models\MasterBbtb;
use App\Models\PemeriksaanBayi;
use Illuminate\Support\Facades\Cache;

class AntropometriHelper
{
    /**
     * Sempurna Sesuai Standar Evaluasi WHO / Kemenkes RI (PMK No. 2 Tahun 2020)
     */
    private static function hitungZScore($nilaiRiil, $master)
    {
        if (!$master) return null;
        
        $nilaiRiil = (float) $nilaiRiil;
        $median = (float) $master->median;

        if ($nilaiRiil == $median) return 0.00;
        
        // Ambil semua parameter sebaran SD
        $m3sd = (float) $master->minus_3_sd;
        $m2sd = (float) $master->minus_2_sd;
        $m1sd = (float) $master->minus_1_sd;
        $p1sd = (float) $master->plus_1_sd;
        $p2sd = (float) $master->plus_2_sd;
        $p3sd = (float) $master->plus_3_sd;

        // Logika Distribusi Bertingkat Standar Deviasi
        if ($nilaiRiil > $median) {
            if ($nilaiRiil <= $p1sd) {
                $pembagi = $p1sd - $median;
                return $pembagi != 0 ? ($nilaiRiil - $median) / $pembagi : 0;
            } elseif ($nilaiRiil <= $p2sd) {
                $pembagi = $p2sd - $p1sd;
                return $pembagi != 0 ? 1 + (($nilaiRiil - $p1sd) / $pembagi) : 1;
            } else {
                $pembagi = $p3sd - $p2sd;
                return $pembagi != 0 ? 2 + (($nilaiRiil - $p2sd) / $pembagi) : 2;
            }
        } else {
            if ($nilaiRiil >= $m1sd) {
                $pembagi = $median - $m1sd;
                return $pembagi != 0 ? ($nilaiRiil - $median) / $pembagi : 0;
            } elseif ($nilaiRiil >= $m2sd) {
                $pembagi = $m1sd - $m2sd;
                return $pembagi != 0 ? -1 + (($nilaiRiil - $m1sd) / $pembagi) : -1;
            } else {
                $pembagi = $m2sd - $m3sd;
                return $pembagi != 0 ? -2 + (($nilaiRiil - $m2sd) / $pembagi) : -2;
            }
        }
    }

    // 🟢 AKSES PUBLIK: Mendapatkan nilai desimal Z-Score BB/U
    public static function hitungZScoreBBU($jk, $umurBulan, $bb)
    {
        if (!is_numeric($bb) || !is_numeric($umurBulan) || empty($jk)) return null;
        
        $umurBulan = (int) $umurBulan;
        $bbuCache = Cache::rememberForever('master_bbu_all', function () {
            return MasterBbu::all();
        });

        $master = $bbuCache->where('jenis_kelamin', $jk)->where('umur_bulan', $umurBulan)->first();
        return self::hitungZScore($bb, $master);
    }

    // 🟢 AKSES PUBLIK: Mendapatkan nilai desimal Z-Score TB/U
    public static function hitungZScoreTBU($jk, $umurBulan, $tb)
    {
        if (!is_numeric($tb) || !is_numeric($umurBulan) || empty($jk)) return null;
        
        $umurBulan = (int) $umurBulan;
        $tbuCache = Cache::rememberForever('master_tbu_all', function () {
            return MasterTbu::all();
        });

        $master = $tbuCache->where('jenis_kelamin', $jk)->where('umur_bulan', $umurBulan)->first();
        return self::hitungZScore($tb, $master);
    }

    // 🟢 AKSES PUBLIK: Mendapatkan nilai desimal Z-Score BB/TB
    public static function hitungZScoreBBTB($jk, $tb, $bb)
    {
        if (!is_numeric($tb) || !is_numeric($bb) || empty($jk)) return null;
        
        $bbtbCache = Cache::rememberForever('master_bbtb_all', function () {
            return MasterBbtb::all();
        });

        $tbFloat = round((float) $tb, 1); 
        
        $master = $bbtbCache->where('jenis_kelamin', $jk)->sortBy(function($item) use ($tbFloat) {
            return abs((float) $item->tinggi_badan_cm - $tbFloat);
        })->first();

        return self::hitungZScore($bb, $master);
    }

    // Klasifikasi Teks Status Gizi (BB/U)
    public static function hitungBbu($jk, $umurBulan, $bb)
    {
        $zscore = self::hitungZScoreBBU($jk, $umurBulan, $bb);

        if (is_null($zscore)) return 'Data Master Tidak Ditemukan';

        if ($zscore < -3) return 'Berat Badan Sangat Kurang';
        if ($zscore >= -3 && $zscore < -2) return 'Berat Badan Kurang';
        if ($zscore >= -2 && $zscore <= 1) return 'Berat Badan Normal';
        return 'Risiko Berat Badan Lebih';
    }

    // Klasifikasi Teks Status Stunting (TB/U)
    public static function hitungTbu($jk, $umurBulan, $tb)
    {
        $zscore = self::hitungZScoreTBU($jk, $umurBulan, $tb);

        if (is_null($zscore)) return 'Data Master Tidak Ditemukan';

        if ($zscore < -3) return 'Sangat Pendek (Severely Stunted)';
        if ($zscore >= -3 && $zscore < -2) return 'Pendek (Stunted)';
        if ($zscore >= -2 && $zscore <= 3) return 'Normal';
        return 'Tinggi';
    }

    // Klasifikasi Teks Status Gizi Akut (BB/TB)
    public static function hitungBbtb($jk, $tb, $bb)
    {
        $zscore = self::hitungZScoreBBTB($jk, $tb, $bb);

        if (is_null($zscore)) return 'Data Master Tidak Ditemukan';

        if ($zscore < -3) return 'Gizi Buruk (Severely Wasted)';
        if ($zscore >= -3 && $zscore < -2) return 'Gizi Kurang (Wasted)';
        if ($zscore >= -2 && $zscore <= 1) return 'Gizi Baik (Normal)';
        if ($zscore > 1 && $zscore <= 2) return 'Berisiko Gizi Lebih (Possible Risk of Overweight)';
        if ($zscore > 2 && $zscore <= 3) return 'Gizi Lebih (Overweight)';
        return 'Obesitas (Obese)';
    }

    public static function hitungKBM($pasienId, $usiaBulan, $beratSekarang)
    {
        // 1. Standar KBM Buku KIA Kemenkes (dalam satuan Kilogram)
        $daftarKbm = [
            1 => 0.8, // Usia 1 bulan, KBM harus naik min 800gr
            2 => 0.9, // Usia 2 bulan, KBM harus naik min 900gr
            3 => 0.8, // Usia 3 bulan, KBM harus naik min 800gr
            4 => 0.6, // Usia 4 bulan, KBM harus naik min 600gr
            5 => 0.5, // Usia 5 bulan, KBM harus naik min 500gr
            6 => 0.4, // Usia 6 bulan, KBM harus naik min 400gr
            7 => 0.3, 8 => 0.3, 9 => 0.3, 10 => 0.3, 11 => 0.3, // 7-11 bulan min 300gr
        ];

        // Usia 12-59 bulan target KBM-nya flat di 0.2 kg (200gr)
        $targetKbm = $usiaBulan >= 12 ? 0.2 : ($daftarKbm[$usiaBulan] ?? 0.2);

        // Jika ini adalah penimbangan pertama kali (bulan ke-0 / Baru Lahir)
        if ($usiaBulan == 0) {
            return [
                'kenaikan_bb' => 'naik',
                'keterangan_bb' => 'Pemeriksaan awal / berat lahir awal (Bulan ke-0)',
            ];
        }

        // 2. Cari data penimbangan bulan sebelumnya (usia saat ini - 1)
        $pemeriksaanBulanLalu = PemeriksaanBayi::where('pasien_id', $pasienId)
            ->where('usia_bulan', $usiaBulan - 1)
            ->first();

        // Jika bulan lalu tidak datang menimbang, otomatis statusnya "T" (Tidak Naik) menurut Kemenkes
        if (!$pemeriksaanBulanLalu) {
            return [
                'kenaikan_bb' => 'tidak naik',
                'keterangan_bb' => 'Bulan lalu tidak menimbang (Status: T)',
            ];
        }

        $beratLalu = (float) $pemeriksaanBulanLalu->berat_badan;
        $selisih = $beratSekarang - $beratLalu;

        // 3. Evaluasi Target KBM
        if ($selisih >= $targetKbm) {
            return [
                'kenaikan_bb' => 'nair', // disimpan lowercase agar sinkron dengan kondisi warna di blade Anda
                'keterangan_bb' => "Naik (N). Naik +" . ($selisih * 1000) . "g (Target KBM +" . ($targetKbm * 1000) . "g)",
            ];
        } else {
            $pesan = $selisih < 0 
                ? "Tidak Naik (T). BB Turun " . ($selisih * 1000) . "g" 
                : "Tidak Naik (T). Hanya naik +" . ($selisih * 1000) . "g (Kurang dari KBM +" . ($targetKbm * 1000) . "g)";
                
            return [
                'kenaikan_bb' => 'tidak naik',
                'keterangan_bb' => $pesan,
            ];
        }
    }
}