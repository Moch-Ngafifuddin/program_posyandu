<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PemeriksaanExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
            'Tanggal Periksa',
            'Usia (Bulan)',
            'Berat Badan (Kg)',
            'Tinggi Badan (Cm)',
            'Cara Ukur',
            'LiLA (Cm)',
            'Lingkar Kepala (Cm)',
            'Status Gizi (BB/U)',
            'Status Stunting (TB/U)',
            'Status BB/TB',
            'Kenaikan BB',
            'Catatan/KIE'
        ];
    }

    public function map($row): array
    {
        static $no = 1;

        return [
            $no++,
            "'" . ($row->pasien?->nik ?? '-'), 
            $row->pasien?->nama ?? '-',
            \Carbon\Carbon::parse($row->tgl_periksa)->format('d-m-Y'),
            $row->usia_bulan,
            $row->berat_badan,
            $row->tinggi_badan,
            $row->cara_ukur,
            $row->lila,
            $row->lingkar_kepala,
            $row->status_gizi,
            $row->status_stunting,
            $row->status_bbtb,
            $row->kenaikan_bb,
            $row->intervensiKlinis?->catatan ?? '-' 
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}