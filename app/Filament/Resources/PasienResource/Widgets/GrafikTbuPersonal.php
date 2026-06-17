<?php

namespace App\Filament\Resources\PasienResource\Widgets; 

use Filament\Widgets\ChartWidget;
use App\Models\PemeriksaanBayi;
use App\Models\MasterTbu; // Menggunakan master data TB/U
use App\Models\Pasien;

class GrafikTbuPersonal extends ChartWidget
{
    public ?int $pasienId = null;
    protected static ?string $heading = 'Grafik Panjang/Tinggi Badan menurut Umur (TB/U)';
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

        // Ambil data acuan standar stunting Kemenkes dari tabel master TB/U
        $masterData = MasterTbu::where('jenis_kelamin', $jk)
            ->whereBetween('umur_bulan', [0, 24])
            ->orderBy('umur_bulan')
            ->get();

        $labels = $masterData->pluck('umur_bulan')->toArray();
        $sd3Minus = $masterData->pluck('minus_3_sd')->toArray();
        $sd2Minus = $masterData->pluck('minus_2_sd')->toArray();
        $median   = $masterData->pluck('median')->toArray();
        $sd3Plus  = $masterData->pluck('plus_3_sd')->toArray(); // Batas tinggi (Macrocephaly/Tinggi)

        $pemeriksaanAnak = PemeriksaanBayi::where('pasien_id', $this->pasienId)
            ->orderBy('tgl_periksa', 'asc')
            ->get();

        $dataTinggiRiil = [];
        foreach ($labels as $bulan) {
            $periksaBulanIni = $pemeriksaanAnak->firstWhere('usia_bulan', $bulan);
            $dataTinggiRiil[] = $periksaBulanIni ? $periksaBulanIni->tinggi_badan : null;
        }

        static::$heading = "Kurva Pertumbuhan Tinggi Badan Balita " . ($jk === 'L' ? 'Laki-Laki' : 'Perempuan') . " : {$pasien->nama}";

        return [
            'datasets' => [
                [
                    'label' => 'Tinggi Badan Anak (Cm)',
                    'data' => $dataTinggiRiil,
                    'borderColor' => '#10b981', // Hijau Emerald tebal untuk garis riil anak
                    'borderWidth' => 4,
                    'pointBackgroundColor' => '#10b981',
                    'pointRadius' => 5,
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Batas Atas (+3 SD)',
                    'data' => $sd3Plus,
                    'borderColor' => '#3b82f6',
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Garis Ideal (Median)',
                    'data' => $median,
                    'borderColor' => $jk === 'L' ? '#60a5fa' : '#f472b6', // Biru vs Pink standar KIA
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Batas Pendek / Stunting (-2 SD)',
                    'data' => $sd2Minus,
                    'borderColor' => '#f59e0b',
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Sangat Pendek / Gizi Buruk Kronis (-3 SD)',
                    'data' => $sd3Minus,
                    'borderColor' => '#ef4444',
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_map(fn($val) => 'Bln ' . $val, $labels),
        ];
    }
}