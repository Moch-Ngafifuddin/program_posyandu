<?php

namespace App\Filament\Resources\ArsipPasienResource\Pages;

use App\Filament\Resources\ArsipPasienResource;
use Filament\Resources\Pages\ManageRecords;

class ManageArsipPasiens extends ManageRecords
{
    protected static string $resource = ArsipPasienResource::class;
    
    protected function getHeaderActions(): array
    {
        return [];
    }
}