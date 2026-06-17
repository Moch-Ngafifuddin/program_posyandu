<?php

namespace App\Imports;

use App\Models\PemeriksaanBayi;
use App\Models\Pasien;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

    class PemeriksaanImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // 1. Validasi baris kosong kritis
        if (empty($row['nik_balita']) || empty($row['tgl_periksa'])) {
            return null;
        }

        // 2. Normalisasi NIK dari baris Excel
        $nikMurni = trim($row['nik_balita']);
        
        // 🟢 PERBAIKAN MUTLAK: Menggunakan hash_hmac + app.key agar sinkron 100% dengan database skripsi Anda!
        $nikHashFinal = hash_hmac('sha256', $nikMurni, config('app.key'));

        // Cari data pasien berdasarkan hash hmac yang sah
        $pasien = Pasien::where('nik_hash', $nikHashFinal)->first();
        
        // Jika masih tidak ditemukan, kita buat sistem fallback ke MD5/SHA murni untuk berjaga-jaga
        if (!$pasien) {
            $pasien = Pasien::where('nik_hash', md5($nikMurni))
                ->orWhere('nik_hash', hash('sha256', $nikMurni))
                ->first();
        }
        
        // Jika semua metode pencarian sidik jari NIK tetap buntu, lemparkan notifikasi informatif
        if (!$pasien) {
            throw new \Exception("Gagal Import: NIK Anak '{$nikMurni}' belum terdaftar di menu Input Balita Baru. Silakan daftarkan anak ini terlebih dahulu ke sistem.");
        }

        // 3. Normalisasi Tanggal Periksa
        $tglPeriksa = null;
        try {
            $tglPeriksa = Carbon::parse($row['tgl_periksa'])->format('Y-m-d');
        } catch (\Exception $e) {
            $tglPeriksa = Carbon::today()->format('Y-m-d');
        }

        // 4. Masukkan rekam medis bulanan ke tabel pemeriksaan_bayi
        return new PemeriksaanBayi([
            'pasien_id'         => $pasien->id,
            'tgl_periksa'       => $tglPeriksa,
            'usia_bulan'        => !empty($row['usia_bulan']) ? (int)$row['usia_bulan'] : 0,
            'keterangan_umur'   => ($row['usia_bulan'] ?? 0) . ' Bulan',
            'berat_badan'       => !empty($row['berat_badan']) ? (float)$row['berat_badan'] : null,
            'tinggi_badan'      => !empty($row['tinggi_badan']) ? (float)$row['tinggi_badan'] : null,
            'cara_ukur'         => $row['cara_ukur'] ?? 'terlentang',
            'lila'              => !empty($row['lila']) ? (float)$row['lila'] : null,
            'lingkar_kepala'    => !empty($row['lingkar_kepala']) ? (float)$row['lingkar_kepala'] : null,
            'status_gizi'       => $row['status_gizi'] ?? 'Gizi Baik (Normal)',
            'status_stunting'   => $row['status_stunting'] ?? 'Normal',
            'status_bbtb'       => $row['status_bbtb'] ?? 'Gizi Baik (Normal)',
            'kenaikan_bb'       => $row['kenaikan_bb'] ?? 'naik',
            'catatan'           => $row['catatan'] ?? '-',
        ]);
    }
}