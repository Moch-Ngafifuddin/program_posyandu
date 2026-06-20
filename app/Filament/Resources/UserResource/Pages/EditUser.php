<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['posyandu_id']) && $data['posyandu_id']) {
            $masterPosyandu = \App\Models\MasterPosyandu::find($data['posyandu_id']);
            if ($masterPosyandu) {
                $data['provinsi'] = $masterPosyandu->provinsi;
                $data['kabupaten_kota'] = $masterPosyandu->kabupaten_kota;
                $data['kecamatan'] = $masterPosyandu->kecamatan;
                $data['desa_kelurahan'] = $masterPosyandu->desa_kelurahan;
                $data['nama_puskesmas'] = $masterPosyandu->nama_puskesmas;
                $data['nama_posyandu'] = $masterPosyandu->nama_posyandu;
            }
        }
        return $data;
    }
}