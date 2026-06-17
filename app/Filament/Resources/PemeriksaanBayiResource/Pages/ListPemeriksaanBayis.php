<?php

namespace App\Filament\Resources\PemeriksaanBayiResource\Pages;

use App\Filament\Resources\PemeriksaanBayiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPemeriksaanBayis extends ListRecords
{
    protected static string $resource = PemeriksaanBayiResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery()->with(['pasien']);
        return $query->whereDate('tgl_periksa', now()->toDateString());
    }
}