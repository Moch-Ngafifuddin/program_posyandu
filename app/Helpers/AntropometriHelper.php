<?php

namespace App\Helpers;

use App\Models\MasterBbu;
use App\Models\MasterTbu;
use App\Models\MasterBbtb;
use App\Models\PemeriksaanBayi;
use Illuminate\Support\Facades\Cache;

class AntropometriHelper
{
    private static function hitungZScore($nilaiRiil, $master)
    {
        if (!$master) return null;
        
        $nilaiRiil = (float) $nilaiRiil;
        $median = (float) $master->median;

        if ($nilaiRiil == $median) return 0.00;
        
        $m3sd = (float) $master->minus_3_sd;
        $m2sd = (float) $master->minus_2_sd;
        $m1sd = (float) $master->minus_1_sd;
        $p1sd = (float) $master->plus_1_sd;
        $p2sd = (float) $master->plus_2_sd;
        $p3sd = (float) $master->plus_3_sd;

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

    public static function hitungZScoreBBU($jk, $umurBulan, $bb)
    {
        if (!is_numeric($bb) || !is_numeric($umurBulan) || empty($jk)) return null;
        
        $umurBulan = (int) $umurBulan;
        $jkStr = strtoupper(trim($jk));
        
        $bbuCache = Cache::rememberForever('master_bbu_all', function () {
            return MasterBbu::all();
        });

        $master = $bbuCache->firstWhere(function ($item) use ($jkStr, $umurBulan) {
            return strtoupper(trim($item->jenis_kelamin)) === $jkStr && (int) $item->umur_bulan === $umurBulan;
        });
        
        return self::hitungZScore($bb, $master);
    }

    public static function hitungZScoreTBU($jk, $umurBulan, $tb)
    {
        if (!is_numeric($tb) || !is_numeric($umurBulan) || empty($jk)) return null;
        
        $umurBulan = (int) $umurBulan;
        $jkStr = strtoupper(trim($jk));
        
        $tbuCache = Cache::rememberForever('master_tbu_all', function () {
            return MasterTbu::all();
        });

        $master = $tbuCache->firstWhere(function ($item) use ($jkStr, $umurBulan) {
            return strtoupper(trim($item->jenis_kelamin)) === $jkStr && (int) $item->umur_bulan === $umurBulan;
        });

        return self::hitungZScore($tb, $master);
    }

    public static function hitungZScoreBBTB($jk, $tb, $bb)
    {
        if (!is_numeric($tb) || !is_numeric($bb) || empty($jk)) return null;
        
        $jkStr = strtoupper(trim($jk));
        $tbFloat = round((float) $tb, 1); 
        
        $bbtbCache = Cache::rememberForever('master_bbtb_all', function () {
            return MasterBbtb::all();
        });

        $master = $bbtbCache->filter(function($item) use ($jkStr) {
            return strtoupper(trim($item->jenis_kelamin)) === $jkStr;
        })->sortBy(function($item) use ($tbFloat) {
            return abs((float) $item->tinggi_badan_cm - $tbFloat);
        })->first();

        return self::hitungZScore($bb, $master);
    }

    public static function hitungBbu($jk, $umurBulan, $bb)
    {
        $zscore = self::hitungZScoreBBU($jk, $umurBulan, $bb);
        if (is_null($zscore)) return 'Data Master Tidak Ditemukan';
        if ($zscore < -3) return 'Berat Badan Sangat Kurang';
        if ($zscore >= -3 && $zscore < -2) return 'Berat Badan Kurang';
        if ($zscore >= -2 && $zscore <= 1) return 'Berat Badan Normal';
        return 'Risiko Berat Badan Lebih';
    }

    public static function hitungTbu($jk, $umurBulan, $tb)
    {
        $zscore = self::hitungZScoreTBU($jk, $umurBulan, $tb);
        if (is_null($zscore)) return 'Data Master Tidak Ditemukan';
        if ($zscore < -3) return 'Sangat Pendek (Severely Stunted)';
        if ($zscore >= -3 && $zscore < -2) return 'Pendek (Stunted)';
        if ($zscore >= -2 && $zscore <= 3) return 'Normal';
        return 'Tinggi';
    }

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
        $daftarKbm = [1 => 0.8, 2 => 0.9, 3 => 0.8, 4 => 0.6, 5 => 0.5, 6 => 0.4, 7 => 0.3, 8 => 0.3, 9 => 0.3, 10 => 0.3, 11 => 0.3];
        $targetKbm = $usiaBulan >= 12 ? 0.2 : ($daftarKbm[$usiaBulan] ?? 0.2);

        if ($usiaBulan == 0) return ['kenaikan_bb' => 'naik', 'keterangan_bb' => 'Pemeriksaan awal / berat lahir awal (Bulan ke-0)'];

        $pemeriksaanBulanLalu = PemeriksaanBayi::where('pasien_id', $pasienId)->where('usia_bulan', $usiaBulan - 1)->first();
        if (!$pemeriksaanBulanLalu) return ['kenaikan_bb' => 'tidak_naik', 'keterangan_bb' => 'Bulan lalu tidak menimbang (Status: T)'];

        $beratLalu = (float) $pemeriksaanBulanLalu->berat_badan;
        $selisih = $beratSekarang - $beratLalu;

        if ($selisih >= $targetKbm) return ['kenaikan_bb' => 'naik', 'keterangan_bb' => "Naik (N). Naik +" . ($selisih * 1000) . "g (Target KBM +" . ($targetKbm * 1000) . "g)"];
        return ['kenaikan_bb' => 'tidak_naik', 'keterangan_bb' => $selisih < 0 ? "Tidak Naik (T). BB Turun " . ($selisih * 1000) . "g" : "Tidak Naik (T). Hanya naik +" . ($selisih * 1000) . "g (Kurang dari KBM +" . ($targetKbm * 1000) . "g)"];
    }
}