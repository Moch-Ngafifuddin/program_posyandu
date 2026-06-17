<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Pasien;
use App\Models\PemeriksaanBayi;
use App\Filament\Resources\PemeriksaanBayiResource;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {

        $pemeriksaanTerbaruIds = PemeriksaanBayi::selectRaw('MAX(id) as id')
            ->groupBy('pasien_id')
            ->pluck('id');


        $totalBalitaAktif = Pasien::where('is_arsip', 0)->count();


        $metrics = PemeriksaanBayi::whereIn('id', $pemeriksaanTerbaruIds)
            ->selectRaw("
                SUM(CASE WHEN status_gizi = 'Berat Badan Sangat Kurang' THEN 1 ELSE 0 END) as gizi_buruk,
                SUM(CASE WHEN status_gizi = 'Berat Badan Kurang' THEN 1 ELSE 0 END) as bb_kurang,
                SUM(CASE WHEN status_gizi = 'Berat Badan Normal' THEN 1 ELSE 0 END) as gizi_normal,
                SUM(CASE WHEN status_gizi = 'Risiko Berat Badan Lebih' THEN 1 ELSE 0 END) as gizi_lebih,
                SUM(CASE WHEN status_stunting IN ('Sangat Pendek (Severely Stunted)', 'Pendek (Stunted)') THEN 1 ELSE 0 END) as stunting
            ")->first();

        $resourceUrl = PemeriksaanBayiResource::getUrl('index');

        return [
            // 1. STATISTIK TOTAL BALITA AKTIF
            Stat::make('Total Balita Aktif', $totalBalitaAktif . ' Anak')
                ->description('Semua balita terdaftar aktif')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->url(route('filament.admin.resources.database-balitas.index')),
        
            // 2. STATISTIK BALITA STUNTING
            Stat::make('Balita Stunting', ($metrics->stunting ?? 0) . ' Anak')
                ->description('Kategori Pendek & Sangat Pendek')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color(($metrics->stunting ?? 0) > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.database-balitas.index', [
                    'tableFilters[status_stunting][value]' => 'Pendek'
                ])),
        
            // 3. STATISTIK GIZI BURUK
            Stat::make('Gizi Buruk', ($metrics->gizi_buruk ?? 0) . ' Anak')
                ->description('Berat Badan Sangat Kurang')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(($metrics->gizi_buruk ?? 0) > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.database-balitas.index', [
                    'tableFilters[status_gizi][value]' => 'Gizi Buruk'
                ])),
        
            // 4. STATISTIK BERAT BADAN KURANG
            Stat::make('Berat Badan Kurang', ($metrics->bb_kurang ?? 0) . ' Anak')
                ->description('Kategori BB Kurang')
                ->descriptionIcon('heroicon-m-minus-circle')
                ->color(($metrics->bb_kurang ?? 0) > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.database-balitas.index', [
                    'tableFilters[status_gizi][value]' => 'Gizi Kurang' 
                ])),
        
            // 5. STATISTIK GIZI NORMAL
            Stat::make('Gizi Normal', ($metrics->gizi_normal ?? 0) . ' Anak')
                ->description('Berat Badan Sehat & Ideal')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(route('filament.admin.resources.database-balitas.index', [
                    'tableFilters[status_gizi][value]' => 'Gizi Baik (Normal)'
                ])),
        
            // 6. STATISTIK RISIKO OBESITAS
            Stat::make('Risiko Obesitas', ($metrics->gizi_lebih ?? 0) . ' Anak')
                ->description('Risiko Berat Badan Lebih')
                ->descriptionIcon('heroicon-m-plus')
                ->color(($metrics->gizi_lebih ?? 0) > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.database-balitas.index', [
                    'tableFilters[status_gizi][value]' => 'Risiko Berat Badan Lebih'
                ])),
        ];
    }
}