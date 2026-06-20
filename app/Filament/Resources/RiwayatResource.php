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
            ->modifyQueryUsing(fn ($query) => $query->with(['riwayatKelahiran', 'posyandu'])) 
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('JK')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('tgl_lahir')
                    ->label('Tgl Lahir')
                    ->date('d-m-Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_ibu')
                    ->label('Nama Ortu')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('provinsi')
                    ->label('Prov')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('kabupaten')
                    ->label('Kab/Kota')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('kecamatan')
                    ->label('Kec')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('desa_kelurahan')
                    ->label('Desa/Kel')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('posyandu.nama_puskesmas')
                    ->label('Puskesmas')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('posyandu.nama_posyandu')
                    ->label('Posyandu')
                    ->searchable()
                    ->placeholder('-'),
            ])
    
            ->actions([

                Tables\Actions\Action::make('lihatRiwayat')
                    ->label('Riwayat Tabel')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->modalHeading(fn (Pasien $record) => "Arsip Rekam Medis: {$record->nama}")
                    ->modalWidth('7xl') 
                    ->modalContent(fn (Pasien $record) => view('filament.pages.cek-riwayat', [
                        'pasien' => $record,
                        'pemeriksaan' => $record->pemeriksaanBayi()->orderBy('tgl_periksa', 'desc')->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

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
    
                Tables\Actions\Action::make('lihatKms')
                    ->label('Grafik KMS')
                    ->modalHeading(fn (Pasien $record) => "Grafik KMS Personal: {$record->nama}")
                    ->icon('heroicon-o-book-open') 
                    ->color('success')
                    ->modalWidth('4xl')
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
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') {
            return true;
        }

        return in_array('riwayats', $user->akses_menu ?? []);
    }
}