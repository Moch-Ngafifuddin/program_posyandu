<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PantauAnakController;
use App\Http\Controllers\LaporanPdfController;

// Rute Pencarian & Pantau Anak (Publik)
Route::get('/pantau', [PantauAnakController::class, 'index'])->name('pantau.index');
Route::post('/pantau/cari', [PantauAnakController::class, 'cari'])->name('pantau.cari');
Route::get('/pantau/{id}', [PantauAnakController::class, 'detail'])->name('pantau.detail');

// Rute Export & Laporan PDF (Khusus Link Resmi / Bertanda Tangan)
Route::get('/laporan/download/{id}', [LaporanPdfController::class, 'downloadLaporan'])
    ->name('laporan.download')
    ->middleware('signed');

Route::get('/download-excel-wa', [LaporanPdfController::class, 'downloadExcelWa'])
    ->name('download.excel.wa')
    ->middleware('signed'); 

Route::get('/download-kms-personal/{id}', [LaporanPdfController::class, 'downloadKmsPersonal'])
    ->name('kms.personal.download');