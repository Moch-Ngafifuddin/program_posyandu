<?php

namespace App\Imports;

use App\Models\Pasien;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PasienImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['nama_lengkap']) || empty($row['tgl_lahir'])) {
            return null;
        }

        $tanggalLahir = null;
        if (!empty($row['tgl_lahir'])) {
            if (is_numeric($row['tgl_lahir'])) {
                $tanggalLahir = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['tgl_lahir'])->format('Y-m-d');
            } else {
                $tanggalLahir = Carbon::parse($row['tgl_lahir'])->format('Y-m-d');
            }
        }

        $user = Auth::user();
        $posyanduId = $user?->posyandu_id ?? 1; 

        $pasien = Pasien::create([
            'posyandu_id'               => $posyanduId,
            'nik'                       => $row['nik'] ?? null,
            'no_kk'                     => $row['nomor_kartu_keluarga_kk'] ?? $row['no_kk'] ?? null,
            'nama'                      => $row['nama_lengkap'],
            'jenis_kelamin'             => strtoupper($row['jenis_kelamin'] ?? $row['jk'] ?? 'L') === 'P' ? 'P' : 'L',
            'tgl_lahir'                 => $tanggalLahir,
            'tempat_lahir'              => $row['tempat_lahir'] ?? '-',
            'alamat'                    => $row['alamat_lengkap'] ?? $row['alamat'] ?? '-',
            'rt'                        => $row['rt'] ?? null,
            'rw'                        => $row['rw'] ?? null,
            'no_hp'                     => $row['nomor_whatsapp_aktif'] ?? $row['no_hp'] ?? null,
            
            'nama_ibu'                  => $row['nama_ibu'] ?? '-',
            'nik_ibu'                   => $row['nik_ibu'] ?? null,
            'pendidikan_pekerjaan_ibu'  => $row['pendidikanpekerjaan_ibu'] ?? null,
            'nama_ayah'                 => $row['nama_ayah'] ?? '-',
            'nik_ayah'                  => $row['nik_ayah'] ?? null,
            'pendidikan_pekerjaan_ayah' => $row['pendidikanpekerjaan_ayah'] ?? null,
            'nama_wali'                 => $row['nama_wali_jika_ada'] ?? $row['nama_wali'] ?? null,
            'is_arsip'                  => 0,
        ]);

        $pasien->riwayatKelahiran()->create([
            'anak_ke'              => !empty($row['anak_ke']) ? (int)$row['anak_ke'] : null,
            'usia_kehamilan'       => !empty($row['usia_kehamilan_saat_lahir_minggu']) ? (int)$row['usia_kehamilan_saat_lahir_minggu'] : null,
            'berat_lahir'          => !empty($row['berat_lahir_kg']) ? (float)$row['berat_lahir_kg'] : (!empty($row['berat_lahir']) ? (float)$row['berat_lahir'] : null),
            'panjang_lahir'        => !empty($row['panjang_lahir_cm']) ? (float)$row['panjang_lahir_cm'] : (!empty($row['panjang_lahir']) ? (float)$row['panjang_lahir'] : null),
            'lingkar_kepala_lahir' => !empty($row['lingkar_kepala_lahir_cm']) ? (float)$row['lingkar_kepala_lahir_cm'] : null,
            'imd'                  => isset($row['inisiasi_menyusu_dini_imd']) && (strtolower($row['inisiasi_menyusu_dini_imd']) == 'ya' || $row['inisiasi_menyusu_dini_imd'] == 1) ? 1 : 0,
            'riwayat_asi'          => $row['riwayat_asi_eksklusif'] ?? $row['riwayat_asi'] ?? null,
        ]);

        return null; 
    }
}