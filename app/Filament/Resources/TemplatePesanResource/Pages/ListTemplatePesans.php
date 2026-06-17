<?php

namespace App\Filament\Resources\TemplatePesanResource\Pages;

use App\Filament\Resources\TemplatePesanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemplatePesans extends ListRecords
{
    protected static string $resource = TemplatePesanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
