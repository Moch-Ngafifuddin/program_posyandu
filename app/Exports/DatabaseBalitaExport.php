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

    public function map($row): array
    {
        static $rowNumber = 0;
        $rowNumber++;

        $posyandu = $row->posyandu;
        $pemeriksaan = $row->latestPemeriksaan; 
        $intervensi = $pemeriksaan?->intervensiKlinis;

        return [
            $rowNumber,
            "'" . ($row->nik ?? '-'),
            $row->nama ?? '-',
            $row->jenis_kelamin ?? '-',
            $row->tgl_lahir ? \Carbon\Carbon::parse($row->tgl_lahir)->format('d-m-Y') : '-',
            $row->nama_ibu ?? '-',
            
            $posyandu?->provinsi ?? '-',
            $posyandu?->kabupaten_kota ?? '-',
            $posyandu?->kecamatan ?? '-',
            $posyandu?->nama_puskesmas ?? '-',
            $posyandu?->desa_kelurahan ?? '-',
            $posyandu?->nama_posyandu ?? '-',
            
            $pemeriksaan?->usia_bulan ?? 0,
            $pemeriksaan?->berat_badan ?? 0,
            $pemeriksaan?->tinggi_badan ?? 0,
            $pemeriksaan?->status_gizi ?? '-',
            $pemeriksaan?->status_stunting ?? '-',
            
            $intervensi?->vitamin_a ? 'Ya' : 'Tidak',
            $intervensi?->obat_cacing ? 'Ya' : 'Tidak',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}