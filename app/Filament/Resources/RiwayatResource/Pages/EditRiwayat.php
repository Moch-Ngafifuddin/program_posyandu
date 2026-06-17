<?php

namespace App\Filament\Resources\RiwayatResource\Pages;

use App\Filament\Resources\RiwayatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRiwayat extends EditRecord
{
    protected static string $resource = RiwayatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
