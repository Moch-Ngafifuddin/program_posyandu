<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\PemeriksaanBayi;

class GrafikDonatGizi extends ChartWidget
{
    protected static ?string $heading = 'Persentase Status Gizi & Stunting Balita';
    protected int | string | array $columnSpan = 1; 

    public function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
            'aspectRatio' => 2,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        // 🚀 Ambil ID pemeriksaan terbaru via SQL Grouping
        $pemeriksaanTerbaruIds = PemeriksaanBayi::selectRaw('MAX(id) as id')
            ->groupBy('pasien_id')
            ->pluck('id');

        // 🚀 OPTIMASI MUTLAK: Hitung semua data donat dalam 1 baris SQL Query saja
        $counts = PemeriksaanBayi::whereIn('id', $pemeriksaanTerbaruIds)
            ->selectRaw("
                SUM(CASE WHEN status_stunting IN ('Sangat Pendek (Severely Stunted)', 'Pendek (Stunted)') THEN 1 ELSE 0 END) as stunting,
                SUM(CASE WHEN status_gizi = 'Berat Badan Sangat Kurang' THEN 1 ELSE 0 END) as gizi_buruk,
                SUM(CASE WHEN status_gizi = 'Berat Badan Kurang' THEN 1 ELSE 0 END) as bb_kurang,
                SUM(CASE WHEN status_gizi = 'Berat Badan Normal' THEN 1 ELSE 0 END) as gizi_normal,
                SUM(CASE WHEN status_gizi = 'Risiko Berat Badan Lebih' THEN 1 ELSE 0 END) as obesitas
            ")->first();

        return [
            'datasets' => [
                [
                    'label' => 'Persentase Balita',
                    'data' => [
                        (int) ($counts->stunting ?? 0), 
                        (int) ($counts->gizi_buruk ?? 0), 
                        (int) ($counts->bb_kurang ?? 0), 
                        (int) ($counts->gizi_normal ?? 0), 
                        (int) ($counts->obesitas ?? 0)
                    ],
                    'backgroundColor' => [
                        '#ef4444', // Stunting
                        '#b91c1c', // Gizi Buruk
                        '#f59e0b', // BB Kurang
                        '#10b981', // Gizi Normal
                        '#3b82f6', // Risiko Obesitas
                    ],
                ],
            ],
            'labels' => [
                'Stunting (Pendek)', 
                'Gizi Buruk', 
                'Berat Badan Kurang', 
                'Gizi Normal', 
                'Risiko Obesitas'
            ],
        ];
    }
}