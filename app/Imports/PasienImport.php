<?php

namespace App\Imports;

use App\Models\Pasien;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class PasienImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $tanggalLahir = null;
        
        if (!empty($row['tgl_lahir'])) {
            if (is_numeric($row['tgl_lahir'])) {
                $tanggalLahir = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['tgl_lahir'])->format('Y-m-d');
            } else {
                $tanggalLahir = \Carbon\Carbon::parse($row['tgl_lahir'])->format('Y-m-d');
            }
        }

        $user = auth()->user();

        $tanggalLahir = null;
        if (!empty($row['tgl_lahir'])) {
            if (is_numeric($row['tgl_lahir'])) {
                $tanggalLahir = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['tgl_lahir'])->format('Y-m-d');
            } else {
                $tanggalLahir = \Carbon\Carbon::parse($row['tgl_lahir'])->format('Y-m-d');
            }
        }

        return new Pasien([
            'nik'                       => $row['nik'] ?? null,
            'nik_hash'                  => !empty($row['nik']) ? hash_hmac('sha256', $row['nik'], config('app.key')) : null,
            'no_kk'                     => $row['no_kk'] ?? null,
            'nama'                      => $row['nama'],
            'jenis_kelamin'             => strtoupper($row['jenis_kelamin'] ?? 'L') === 'P' ? 'P' : 'L',
            'tgl_lahir'                 => $tanggalLahir ?? now()->toDateString(), 
            'tempat_lahir'              => $row['tempat_lahir'] ?? '-',
            'alamat'                    => $row['alamat'] ?? '-',
            'rt'                        => !empty($row['rt']) ? (int)$row['rt'] : null,
            'rw'                        => !empty($row['rw']) ? (int)$row['rw'] : null,
            'provinsi'                  => $user?->provinsi ?? '-',
            'kabupaten'                 => $user?->kabupaten_kota ?? '-',
            'kecamatan'                 => $user?->kecamatan ?? '-',
            'desa_kelurahan'            => $user?->desa_kelurahan ?? '-',
            'nama_puskesmas'            => $user?->nama_puskesmas ?? '-',
            'nama_posyandu'             => $user?->nama_posyandu ?? '-',
            'no_hp'                     => $row['no_hp'] ?? null,
            'nama_wali'                 => $row['nama_wali'] ?? null,
            'nama_ayah'                 => $row['nama_ayah'] ?? '-',
            'nik_ayah'                  => $row['nik_ayah'] ?? null,
            'pendidikan_pekerjaan_ayah' => $row['pendidikan_pekerjaan_ayah'] ?? null,
            'nama_ibu'                  => $row['nama_ibu'] ?? '-',
            'nik_ibu'                   => $row['nik_ibu'] ?? null,
            'pendidikan_pekerjaan_ibu'  => $row['pendidikan_pekerjaan_ibu'] ?? null,
            'anak_ke'                   => !empty($row['anak_ke']) ? (int)$row['anak_ke'] : null,
            'usia_kehamilan'            => !empty($row['usia_kehamilan']) ? (int)$row['usia_kehamilan'] : null,
            'berat_lahir'               => !empty($row['berat_lahir']) ? (float)$row['berat_lahir'] : null,
            'panjang_lahir'             => !empty($row['panjang_lahir']) ? (float)$row['panjang_lahir'] : null,
            'lingkar_kepala_lahir'      => !empty($row['lingkar_kepala_lahir']) ? (float)$row['lingkar_kepala_lahir'] : null,
            'imd'                       => strtoupper($row['imd'] ?? 'TIDAK') === 'YA' || $row['imd'] == 1 ? 1 : 0,
            'riwayat_asi'               => $row['riwayat_asi'] ?? null,
            'is_arsip'                  => 0,
        ]);
    }
}