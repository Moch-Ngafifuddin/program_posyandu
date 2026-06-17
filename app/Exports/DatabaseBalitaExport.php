<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles; 
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DatabaseBalitaExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'No',
            'NIK Balita',
            'Nama Lengkap',
            'JK',
            'Tanggal Lahir',
            'Nama Ortu (Ibu)',
            'Kategori Gizi (BB/U)', 
            'Kategori Stunting (TB/U)', 
            'Provinsi',
            'Kab/Kota',
            'Kecamatan',
            'Puskesmas',
            'Desa/Kel',
            'Posyandu',
        ];
    }

    public function map($row): array
    {
        static $no = 1;

        $tanggalLahir = $row->pasien?->tgl_lahir 
            ? \Carbon\Carbon::parse($row->pasien->tgl_lahir)->format('d-m-Y') 
            : '-';

        return [
            $no++,
            "'" . ($row->pasien?->nik ?? '-'), 
            $row->pasien?->nama ?? '-',
            $row->pasien?->jenis_kelamin ?? '-',
            $tanggalLahir,
            $row->pasien?->nama_ibu ?? '-',
            $row->status_gizi ?? '-', 
            $row->status_stunting ?? '-',
            $row->pasien?->provinsi ?? '-',
            $row->pasien?->kabupaten ?? $row->pasien?->kab_kota ?? '-',
            $row->pasien?->kecamatan ?? '-',
            $row->pasien?->nama_puskesmas ?? '-', 
            $row->pasien?->desa_kelurahan ?? '-', 
            $row->pasien?->nama_posyandu ?? $row->pasien?->posyandu ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}