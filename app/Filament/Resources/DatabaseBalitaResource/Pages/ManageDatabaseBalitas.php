<?php

namespace App\Filament\Resources\DatabaseBalitaResource\Pages;

use App\Filament\Resources\DatabaseBalitaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDatabaseBalitas extends ManageRecords
{
    protected static string $resource = DatabaseBalitaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
