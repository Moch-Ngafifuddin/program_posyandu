<?php

namespace App\Filament\Resources\PemeriksaanBayiResource\Pages;

use App\Filament\Resources\PemeriksaanBayiResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\Pasien;
use App\Helpers\AntropometriHelper;
use Carbon\Carbon;

class EditPemeriksaanBayi extends EditRecord
{
    protected static string $resource = PemeriksaanBayiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $pasienId = $data['pasien_id'] ?? $this->record->pasien_id;
        $pasien = Pasien::find($pasienId);
        
        if ($pasien) {
            $jk = $pasien->jenis_kelamin;
            $tglLahir = Carbon::parse($pasien->tgl_lahir);
            $tglPeriksa = Carbon::parse($data['tgl_periksa']);
            $umurBulan = $tglLahir->diffInMonths($tglPeriksa);
            
            $data['usia_bulan'] = $umurBulan;

            $data['status_gizi'] = AntropometriHelper::hitungBbu($jk, $umurBulan, $data['berat_badan']);
            $data['status_stunting'] = AntropometriHelper::hitungTbu($jk, $umurBulan, $data['tinggi_badan']);
        }

        return $data;
    }
}