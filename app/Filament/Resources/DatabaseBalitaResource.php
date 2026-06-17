<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\DatabaseBalitaResource\Pages;
use App\Models\PemeriksaanBayi;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Carbon\Carbon;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Components\FileUpload;
use App\Models\Pasien;


class DatabaseBalitaResource extends Resource
{

    protected static ?string $model = Pasien::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack'; 
    protected static ?string $navigationGroup = 'Pelayanan';
    protected static ?string $navigationLabel = 'Database Balita';
    protected static ?string $pluralModelLabel = 'Database Balita';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['latestPemeriksaan'])
                ->where('is_arsip', 0)
            )
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->with(['pasien'])->get();
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\DatabaseBalitaExport($records), 
                            'database_balita_' . now()->format('Ymd_His') . '.xlsx'
                        );
                    }),

                Tables\Actions\Action::make('import_excel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->modalHeading('Import Data Balita Massal')
                    ->modalDescription('Upload file template excel balita yang kolomnya sudah disesuaikan dengan skema tabel pasien.')
                    ->modalSubmitActionLabel('Proses Enkripsi & Simpan')
                    ->form([
                        FileUpload::make('file_excel')
                            ->label('Pilih Berkas Excel (.xlsx)')
                            ->disk('local') 
                            ->directory('import-temp')
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ]),
                    ])
                    ->action(function (array $data) {
                        $storageDisk = \Illuminate\Support\Facades\Storage::disk('local');
                        
                        if (!$storageDisk->exists($data['file_excel'])) {
                            Notification::make()
                                ->title('File Tidak Ditemukan')
                                ->body('Sistem gagal mendeteksi letak penyimpanan sementara berkas excel.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $filePath = $storageDisk->path($data['file_excel']);

                        try {
                            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\PasienImport, $filePath);

                            $storageDisk->delete($data['file_excel']);

                            Notification::make()
                                ->title('Proses Import Sukses!')
                                ->body('Seluruh data balita berhasil di-enkripsi casting AES-256 dan disimpan.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Melakukan Import')
                                ->body('Terjadi kesalahan format atau database: ' . $e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
    
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->with(['pasien'])->get();

                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.database-balita', [
                            'records' => $records,
                            'tgl_cetak' => now()->format('d-m-Y H:i')
                        ])->setPaper('f4', 'landscape');

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'laporan_database_balita_' . now()->format('Ymd_His') . '.pdf'
                        );
                    }),
    
                Tables\Actions\Action::make('kirim_wa_massal')
                    ->label('Kirim WA')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->form([
                        TextInput::make('nomor_wa_admin')
                            ->label('Nomor WhatsApp Admin Penerima')
                            ->placeholder('Contoh: 08123456789')
                            ->required()
                            ->tel()
                            ->maxLength(15),
                    ])
                    ->modalHeading('Kirim Rekap Database via WhatsApp Gateway')
                    ->modalDescription('Masukkan nomor WhatsApp tujuan. Sistem akan mengirimkan pesan otomatis melalui API Fonnte.')
                    ->modalSubmitActionLabel('Kirim via Fonnte')
                    ->action(function ($livewire, array $data) {
                        $baseQuery = $livewire->getFilteredTableQuery();

                        $activeFilters = $livewire->tableFilters;
                        $statusGizi = $activeFilters['status_gizi']['value'] ?? null;
                        $statusStunting = $activeFilters['status_stunting']['value'] ?? null;
                        $dariTanggal = $activeFilters['tgl_periksa_range']['dari_tanggal'] ?? null;
                        $sampaiTanggal = $activeFilters['tgl_periksa_range']['sampai_tanggal'] ?? null;

                        $linkDownload = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                            'download.excel.wa',
                            now()->addHours(24),
                            [
                                'status_gizi' => $statusGizi,
                                'status_stunting' => $statusStunting,
                                'dari' => $dariTanggal,
                                'sampai' => $sampaiTanggal,
                            ]
                        );

                        $total      = (clone $baseQuery)->count();
                        $giziBuruk  = (clone $baseQuery)->where('status_gizi', 'Gizi Buruk')->count();
                        $bbKurang   = (clone $baseQuery)->where('status_gizi', 'Gizi Kurang')->count();
                        $giziNormal = (clone $baseQuery)->where('status_gizi', 'Gizi Baik (Normal)')->count();
                        $bbLebih    = (clone $baseQuery)->where('status_gizi', 'Risiko Berat Badan Lebih')->count();
                        $pendek     = (clone $baseQuery)->whereIn('status_stunting', ['Pendek', 'Sangat Pendek'])->count();

                        $pesan = "*NOTIFIKASI REKAP DATABASE BALITA*\n";
                        $pesan .= "Tanggal Penarikan: " . now()->format('d-m-Y H:i') . " WIB\n";
                        $pesan .= "Posyandu: " . (auth()->user()?->nama_posyandu ?? '-') . "\n";
                        $pesan .= "----------------------------------------\n";
                        $pesan .= "Jumlah Balita Terfilter: *{$total} Anak*\n\n";

                        $pesan .= "*Rincian Kasus Gizi (BB/U):*\n";
                        $pesan .= "• Gizi Buruk: {$giziBuruk} Anak\n";
                        $pesan .= "• BB Kurang (Gizi Kurang): {$bbKurang} Anak\n";
                        $pesan .= "• BB Normal: {$giziNormal} Anak\n";
                        $pesan .= "• Risiko Obesitas: {$bbLebih} Anak\n\n";

                        $pesan .= "*Rincian Kasus Stunting (TB/U):*\n";
                        $pesan .= "• Pendek/Stunting: {$pendek} Anak\n";
                        $pesan .= "----------------------------------------\n";
                        $pesan .= "*📥 LINK DOWNLOAD DATA MENTAH EXCEL:*\n";
                        $pesan .= $linkDownload . "\n";
                        $pesan .= "----------------------------------------\n";
                        $pesan .= "_Pesan ini otomatis di kirim oleh Sistem Pelayanan Posyandu._";

                        $kirim = \App\Services\LayananFonnte::kirimPesan($data['nomor_wa_admin'], $pesan);

                        if ($kirim) {
                            Notification::make() 
                                ->title('Berhasil Terkirim!')
                                ->body('Laporan rekapitulasi beserta link download Excel sukses dikirim.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Pengiriman Gagal')
                                ->body('Gagal menghubungi API Fonnte. Periksa token Anda di file .env.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter(),
            
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable(query: function ($query, $search) {
                        // Mempertahankan logika pencarian NIK terenkripsi di latar belakang
                        $query->orWhere('nik_hash', hash_hmac('sha256', $search, config('app.key')));
                    })
                    ->fontFamily('mono'),
            
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->weight('semibold'),
            
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('JK')
                    ->alignCenter(),
            
                Tables\Columns\TextColumn::make('tgl_lahir')
                    ->label('Tgl Lahir')
                    ->date('d-m-Y'),
            
                Tables\Columns\TextColumn::make('nama_ibu')
                    ->label('Nama Ibu')
                    ->searchable(),

            
                // 🟢 SINKRONISASI UTAMA: Mengambil data wilayah langsung dari relasi master_posyandu milik pasien
                Tables\Columns\TextColumn::make('posyandu.provinsi')
                    ->label('Prov')
                    ->placeholder('-'),
            
                Tables\Columns\TextColumn::make('posyandu.kabupaten_kota')
                    ->label('Kab/Kota')
                    ->placeholder('-'),
            
                Tables\Columns\TextColumn::make('posyandu.kecamatan')
                    ->label('Kec')
                    ->placeholder('-'),
            
                Tables\Columns\TextColumn::make('posyandu.nama_puskesmas')
                    ->label('Puskesmas')
                    ->placeholder('-'),
            
                Tables\Columns\TextColumn::make('posyandu.desa_kelurahan')
                    ->label('Desa/Kel')
                    ->placeholder('-'),
            
                Tables\Columns\TextColumn::make('posyandu.nama_posyandu')
                    ->label('Posyandu')
                    ->placeholder('-'),
            ])

            ->filters([

                SelectFilter::make('status_gizi')
                    ->label('Kategori Gizi (BB/U)')
                    ->options([
                        'Gizi Buruk' => 'Gizi Buruk',
                        'Gizi Kurang' => 'Gizi Kurang',
                        'Gizi Baik (Normal)' => 'Gizi Baik (Normal)',
                        'Risiko Berat Badan Lebih' => 'Risiko Berat Badan Lebih',
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn ($q, $value) => $q->whereHas('latestPemeriksaan', fn ($sub) => $sub->where('status_gizi', $value))
                    )),

                SelectFilter::make('status_stunting')
                    ->label('Kategori Stunting (TB/U)')
                    ->options([
                        'Sangat Pendek' => 'Sangat Pendek',
                        'Pendek' => 'Pendek',
                        'Normal' => 'Normal',
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn ($q, $value) => $q->whereHas('latestPemeriksaan', fn ($sub) => $sub->where('status_stunting', $value))
                    )),

                Filter::make('tgl_periksa_range')
                    ->label('Periode Pemeriksaan')
                    ->form([
                        DatePicker::make('dari_tanggal')->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $q, $date) => $q->whereHas('latestPemeriksaan', fn ($sub) => $sub->whereDate('tgl_periksa', '>=', $date)),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $q, $date) => $q->whereHas('latestPemeriksaan', fn ($sub) => $sub->whereDate('tgl_periksa', '<=', $date)),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->modalHeading('Ubah Identitas Utama Balita')
                    ->modalWidth('2xl'),

                Tables\Actions\Action::make('hapus_atau_arsip')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading(fn (Pasien $record) => "Manajemen Status Data: {$record->nama}")
                    ->modalWidth('md')
                    ->modalSubmitActionLabel('Konfirmasi & Simpan')
                    ->form([
                        Select::make('status_tindakan')
                            ->label('Alasan Penghapusan / Pengarsipan')
                            ->options([
                                'salah_input' => 'Salah Input (Hapus Permanen)',
                                'pindah' => 'Pindah Domisili / Wilayah (Arsipkan)',
                                'meninggal' => 'Meninggal Dunia (Arsipkan)',
                            ])
                            ->required()
                            ->live(),

                        Placeholder::make('peringatan_salah_input')
                            ->label('⚠️ PERINGATAN KRITIS')
                            ->content('Data balita beserta seluruh riwayat pemeriksaan bulanan akan DIHAPUS PERMANEN dari database.')
                            ->visible(fn (Get $get) => $get('status_tindakan') === 'salah_input'),

                        Textarea::make('keterangan_pindah')
                            ->label('Keterangan Pindah')
                            ->required()
                            ->visible(fn (Get $get) => $get('status_tindakan') === 'pindah'),

                        DatePicker::make('tgl_meninggal')
                            ->label('Tanggal Meninggal')
                            ->required()
                            ->maxDate(now())
                            ->visible(fn (Get $get) => $get('status_tindakan') === 'meninggal'),

                        TextInput::make('tempat_pemakaman')
                            ->label('Tempat Pemakaman')
                            ->required()
                            ->visible(fn (Get $get) => $get('status_tindakan') === 'meninggal'),

                        TextInput::make('penyebab_meninggal')
                            ->label('Penyebab meninggal')
                            ->required()
                            ->visible(fn (Get $get) => $get('status_tindakan') === 'meninggal'),
                    ])
                    ->action(function (Pasien $record, array $data) {
                        switch ($data['status_tindakan']) {
                            case 'salah_input':
                                $record->delete();
                                Notification::make()->title('Berhasil Dihapus secara permanen.')->success()->send();
                                break;

                            case 'pindah':
                                $record->update([
                                    'is_arsip' => 1,
                                    'keterangan_pindah' => $data['keterangan_pindah'],
                                ]);
                                Notification::make()->title('Balita dipindahkan ke arsip pindah domisili.')->success()->send();
                                break;

                            case 'meninggal':
                                $record->update([
                                    'is_arsip' => 1,
                                    'tgl_meninggal' => $data['tgl_meninggal'],
                                    'tempat_pemakaman' => $data['tempat_pemakaman'],
                                    'penyebab_meninggal' => $data['penyebab_meninggal'],

                                ]);
                                Notification::make()->title('Balita dipindahkan ke arsip meninggal dunia.')->success()->send();
                                break;
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDatabaseBalitas::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin' || $user->mejaPelayanan?->kode_meja === 'superadmin') {
            return true;
        }
        
        return in_array('database-balitas', $user->akses_menu ?? []);
    }
}