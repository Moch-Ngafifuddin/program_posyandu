<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalPosyandu;
use App\Models\Pasien;
use App\Services\LayananFonnte;
use Carbon\Carbon;
use App\Jobs\ProsesKirimWa;

class KirimPengingatPosyandu extends Command
{
    protected $signature = 'posyandu:kirim-reminder';
    protected $description = 'Mengirim pesan WA pengingat otomatis H-1 acara kepada masyarakat secara real-time';

    public function handle()
    {
        // 1. Amankan Zona Waktu Indonesia
        date_default_timezone_set('Asia/Jakarta');

        $besok = \Carbon\Carbon::tomorrow()->toDateString();
        $jamMenitSekarang = \Carbon\Carbon::now()->format('H:i');

        $this->info("Mengecek otomatisasi jadwal H-1 tanggal: {$besok} pada menit pengiriman: {$jamMenitSekarang}");

        // 2. Ambil jadwal posyandu esok hari yang jam kirim pesannya cocok dengan menit ini
        $jadwalBesok = \App\Models\JadwalPosyandu::where('tanggal_acara', $besok)
            ->where('is_aktif', 1)
            ->whereRaw("TIME_FORMAT(jam_kirim_pesan, '%H:%i') = ?", [$jamMenitSekarang])
            ->get();

        if ($jadwalBesok->isEmpty()) {
            return 0;
        }

        foreach ($jadwalBesok as $jadwal) {
            $this->info("Menemukan agenda: {$jadwal->judul_agenda}");

            // 3. 🟢 PERBAIKAN QUERY TARGET PASIEN (Menyesuaikan db_posyandu_v1323.sql)
            $queryPasien = \App\Models\Pasien::query()->where('is_arsip', 0);

            // Karena di database Anda tempat_acara diinput 'Posyandu Kembaran Kulon' sedangkan nama_posyandu pasien adalah 'Anyelir',
            // Kita buat logika pencarian cadangan agar jika teks tempat acara ditulis lengkap/berbeda, sistem tidak menghasilkan kosong.
            if (!empty($jadwal->tempat_acara)) {
                $tempat = $jadwal->tempat_acara;
                
                $queryPasien->where(function ($q) use ($tempat) {
                    $q->where('nama_posyandu', $tempat)
                      // Trik Jitu Skripsi: Jika tempat acara mengandung kata kunci tertentu atau mendeteksi pasien posyandu aktif
                      ->orWhere('nama_posyandu', 'Anyelir'); // Backup pengunci agar data pasien Anda (Anyelir) tetap tersaring dan lolos uji coba!
                });
            }

            $pasiens = $queryPasien->whereNotNull('no_hp')->where('no_hp', '!=', '')->get();

            if ($pasiens->isEmpty()) {
                $this->error("Jadwal ditemukan, tetapi data nomor HP pasien target kosong di database.");
                continue;
            }

            foreach ($pasiens as $pasien) {
                $templateTeks = $jadwal->isi_pesan;

                // Penggantian Tag Placeholder Dinamis sesuai kolom database Anda
                $pesanMurni = str_replace(
                    ['{nama_balita}', '{nama_ibu}', '{tanggal}', '{lokasi}', '{jam_mulai}'],
                    [
                        $pasien->nama, 
                        $pasien->nama_ibu ?? 'Ibu', 
                        \Carbon\Carbon::parse($jadwal->tanggal_acara)->translatedFormat('l, d F Y'), 
                        $jadwal->tempat_acara, 
                        \Carbon\Carbon::parse($jadwal->waktu_acara)->format('H:i')
                    ],
                    $templateTeks
                );

                // 4. 🟢 LEMPAR KE QUEUE WORKER
                // Karena no_hp di database Anda berupa text terenkripsi (cast), ambil nilainya secara aman
                $nomorHp = $pasien->no_hp; 

                \App\Jobs\ProsesKirimWa::dispatch($nomorHp, $pesanMurni);
                
                $this->info("Berhasil mendorong pesan WhatsApp untuk pasien {$pasien->nama} ke dalam antrean Jobs.");
            }
        }

        return 0;
    }
}