<?php

namespace App\Imports;

use App\Models\PemeriksaanBayi;
use App\Models\Pasien;
use App\Models\JadwalPosyandu;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PemeriksaanImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['nik_balita']) || empty($row['tgl_periksa'])) {
            return null;
        }

        $nikMurni = trim($row['nik_balita']);
        $nikHashFinal = hash_hmac('sha256', $nikMurni, config('app.key'));

        $pasien = Pasien::where('nik_hash', $nikHashFinal)->first();
        if (!$pasien) {
            return null; 
        }

        try {
            $tglPeriksa = Carbon::parse($row['tgl_periksa'])->format('Y-m-d');
        } catch (\Exception $e) {
            $tglPeriksa = Carbon::today()->format('Y-m-d');
        }

        $jadwal = JadwalPosyandu::whereDate('tanggal_acara', $tglPeriksa)->first() 
                  ?? JadwalPosyandu::latest()->first();

        $pemeriksaan = PemeriksaanBayi::create([
            'pasien_id'         => $pasien->id,
            'jadwal_id'         => $jadwal?->id ?? null,
            'petugas_id'        => Auth::id(),
            'tgl_periksa'       => $tglPeriksa,
            'usia_bulan'        => !empty($row['usia_bulan']) ? (int)$row['usia_bulan'] : 0,
            'keterangan_umur'   => ($row['usia_bulan'] ?? 0) . ' Bulan',
            'berat_badan'       => !empty($row['berat_badan']) ? (float)$row['berat_badan'] : null,
            'tinggi_badan'      => !empty($row['tinggi_badan']) ? (float)$row['tinggi_badan'] : null,
            'cara_ukur'         => $row['cara_ukur'] ?? 'terlentang',
            'lila'              => !empty($row['lila']) ? (float)$row['lila'] : null,
            'lingkar_kepala'    => !empty($row['lingkar_kepala']) ? (float)$row['lingkar_kepala'] : null,
        ]);

        $pemeriksaan->intervensiKlinis()->create([
            'pitting_edema'   => $row['pitting_edema'] ?? 'tidak ada',
            'vitamin_a'       => isset($row['vitamin_a']) && (strtolower($row['vitamin_a']) == 'ya' || $row['vitamin_a'] == 1) ? 1 : 0,
            'obat_cacing'     => isset($row['obat_cacing']) && (strtolower($row['obat_cacing']) == 'ya' || $row['obat_cacing'] == 1) ? 1 : 0,
            'jenis_imunisasi' => $row['jenis_imunisasi'] ?? null,
            'asi_eksklusif'   => isset($row['asi_eksklusif']) && (strtolower($row['asi_eksklusif']) == 'ya' || $row['asi_eksklusif'] == 1) ? 1 : 0,
            'pmba'            => 0,
            'sdidtk'          => 0,
            'deteksi_tbc'     => 0,
            'kie'             => 0,
            'rujuk'           => 0,
        ]);

        return null;
    }
}