<?php

namespace App\Filament\Resources\PasienResource\Widgets; 

use Filament\Widgets\ChartWidget;
use App\Models\PemeriksaanBayi;
use App\Models\MasterBbtb; // Menggunakan master data BB/TB
use App\Models\Pasien;

class GrafikBbtbPersonal extends ChartWidget
{
    public ?int $pasienId = null;
    protected static ?string $heading = 'Grafik Berat Badan menurut Tinggi Badan (BB/TB)';
    protected static bool $shouldRegisterNavigation = false; 
    protected int | string | array $columnSpan = 'full';

    public function mount(?int $pasienId = null): void
    {
        $this->pasienId = $pasienId;
    }

    public function getType(): string
    {
        return 'line'; 
    }

    protected function getData(): array
    {
        $pasien = Pasien::find($this->pasienId);
        if (!$pasien) {
            return ['datasets' => [], 'labels' => []];
        }

        $jk = $pasien->jenis_kelamin;

        // Pada BB/TB, acuan sumbu X bukan umur, melainkan Rentang Tinggi Anak (Contoh: 65cm sampai 90cm)
        $masterData = MasterBbtb::where('jenis_kelamin', $jk)
            ->whereBetween('tinggi_badan_cm', [65, 95])
            ->orderBy('tinggi_badan_cm')
            ->get();

        $labels = $masterData->pluck('tinggi_badan_cm')->toArray();
        $sd3Minus = $masterData->pluck('minus_3_sd')->toArray();
        $sd2Minus = $masterData->pluck('minus_2_sd')->toArray();
        $median   = $masterData->pluck('median')->toArray();
        $sd1Plus  = $masterData->pluck('plus_1_sd')->toArray();
        $sd2Plus  = $masterData->pluck('plus_2_sd')->toArray(); // Batas Obesitas

        $pemeriksaanAnak = PemeriksaanBayi::where('pasien_id', $this->pasienId)
            ->orderBy('tgl_periksa', 'asc')
            ->get();

        $dataPlotBbtb = [];
        foreach ($labels as $tbAcuan) {
            // Cari data riil anak yang tingginya mendekati pembulatan acuan tabel Kemenkes
            $periksaCocok = $pemeriksaanAnak->first(function($item) use ($tbAcuan) {
                return round($item->tinggi_badan) == $tbAcuan;
            });
            $dataPlotBbtb[] = $periksaCocok ? $periksaCocok->berat_badan : null;
        }

        static::$heading = "Kurva Proporsi Berat terhadap Tinggi Balita " . ($jk === 'L' ? 'Laki-Laki' : 'Perempuan') . " : {$pasien->nama}";

        return [
            'datasets' => [
                [
                    'label' => 'Kondisi Riil Anak',
                    'data' => $dataPlotBbtb,
                    'borderColor' => '#6366f1', // Warna Indigo untuk indikator gizi akut Wasting
                    'borderWidth' => 4,
                    'pointBackgroundColor' => '#6366f1',
                    'pointRadius' => 5,
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Batas Obesitas (+2 SD)',
                    'data' => $sd2Plus,
                    'borderColor' => '#a855f7',
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Batas Atas (+1 SD)',
                    'data' => $sd1Plus,
                    'borderColor' => '#34d399',
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Garis Ideal (Median)',
                    'data' => $median,
                    'borderColor' => $jk === 'L' ? '#60a5fa' : '#f472b6',
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Batas Kurus / Wasting (-2 SD)',
                    'data' => $sd2Minus,
                    'borderColor' => '#f59e0b',
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Sangat Kurus / Gizi Buruk (-3 SD)',
                    'data' => $sd3Minus,
                    'borderColor' => '#ef4444',
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_map(fn($val) => $val . ' Cm', $labels),
        ];
    }
}