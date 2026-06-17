<?php

namespace App\Filament\Resources\PasienResource\Pages;

use App\Filament\Resources\PasienResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePasien extends CreateRecord
{
    protected static string $resource = PasienResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            // 1. Cari atau buat ID Posyandu berdasarkan nama_posyandu yang di-input/default otomatis
            $posyandu = DB::table('master_posyandu')
                ->where('nama_posyandu', $data['nama_posyandu'] ?? 'Anyelir')
                ->first();

            // 2. Petakan data ke struktur tabel pasien utama yang baru
            $pasienData = collect($data)->only([
                'nama', 'jenis_kelamin', 'tgl_lahir', 'tempat_lahir', 
                'alamat', 'rt', 'rw', 'no_hp', 'nama_wali', 
                'nama_ayah', 'nik_ayah', 'pendidikan_pekerjaan_ayah', 
                'nama_ibu', 'nik_ibu', 'pendidikan_pekerjaan_ibu'
            ])->toArray();

            $pasienData['posyandu_id'] = $posyandu ? $posyandu->id : 1; // Default ke ID 1 jika tidak ketemu
            $pasienData['nik_hash'] = $data['nik'] ? hash_hmac('sha256', $data['nik'], config('app.key')) : null;
            $pasienData['nik'] = $data['nik']; // Enkripsi ditangani otomatis oleh cast di model

            // Simpan record utama pasien
            $record = static::getModel()::create($pasienData);

            // 3. Alihkan data Riwayat Kelahiran ke tabel terpisah baru (pasien_riwayat_kelahiran)
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