<?php

namespace App\Filament\Resources\MejaPelayananResource\Pages;

use App\Filament\Resources\MejaPelayananResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMejaPelayanan extends EditRecord
{
    protected static string $resource = MejaPelayananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
