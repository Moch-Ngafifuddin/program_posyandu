<?php

namespace App\Http\Controllers;

use App\Models\PemeriksaanBayi;
use App\Models\Pasien;
use App\Models\MasterBbu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LaporanPdfController extends Controller
{
    /**
     * 🟢 SINKRON: Mengunduh Laporan Resmi Bulanan + Grafik KMS PDF
     */
    public function downloadLaporan($id)
    {
        // 1. Ambil data pemeriksaan spesifik berdasarkan ID yang dikirim dari WhatsApp
        $pemeriksaanUtama = PemeriksaanBayi::with('pasien')->findOrFail($id);
        $pasien = $pemeriksaanUtama->pasien;

        // 2. Ambil seluruh riwayat pertumbuhan anak ini untuk membentuk titik kurva KMS
        $semuaRiwayat = PemeriksaanBayi::where('pasien_id', $pasien->id)
            ->orderBy('usia_bulan', 'asc')
            ->get();

        // 3. Ambil Master Data Standar Antropometri Kemenkes (BB/U) sesuai jenis kelamin anak
        $masterKms = MasterBbu::where('jenis_kelamin', $pasien->jenis_kelamin)
            ->whereBetween('umur_bulan', [0, 12]) // Batasi 0-12 bulan untuk efisiensi kertas laporan
            ->orderBy('umur_bulan', 'asc')
            ->get();

        // 4. Muat view PDF khusus yang didesain menggunakan CSS murni (Anti-Crash DomPDF)
        $pdf = Pdf::loadView('pdf.laporan', [
            'pemeriksaanUtama' => $pemeriksaanUtama,
            'pasien'           => $pasien,
            'pemeriksaan'      => $semuaRiwayat,
            'masterKms'        => $masterKms,
        ]);

        $pdf->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true // Mengizinkan pembacaan logo lokal
        ]);

        return $pdf->stream('Laporan_Tumbuh_Kembang_' . str_replace(' ', '_', $pasien->nama) . '.pdf');
    }
}