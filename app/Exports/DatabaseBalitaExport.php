<?php

namespace App\Exports;

use App\Models\PemeriksaanBayi;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class DatabaseBalitaExport implements FromQuery, WithHeadings, WithMapping
{
    protected $posyanduId;

    public function __0construct($posyanduId = null)
    {
        $this->posyanduId = $posyanduId;
    }

    /**
     * 🟢 SINKRON: Mengambil query pemeriksaan lengkap dengan Eager Loading relasi baru
     */
    public function query()
    {
        $query = PemeriksaanBayi::query()->with(['pasien.posyandu', 'intervensiKlinis']);

        // Jika user yang login adalah admin posyandu tertentu, filter berdasarkan posyandu mereka
        if ($this->posyanduId) {
            $query->whereHas('pasien', function (Builder $q) {
                $q->where('posyandu_id', $this->posyanduId);
            });
        }

        return $query;
    }

    /**
     * Menentukan judul kolom pada file Excel
     */
    public function headings(): array
    {
        return [
            'No',
            'NIK Balita',
            'Nama Balita',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Nama Ibu',
            'Provinsi',
            'Kabupaten/Kota',
            'Kecamatan',
            'Puskesmas',
            'Desa/Kelurahan',
            'Nama Posyandu',
            'Usia (Bulan)',
            'Berat Badan (Kg)',
            'Tinggi Badan (Cm)',
            'Status Gizi (BB/U)',
            'Status Stunting (TB/U)',
            'Vitamin A',
            'Obat Cacing',
        ];
    }

    /**
     * 🟢 SINKRON: Memetakan baris data sesuai struktur database baru
     */
    public function map($row): array
    {
        static $rowNumber = 0;
        $rowNumber++;

        $pasien = $row->pasien;
        $posyandu = $pasien?->posyandu;
        $intervensi = $row->intervensiKlinis;

        return [
            $rowNumber,
            $pasien?->nik ?? '-',
            $pasien?->nama ?? '-',
            $pasien?->jenis_kelamin ?? '-',
            $pasien?->tgl_lahir ? \Carbon\Carbon::parse($pasien->tgl_lahir)->format('d-m-Y') : '-',
            $pasien?->nama_ibu ?? '-',
            
            // Kolom wilayah diambil dari master_posyandu relasi pasien
            $posyandu?->provinsi ?? '-',
            $posyandu?->kabupaten_kota ?? '-',
            $posyandu?->kecamatan ?? '-',
            $posyandu?->nama_puskesmas ?? '-',
            $posyandu?->desa_kelurahan ?? '-',
            $posyandu?->nama_posyandu ?? '-',
            
            $row->usia_bulan ?? 0,
            $row->berat_badan ?? 0,
            $row->tinggi_badan ?? 0,
            $row->status_gizi ?? '-',
            $row->status_stunting ?? '-',
            
            // Kolom intervensi diambil dari sub-tabel pemeriksaan_intervensi_klinis
            $intervensi?->vitamin_a ? 'Ya' : 'Tidak',
            $intervensi?->obat_cacing ? 'Ya' : 'Tidak',
        ];
    }
}