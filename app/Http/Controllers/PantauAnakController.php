<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pasien;

class PantauAnakController extends Controller
{
    public function index()
    {
        return view('pantau.index');
    }

    public function cari(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'tgl_lahir' => 'required|date',
        ]);

        $pasien = Pasien::where('nama', 'like', '%' . $request->nama . '%')
            ->where('tgl_lahir', $request->tgl_lahir)
            ->first();

        if (!$pasien) {
            return back()->with('error', 'Maaf, data anak tidak ditemukan. Pastikan Nama dan Tanggal Lahir sesuai.');
        }

        session(['akses_pantau_id' => $pasien->id]);

        return redirect()->route('pantau.detail', $pasien->id);
    }

    public function detail($id)
    {
        if (session('akses_pantau_id') != $id) {
            abort(403, 'Akses Ditolak. Silakan lakukan pencarian data ulang dari halaman awal.');
        }

        $pasien = Pasien::with([
            'riwayatKelahiran', 
            'pemeriksaanBayi' => function($query) {
                $query->with('intervensiKlinis')->orderBy('tgl_periksa', 'desc');
            }
        ])->findOrFail($id);

        return view('pantau.detail', compact('pasien'));
    }
}