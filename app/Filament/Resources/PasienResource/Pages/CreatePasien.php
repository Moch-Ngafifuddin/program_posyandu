<?php

namespace App\Filament\Resources\PasienResource\Pages;

use App\Filament\Resources\PasienResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePasien extends CreateRecord
{
    protected static string $resource = PasienResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['nik'])) {
            $data['nik'] = null;
        }
        return $data;
    }
}