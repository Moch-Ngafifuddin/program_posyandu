<?php

namespace App\Filament\Resources\PasienResource\Widgets; 

use Filament\Widgets\ChartWidget;
use App\Models\PemeriksaanBayi;
use App\Models\MasterBbu;
use App\Models\Pasien;

class GrafikKmsPersonal extends ChartWidget
{

    public ?int $pasienId = null;

    protected static ?string $heading = 'KMS Digital: Grafik Perkembangan Berat Badan menurut Umur';
    
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
        // 1. Cari data profil Balita yang sedang dibuka
        $pasien = Pasien::find($this->pasienId);
        
        // Proteksi jika data pasien tidak ditemukan atau belum dipilih
        if (!$pasien) {
            return ['datasets' => [], 'labels' => []];
        }

        $jk = $pasien->jenis_kelamin;

        // 2. Ambil data standar Kemenkes RI sesuai Jenis Kelamin anak dari tabel master
        $masterData = MasterBbu::where('jenis_kelamin', $jk)
            ->whereBetween('umur_bulan', [0, 24]) // Fokus batasan umur emas 0-24 bulan
            ->orderBy('umur_bulan')
            ->get();

        $labels = $masterData->pluck('umur_bulan')->toArray();
        $sd3Minus = $masterData->pluck('minus_3_sd')->toArray();
        $sd2Minus = $masterData->pluck('minus_2_sd')->toArray();
        $median   = $masterData->pluck('median')->toArray();
        $sd1Plus  = $masterData->pluck('plus_1_sd')->toArray();

        // 3. Ambil data TIMBANGAN RIIL milik balita ini saja (Urut dari pemeriksaan pertama)
        $pemeriksaanAnak = PemeriksaanBayi::where('pasien_id', $this->pasienId)
            ->orderBy('tgl_periksa', 'asc')
            ->get();

        // Petakan timbangan anak ke masing-masing kolom umur bulan di grafik
        $dataTimbanganRiil = [];
        foreach ($labels as $bulan) {
            // Cari apakah di bulan ini anak melakukan timbangan
            $periksaBulanIni = $pemeriksaanAnak->firstWhere('usia_bulan', $bulan);
            
            // Jika ada datanya masukkan angkanya, jika tidak set null agar chart melompati garis dengan rapi
            $dataTimbanganRiil[] = $periksaBulanIni ? $periksaBulanIni->berat_badan : null;
        }

        // Tentukan Judul Grafik dinamis sesuai jenis kelamin anak
        static::$heading = "KMS Digital Balita " . ($jk === 'L' ? 'Laki-Laki' : 'Perempuan') . " : {$pasien->nama}";

        return [
            'datasets' => [
                [
                    'label' => 'Berat Badan Anak (Kg)',
                    'data' => $dataTimbanganRiil,
                    'borderColor' => '#000000', // Hitung tebal penanda utama timbangan anak
                    'borderWidth' => 4,
                    'pointBackgroundColor' => '#000000',
                    'pointRadius' => 5,
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Batas Atas (+1 SD)',
                    'data' => $sd1Plus,
                    'borderColor' => '#34d399',
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Garis Ideal (Median)',
                    'data' => $median,
                    'borderColor' => $jk === 'L' ? '#60a5fa' : '#f472b6', // 💡 Trik Visual: Garis median Biru untuk cowok, Pink untuk cewek sesuai standar warna Buku KIA asli!
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Batas Kurang (-2 SD)',
                    'data' => $sd2Minus,
                    'borderColor' => '#f59e0b',
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Garis BGM / Gizi Buruk (-3 SD)',
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