<?php

namespace App\Http\Controllers;

use App\Models\PemeriksaanBayi;
use App\Models\Pasien;
use App\Models\MasterBbu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Exports\PemeriksaanBayiExport; 
use Maatwebsite\Excel\Facades\Excel;

class LaporanPdfController extends Controller
{

    public function downloadLaporan($id)
    {
        $pemeriksaanUtama = PemeriksaanBayi::with(['pasien.posyandu', 'intervensiKlinis'])->findOrFail($id);
        $pasien = $pemeriksaanUtama->pasien;

        $semuaRiwayat = PemeriksaanBayi::with('intervensiKlinis')
            ->where('pasien_id', $pasien->id)
            ->orderBy('usia_bulan', 'asc')
            ->get();

        $masterKms = MasterBbu::where('jenis_kelamin', $pasien->jenis_kelamin)
            ->whereBetween('umur_bulan', [0, 12]) 
            ->orderBy('umur_bulan', 'asc')
            ->get();

        $pdf = Pdf::loadView('pdf.laporan', [
            'pemeriksaanUtama' => $pemeriksaanUtama,
            'pasien'           => $pasien,
            'pemeriksaan'      => $semuaRiwayat,
            'masterKms'        => $masterKms,
        ]);

        return $pdf->stream("Laporan_Tumbuh_Kembang_{$pasien->nama}.pdf");
    }


    public function downloadExcelWa(Request $request)
    {
        $bulan  = $request->query('bulan');
        $tahun  = $request->query('tahun');
        $minggu = $request->query('minggu');
    
        return Excel::download(
            new \App\Exports\PemeriksaanBayiExport($bulan, $tahun, $minggu), 
            'laporan-pemeriksaan-balita.xlsx'
        );
    }


    public function downloadKmsPersonal($id)
    {
        $pasien = Pasien::findOrFail($id);
        $riwayat = PemeriksaanBayi::where('pasien_id', $id)->orderBy('usia_bulan', 'asc')->get();

        $pdf = Pdf::loadView('pdf.kms_personal', [
            'pasien' => $pasien,
            'riwayat' => $riwayat
        ]);

        return $pdf->download("KMS_Personal_{$pasien->nama}.pdf");
    }
}