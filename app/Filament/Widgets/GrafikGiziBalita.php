<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\PemeriksaanBayi;

class GrafikGiziBalita extends ChartWidget
{
    protected static ?string $heading = 'Grafik Distribusi Kondisi Gizi per Kelompok Umur';
    protected int | string | array $columnSpan = 1;

    public function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'x' => ['stacked' => true],
                'y' => [
                    'stacked' => true,
                    'ticks' => ['stepSize' => 1],
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

        // 🚀 OPTIMASI: Tarik data ringkas dari ID terpilih sekali saja ke memori Collection
        $pemeriksaanData = PemeriksaanBayi::whereIn('id', $pemeriksaanTerbaruIds)
            ->select('usia_bulan', 'status_gizi')
            ->get();

        $kelompokUmur = [
            '0-11 Bulan'  => [0, 11],
            '12-23 Bulan' => [12, 23],
            '24-35 Bulan' => [24, 35],
            '36-59 Bulan' => [36, 59],
        ];

        $dataNormal = [];
        $dataBermasalah = [];

        foreach ($kelompokUmur as $label => $range) {
            // 🚀 FILTER LANGSUNG DI MEMORI RAM (Tanpa Hit Database Lagi!)
            $filteredByAge = $pemeriksaanData->whereBetween('usia_bulan', [$range[0], $range[1]]);

            $dataNormal[] = $filteredByAge->where('status_gizi', 'Berat Badan Normal')->count();

            $dataBermasalah[] = $filteredByAge->whereIn('status_gizi', [
                'Berat Badan Sangat Kurang',
                'Berat Badan Kurang',
                'Risiko Berat Badan Lebih'
            ])->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'BB Normal / Ideal',
                    'data' => $dataNormal,
                    'backgroundColor' => '#34d399',
                ],
                [
                    'label' => 'BB Bermasalah (Kurang/Sangat Kurang/Lebih)',
                    'data' => $dataBermasalah,
                    'backgroundColor' => '#f87171',
                ],
            ],
            'labels' => array_keys($kelompokUmur),
        ];
    }
}