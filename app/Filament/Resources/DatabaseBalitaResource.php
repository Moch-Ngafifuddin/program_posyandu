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
                ->with(['posyandu', 'latestPemeriksaan'])
                ->where('is_arsip', 0)
            )
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->action(function ($livewire) {
                    $records = $livewire->getFilteredTableQuery()->with(['posyandu', 'latestPemeriksaan.intervensiKlinis'])->get();
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
                            Notification::make()->title('File Tidak Ditemukan')->danger()->send();
                            return;
                        }

                        $filePath = $storageDisk->path($data['file_excel']);

                        try {
                            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\PasienImport, $filePath);
                            $storageDisk->delete($data['file_excel']);
                            Notification::make()->title('Proses Import Sukses!')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal Import')->description($e->getMessage())->danger()->send();
                        }
                    }),
    
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->with(['posyandu'])->get();

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
                    ->modalDescription('Masukkan nomor WhatsApp tujuan.')
                    ->action(function ($livewire, array $data) {
                        $baseQuery = $livewire->getFilteredTableQuery();
                
                        $activeFilters = $livewire->tableFilters;
                        $dariTanggal   = $activeFilters['tgl_periksa_range']['dari_tanggal'] ?? null;
                        $sampaiTanggal = $activeFilters['tgl_periksa_range']['sampai_tanggal'] ?? null;
                        $filteredBulan = $dariTanggal ? date('m', strtotime($dariTanggal)) : date('m');
                        $filteredTahun = $dariTanggal ? date('Y', strtotime($dariTanggal)) : date('Y');
                        $linkDownload = route('download.excel.wa', [
                            'bulan' => $filteredBulan,
                            'tahun' => $filteredTahun,
                        ]);
                
                        $total      = (clone $baseQuery)->count();
                        $giziBuruk  = (clone $baseQuery)->whereHas('latestPemeriksaan', fn($sub) => $sub->where('status_gizi', 'Berat Badan Sangat Kurang'))->count();
                        $bbKurang   = (clone $baseQuery)->whereHas('latestPemeriksaan', fn($sub) => $sub->where('status_gizi', 'Berat Badan Kurang'))->count();
                        $giziNormal = (clone $baseQuery)->whereHas('latestPemeriksaan', fn($sub) => $sub->where('status_gizi', 'Berat Badan Normal'))->count();
                        $bbLebih    = (clone $baseQuery)->whereHas('latestPemeriksaan', fn($sub) => $sub->where('status_gizi', 'Risiko Berat Badan Lebih'))->count();
                        $pendek     = (clone $baseQuery)->whereHas('latestPemeriksaan', fn($sub) => $sub->whereIn('status_stunting', ['Pendek (Stunted)', 'Sangat Pendek (Severely Stunted)']))->count();
                
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
                            \Filament\Notifications\Notification::make() 
                                ->title('Berhasil Terkirim!')
                                ->body('Laporan rekapitulasi beserta link download Excel sukses dikirim.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
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
                    ->placeholder('-'),
            
                Tables\Columns\TextColumn::make('posyandu.nama_posyandu')
                    ->label('Posyandu')
                    ->placeholder('-'),
            ])

            ->filters([

                SelectFilter::make('status_gizi')
                    ->label('Kategori Gizi (BB/U)')
                    ->options([
                        'Berat Badan Sangat Kurang' => 'Berat Badan Sangat Kurang',
                        'Berat Badan Kurang' => 'Berat Badan Kurang',
                        'Berat Badan Normal' => 'Berat Badan Normal',
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
                Tables\Actions\Action::make('edit_utama')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->url(fn (\App\Models\Pasien $record): string => 
                        \App\Filament\Resources\PasienResource::getUrl('edit', ['record' => $record->id])
                    ),

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
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') {
            return true;
        }

        return in_array('database-balitas', $user->akses_menu ?? []);
    }
}