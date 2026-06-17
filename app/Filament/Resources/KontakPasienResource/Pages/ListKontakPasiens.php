<?php

namespace App\Filament\Resources\KontakPasienResource\Pages;

use App\Filament\Resources\KontakPasienResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKontakPasiens extends ListRecords
{
    protected static string $resource = KontakPasienResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
