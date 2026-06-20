<?php

namespace App\Observers;

use App\Models\Pasien;

class PasienObserver
{
    public function saving(Pasien $pasien): void
    {
        // 🟢 SINKRON: Menyeragamkan metode enkripsi dengan APP_KEY utama proyek
        if ($pasien->isDirty('nik')) {
            $pasien->nik_hash = $pasien->nik ? hash_hmac('sha256', $pasien->nik, config('app.key')) : null;
        }
    }
}