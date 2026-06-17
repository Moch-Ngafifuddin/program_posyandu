<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\RiwayatResource\Pages;
use App\Models\Pasien;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;
use App\Services\LayananFonnte;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class RiwayatResource extends Resource
{
    protected static ?string $model = Pasien::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationLabel = 'Cek Riwayat Pengukuran';

    protected static ?string $navigationGroup = 'Pelayanan';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
        ->modifyQueryUsing(fn ($query) => $query->with('pemeriksaanBayi'))
            ->columns([
                // 1. Kolom No (Nomor Urut Otomatis)
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                // 2. Kolom NIK
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->sortable(),

                // 3. Kolom Nama Pasien
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                // 4. Kolom Jenis Kelamin (JK)
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('JK')
                    ->alignCenter(),

                // 5. Kolom Tanggal Lahir (Format: DD-MM-YYYY)
                Tables\Columns\TextColumn::make('tgl_lahir')
                    ->label('Tgl Lahir')
                    ->date('d-m-Y')
                    ->sortable(),

                // 6. Kolom Nama Orang Tua (Mengambil data nama_ibu dari model pasien)
                Tables\Columns\TextColumn::make('nama_ibu')
                    ->label('Nama Ortu')
                    ->searchable()
                    ->placeholder('-'),

                // 7. Kolom Provinsi (Diambil dinamis dari akun petugas yang sedang login)
                Tables\Columns\TextColumn::make('prov')
                    ->label('Prov')
                    ->state(fn () => auth()->user()?->provinsi ?? '-'),

                // 8. Kolom Kab/Kota (Diambil dinamis dari akun petugas yang sedang login)
                Tables\Columns\TextColumn::make('kab_kota')
                    ->label('Kab/Kota')
                    ->state(fn () => auth()->user()?->kabupaten_kota ?? '-'),

                // 9. Kolom Kecamatan (Diambil dinamis dari akun petugas yang sedang login)
                Tables\Columns\TextColumn::make('kec')
                    ->label('Kec')
                    ->state(fn () => auth()->user()?->kecamatan ?? '-'),

                // 10. Kolom Puskesmas (Diambil dinamis dari akun petugas yang sedang login)
                Tables\Columns\TextColumn::make('puskesmas')
                    ->label('Puskesmas')
                    ->state(fn () => auth()->user()?->nama_puskesmas ?? '-'),

                // 11. Kolom Desa/Kel (Diambil dinamis dari akun petugas yang sedang login)
                Tables\Columns\TextColumn::make('desa_kel')
                    ->label('Desa/Kel')
                    ->state(fn () => auth()->user()?->desa_kelurahan ?? '-'),

                // 12. Kolom Posyandu (Diambil dinamis dari akun petugas yang sedang login)
                Tables\Columns\TextColumn::make('posyandu')
                    ->label('Posyandu')
                    ->state(fn () => auth()->user()?->nama_posyandu ?? '-'),
            ])
            ->actions([
                // Tombol Edit Bawaan
                Tables\Actions\EditAction::make(),
    
                // 1. Action Riwayat Tabel (Sudah Aman & Ringan)
                Tables\Actions\Action::make('lihatRiwayat')
                    ->label('Riwayat Tabel')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->modalHeading(fn (Pasien $record) => "Arsip Rekam Medis: {$record->nama}")
                    ->modalWidth('7xl') 
                    // 🟢 PERBAIKAN 1: Gunakan fungsi penutupan fn() agar data riwayat tidak di-load sebelum diklik
                    ->modalContent(fn (Pasien $record) => view('filament.pages.cek-riwayat', [
                        'pasien' => $record,
                        'pemeriksaan' => $record->pemeriksaanBayi()->orderBy('tgl_periksa', 'desc')->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                // 2. Action Kirim WA (Sudah Defensif & Anti-Freeze)
                Tables\Actions\Action::make('kirim_wa')
                    ->label('Kirim PDF via WA')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->action(function (Pasien $record) {
                        $pemeriksaan = $record->pemeriksaanBayi()->latest('tgl_periksa')->first();
                        
                        if (!$pemeriksaan) {
                            Notification::make()->title('Gagal: Balita belum memiliki data pemeriksaan!')->danger()->send();
                            return;
                        }
                
                        $urlPdf = URL::temporarySignedRoute(
                            'laporan.download', 
                            now()->addDays(2), 
                            ['id' => $pemeriksaan->id]
                        );
                
                        $pesan = "Halo Bunda, laporan perkembangan anak *{$record->nama}* untuk bulan ini sudah diterbitkan oleh pihak Posyandu.\n\n" .
                                 "Bunda dapat mengunduh berkas resmi laporan rekam medis beserta Grafik KMS Elektronik melalui tautan di bawah ini:\n" .
                                 "👉 {$urlPdf}\n\n" .
                                 "_Sistem Informasi Layanan Posyandu Terintegrasi_";
                        
                        $response = LayananFonnte::kirimPesan($record->no_hp, $pesan);
                
                        if (isset($response['status']) && $response['status'] === true) {
                            Notification::make()
                                ->title('Laporan PDF berhasil dikirim ke WhatsApp Orang Tua!')
                                ->success()
                                ->send();
                        } else {
                            $alasan = $response['reason'] ?? 'Koneksi Terputus';
                            Notification::make()
                                ->title('Gagal Kirim WA Gateway')
                                ->description("Penyebab: {$alasan}. Pastikan laptop terhubung internet atau periksa Token Fonnte Anda.")
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
    
                // 3. Action Grafik KMS Balita (🔴 SUMBER UTAMA LOOPING MEMORI SEBELUMNYA)
                Tables\Actions\Action::make('lihatKms')
                    ->label('Grafik KMS')
                    ->modalHeading(fn (Pasien $record) => "Grafik KMS Personal: {$record->nama}")
                    ->icon('heroicon-o-book-open') 
                    ->color('success')
                    ->modalWidth('4xl')
                    // 🟢 PERBAIKAN 2: Bungkus pemanggilan view ke dalam fungsi penutupan murni fn() agar Livewire tidak merender grafik di balik layar sebelum tombol diklik!
                    ->modalContent(fn (Pasien $record) => view('filament.pages.cek-riwayat-layout', [
                        'getRecord' => fn () => $record,
                    ]))
                    ->modalSubmitAction(false) 
                    ->modalCancelActionLabel('Tutup'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRiwayats::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin' || $user->mejaPelayanan?->kode_meja === 'superadmin') {
            return true;
        }
        
        return in_array('riwayats', $user->akses_menu ?? []);
    }
}