<?php

namespace App\Filament\Resources\PasienResource\Pages;

use App\Filament\Resources\PasienResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreatePasien extends CreateRecord
{
    protected static string $resource = PasienResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();

            $pasienData = collect($data)->only([
                'nama', 'nik','nik_hash','no_kk','jenis_kelamin', 'tgl_lahir', 'tempat_lahir', 
                'alamat', 'rt', 'rw', 'no_hp', 'nama_wali', 
                'nama_ayah', 'nik_ayah', 'pendidikan_pekerjaan_ayah', 
                'nama_ibu', 'nik_ibu', 'pendidikan_pekerjaan_ibu'
            ])->toArray();

            $pasienData['posyandu_id'] = $user?->posyandu_id ?? 1; 
            $pasienData['is_arsip'] = 0;

            $record = static::getModel()::create($pasienData);

            $record->riwayatKelahiran()->create([
                'anak_ke' => $data['anak_ke'] ?? null,
                'usia_kehamilan' => $data['usia_kehamilan'] ?? null,
                'berat_lahir' => $data['berat_lahir'] ?? null,
                'panjang_lahir' => $data['panjang_lahir'] ?? null,
                'lingkar_kepala_lahir' => $data['lingkar_kepala_lahir'] ?? null,
                'imd' => $data['imd'] ?? 0,
                'riwayat_asi' => $data['riwayat_asi'] ?? null,
            ]);

            return $record;
        });
    }
}