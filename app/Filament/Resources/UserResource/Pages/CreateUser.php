<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    // Redirect ke list setelah buat akun (biar tidak stuck di form)
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}