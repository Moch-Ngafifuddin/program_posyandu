<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pasien;

class PantauAnakController extends Controller
{
    // 1. Menampilkan Halaman Pencarian
    public function index()
    {
        return view('pantau.index');
    }

    // 2. Memproses Pencarian
    public function cari(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'tgl_lahir' => 'required|date',
        ]);

        // Cari pasien berdasarkan nama (mirip) dan tanggal lahir (pas)
        $pasien = Pasien::where('nama', 'like', '%' . $request->nama . '%')
            ->where('tgl_lahir', $request->tgl_lahir)
            ->first();

        if (!$pasien) {
            return back()->with('error', 'Maaf, data anak tidak ditemukan. Pastikan Nama dan Tanggal Lahir sesuai.');
        }

        // 🟢 PERBAIKAN: Kunci ID pasien yang berhasil dicari ke dalam Session browser orang tua
        session(['akses_pantau_id' => $pasien->id]);

        return redirect()->route('pantau.detail', $pasien->id);
    }

    // 3. Menampilkan Laporan Lengkap (Mirip Kartu KMS)
    public function detail($id)
    {
        // 🟢 PERBAIKAN: Jika ID di URL berbeda dengan ID di Session, tolak aksesnya!
        if (session('akses_pantau_id') != $id) {
            abort(403, 'Akses Ditolak. Silakan lakukan pencarian data ulang dari halaman awal.');
        }

        // Ambil Data Balita beserta riwayat pemeriksaan bayinya (diurutkan dari yang terbaru)
        $pasien = Pasien::with(['pemeriksaanBayi' => function($query) {
            $query->orderBy('tgl_periksa', 'desc');
        }])->findOrFail($id);

        return view('pantau.detail', compact('pasien'));
    }
}