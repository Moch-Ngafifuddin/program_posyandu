<?php

namespace App\Exports;

use App\Models\PemeriksaanBayi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder; 
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder; 
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PemeriksaanBayiExport extends StringValueBinder implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomValueBinder
{
    private $rowNumber = 0;
    protected $bulan;
    protected $tahun;
    protected $minggu;

    public function __construct($bulan = null, $tahun = null, $minggu = null)
    {
        $this->bulan = $bulan ?? date('m');
        $this->tahun = $tahun ?? date('Y');
        $this->minggu = $minggu;
    }


    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() === 'B' && is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
            },
        ];
    }

    public function collection()
    {
        $subQueryLatest = PemeriksaanBayi::selectRaw('MAX(id) as id')
            ->groupBy('pasien_id');

        $query = PemeriksaanBayi::with(['pasien.posyandu', 'intervensiKlinis'])
            ->whereIn('id', $subQueryLatest)
            ->whereMonth('tgl_periksa', $this->bulan)
            ->whereYear('tgl_periksa', $this->tahun);

        if (!is_null($this->minggu)) {
            $query->whereRaw('WEEK(tgl_periksa, 1) - WEEK(DATE_SUB(tgl_periksa, INTERVAL DAY(tgl_periksa)-1 DAY), 1) + 1 = ?', [$this->minggu]);
        }

        return $query->orderBy('tgl_periksa', 'desc')->get();
    }

    public function headings(): array
    {
        $dataSampel = $this->collection()->first();
        $posyanduMaster = null;

        if ($dataSampel && $dataSampel->pasien && $dataSampel->pasien->posyandu) {
            $posyanduMaster = $dataSampel->pasien->posyandu;
        } elseif (auth()->user() && auth()->user()->posyandu) {
            $posyanduMaster = auth()->user()->posyandu;
        }

        $kabupaten    = $posyanduMaster->kabupaten ?? 'KABUPATEN BANYUMAS';
        $puskesmas    = $posyanduMaster->nama_puskesmas ?? 'UPTD PUSKESMAS PURWOKERTO TIMUR';
        $posyandu     = $posyanduMaster->nama_posyandu ?? 'ANYELIR';
        $desa         = $posyanduMaster->desa_kelurahan ?? 'MERSI';
        $provinsi     = $posyanduMaster->provinsi ?? 'JAWA TENGAH';

        $namaBulan = Carbon::create()->month((int)$this->bulan)->translatedFormat('F');
        $teksPeriode = "BULAN " . strtoupper($namaBulan) . " " . $this->tahun;
        
        if (!is_null($this->minggu)) {
            $teksPeriode .= " (MINGGU KE-" . $this->minggu . ")";
        }

        return [
            ["PEMERINTAH " . strtoupper($kabupaten)],
            ["DINAS KESEHATAN — " . strtoupper($puskesmas)],
            ["REKAPITULASI DATA PERKEMBANGAN DAN KONDISI TERAKHIR BALITA AKTIF"],
            ["Posyandu: {$posyandu} | Desa/Kelurahan: {$desa} | Provinsi: {$provinsi}"],
            ["Periode Laporan: {$teksPeriode} | Waktu Unduh: " . date('d-m-Y H:i') . " WIB"],
            [], 
            [
                'No', 'NIK', 'Nama Balita', 'Jenis Kelamin', 'Tanggal Lahir', 'Orang Tua', 
                'Alamat', 'No Ponsel', 'Kecamatan', 'Desa', 'Usia (Bulan)', 
                'Berat Badan (kg)', 'Tinggi Badan (cm)', 'Status Gizi (BB/U)', 
                'Status Stunting (TB/U)', 'Status BB/TB', 'Kenaikan BB'
            ] 
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:Q1');
        $sheet->mergeCells('A2:Q2');
        $sheet->mergeCells('A3:Q3');
        $sheet->mergeCells('A4:Q4');
        $sheet->mergeCells('A5:Q5');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ],
            3 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ],
            4 => [
                'font' => ['italic' => true, 'size' => 11],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ],
            5 => [
                'font' => ['italic' => true, 'size' => 11],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ],
            7 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFEFEFEF']
                ]
            ],
        ];
    }

    public function map($pemeriksaan): array
    {
        $this->rowNumber++;
        $pasien = $pemeriksaan->pasien;

        return [
            $this->rowNumber,
            $pasien->nik ?? '-', 
            $pasien->nama ?? '-',
            $pasien->jenis_kelamin == 'L' ? 'L' : 'P',
            $pasien->tgl_lahir ? date('d-m-Y', strtotime($pasien->tgl_lahir)) : '-',
            $pasien->nama_ibu ?? $pasien->nama_ayah ?? '-',
            $pasien->alamat ?? '-',
            $pasien->no_hp ?? '-',
            $pasien->kecamatan ?? '-',
            $pasien->desa_kelurahan ?? '-',
            $pemeriksaan->usia_bulan,
            $pemeriksaan->berat_badan,
            $pemeriksaan->tinggi_badan,
            $pemeriksaan->status_gizi,
            $pemeriksaan->status_stunting,
            $pemeriksaan->status_bbtb,
            strtoupper($pemeriksaan->kenaikan_bb == 'naik' ? 'N' : 'T')
        ];
    }
}