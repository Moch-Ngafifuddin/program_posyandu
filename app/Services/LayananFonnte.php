<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LayananFonnte
{
    public static function kirimPesan($target, $pesan)
    {
        // Standar Internasional: Selalu isolasi kredensial API di config/env, jangan hardcode
        $token = config('services.fonnte.token');

        if (!$token) {
            Log::error('[SECURITY WARNING] Fonnte Token belum diatur di file konfigurasi.');
            return ['status' => false, 'reason' => 'Missing Token'];
        }

        // 1. Pembersihan Nomor Target (Hanya Angka)
        $targetBersih = preg_replace('/[^0-9]/', '', $target);
        
        // 2. Normalisasi Kode Negara Standar Internasional
        if (substr($targetBersih, 0, 1) === '0') {
            $targetBersih = '62' . substr($targetBersih, 1);
        }

        if (strlen($targetBersih) < 10) {
            return ['status' => false, 'reason' => 'Invalid Phone Number Length'];
        }

        try {
            // Request HTTP yang bersih dari duplikasi sintaksis bertumpuk
            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => $token,
            ])
            ->post('https://api.fonnte.com/send', [
                'target' => $targetBersih,
                'message' => $pesan,
                'delay' => '2', 
            ]);

            if ($response->failed()) {
                // Standar Keamanan: Log hanya mencatat alasan error sistem dari Fonnte, bukan PII Pasien
                Log::warning('Fonnte API Gateway merespon eror: ' . $response->status());
                return ['status' => false, 'reason' => 'API Gateway Error'];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Gagal menghubungi API Fonnte (Network Exception): ' . $e->getMessage());
            return ['status' => false, 'reason' => 'Connection Timeout'];
        }
    }
}