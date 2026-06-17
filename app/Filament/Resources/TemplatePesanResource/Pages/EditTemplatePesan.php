<?php

namespace App\Filament\Resources\TemplatePesanResource\Pages;

use App\Filament\Resources\TemplatePesanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTemplatePesan extends EditRecord
{
    protected static string $resource = TemplatePesanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
