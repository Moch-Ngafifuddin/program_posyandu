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
        date_default_timezone_set('Asia/Jakarta');

        $besok = Carbon::tomorrow()->toDateString();
        $jamMenitSekarang = Carbon::now()->format('H:i');

        $this->info("Mengecek otomatisasi jadwal H-1 tanggal: {$besok} pada menit pengiriman: {$jamMenitSekarang}");

        $jadwalBesok = JadwalPosyandu::where('tanggal_acara', $besok)
            ->where('is_aktif', 1)
            ->whereRaw("TIME_FORMAT(jam_kirim_pesan, '%H:%i') = ?", [$jamMenitSekarang])
            ->get();

        if ($jadwalBesok->isEmpty()) {
            return 0;
        }

        foreach ($jadwalBesok as $jadwal) {
            $this->info("Menemukan agenda: {$jadwal->judul_agenda}");

            $pasiens = Pasien::query()
                ->where('is_arsip', 0)
                ->where('posyandu_id', $jadwal->posyandu_id) 
                ->whereNotNull('no_hp')
                ->where('no_hp', '!=', '')
                ->get();

            if ($pasiens->isEmpty()) {
                $this->error("Jadwal ditemukan, tetapi tidak ada nomor HP pasien terdaftar pada posyandu_id: {$jadwal->posyandu_id}");
                continue;
            }

            foreach ($pasiens as $pasien) {
                $templateTeks = $jadwal->isi_pesan;

                $pesanMurni = str_replace(
                    ['{nama_balita}', '{nama_ibu}', '{tanggal}', '{lokasi}', '{jam_mulai}'],
                    [
                        $pasien->nama, 
                        $pasien->nama_ibu ?? 'Ibu', 
                        Carbon::parse($jadwal->tanggal_acara)->translatedFormat('l, d F Y'), 
                        $jadwal->tempat_acara, 
                        Carbon::parse($jadwal->waktu_acara)->format('H:i')
                    ],
                    $templateTeks
                );

                $nomorHp = $pasien->no_hp; 
                ProsesKirimWa::dispatch($nomorHp, $pesanMurni);
                
                $this->info("Berhasil mendorong pesan WhatsApp untuk pasien {$pasien->nama} ke dalam antrean Jobs.");
            }
        }

        return 0;
    }
}