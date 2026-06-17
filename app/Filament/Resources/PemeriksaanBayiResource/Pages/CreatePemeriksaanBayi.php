<?php

namespace App\Filament\Resources\PemeriksaanBayiResource\Pages;

use App\Filament\Resources\PemeriksaanBayiResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePemeriksaanBayi extends CreateRecord
{
    protected static string $resource = PemeriksaanBayiResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            // 1. Ambil data jadwal aktif hari ini untuk mengisi kolom wajib jadwal_id baru
            $jadwalHariIni = DB::table('jadwal_posyandu')
                ->whereDate('tanggal_acara', now()->toDateString())
                ->first();

            // 2. Pisahkan data utama pemeriksaan bayi
            $pemeriksaanData = collect($data)->only([
                'pasien_id', 'tgl_periksa', 'keterangan_umur', 'usia_bulan', 
                'berat_badan', 'tinggi_badan', 'cara_ukur', 'lila', 'lingkar_kepala', 
                'status_gizi', 'status_stunting', 'status_bbtb', 'zscore_bbu', 
                'zscore_tbu', 'zscore_bbtb', 'kenaikan_bb', 'keterangan_bb', 
                'rambu_gizi', 'titik_pertumbuhan'
            ])->toArray();

            $pemeriksaanData['jadwal_id'] = $jadwalHariIni ? $jadwalHariIni->id : null;
            $pemeriksaanData['petugas_id'] = auth()->id(); // Logika pengisian ID petugas kader aktif

            $record = static::getModel()::create($pemeriksaanData);

            // 3. Simpan data intervensi klinis ke tabel pecahan barunya (pemeriksaan_intervensi_klinis)
            $record->intervensiKlinis()->create([
                'pitting_edema' => $data['pitting_edema'] ?? 'tidak ada',
                'vitamin_a' => $data['vitamin_a'] ?? 0,
                'obat_cacing' => $data['obat_cacing'] ?? 0,
                'jenis_imunisasi' => isset($data['jenis_imunisasi']) ? implode(', ', $data['jenis_imunisasi']) : null,
                'asi_eksklusif' => $data['asi_eksklusif'] ?? 0,
                'pmba' => $data['pmba'] ?? 0,
                'sdidtk' => $data['sdidtk'] ?? 0,
                'deteksi_tbc' => $data['deteksi_tbc'] ?? 0,
                'kie' => $data['kie'] ?? 0,
                'rujuk' => $data['rujuk'] ?? 0,
                'kelas_ibu' => $data['kelas_ibu'] ?? 0,
                'menerima_mbg' => $data['menerima_mbg'] ?? 0,
                'catatan' => $data['catatan'] ?? null,
            ]);

            return $record;
        });
    }
}