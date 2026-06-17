<?php

namespace App\Filament\Resources\MejaPelayananResource\Pages;

use App\Filament\Resources\MejaPelayananResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMejaPelayanans extends ListRecords
{
    protected static string $resource = MejaPelayananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
