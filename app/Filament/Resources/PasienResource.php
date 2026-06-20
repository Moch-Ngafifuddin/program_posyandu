<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PasienResource\Pages;
use App\Models\Pasien;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;
use App\Observers\PasienObserver;

class PasienResource extends Resource
{
    protected static ?string $model = Pasien::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'Input Data Balita Baru';
    protected static ?string $pluralModelLabel = 'Data Balita';
    protected static ?string $navigationGroup = 'Pelayanan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Utama Balita')
                    ->schema([
                        Forms\Components\TextInput::make('nama')->label('Nama Lengkap Balita')->placeholder('Maylani Az Zahrah')->required(),
                        Forms\Components\Radio::make('jenis_kelamin')->label('Jenis Kelamin')->options(['L' => 'Laki-laki', 'P' => 'Perempuan'])->inline()->required(),
                        Forms\Components\TextInput::make('tempat_lahir')->label('Tempat Lahir')->placeholder('Banyumas')->required(),
                        Forms\Components\DatePicker::make('tgl_lahir')->label('Tanggal Lahir')->required()->native(false)->placeholder('1 Juni 2026'),
                        Forms\Components\TextInput::make('no_kk')->label('Nomor Kartu Keluarga (KK)')->required()->numeric()->maxLength(16)->placeholder('3307191902665551'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('nik')
                                ->label('Nomor NIK Anak')
                                ->placeholder('3304718102998882')
                                ->numeric()
                                ->maxLength(16)
                                ->required(fn (Get $get) => !$get('belum_punya_nik'))
                                ->disabled(fn (Get $get) => $get('belum_punya_nik'))
                                ->dehydrated(),
                            Forms\Components\Checkbox::make('belum_punya_nik')
                                ->label('Anak belum memiliki NIK')
                                ->live()
                                ->dehydrated(false) 
                        ]),
                    ])->columns(2),

                    Forms\Components\Group::make()
                        ->relationship('riwayatKelahiran') 
                        ->columnSpanFull()
                        ->schema([
                            Forms\Components\Section::make('Data Kelahiran & Pengukuran Awal (Buku KIA)')
                                ->description('Data ini tersimpan secara aman di dalam sub-tabel riwayat kelahiran pasien.')
                                ->schema([
                                    
                                    Forms\Components\TextInput::make('anak_ke')
                                        ->label('Anak Ke-')
                                        ->placeholder('2')
                                        ->numeric(),
                                        
                                    Forms\Components\TextInput::make('usia_kehamilan')
                                        ->label('Usia Kehamilan Saat Lahir (Minggu)')
                                        ->placeholder('36')
                                        ->numeric(),
                                        
                                    Forms\Components\TextInput::make('berat_lahir')
                                        ->label('Berat Lahir (Kg)')
                                        ->placeholder('2.4')
                                        ->numeric()
                                        ->live()
                                        ->helperText('Gunakan titik, misal: 2.4'),
                                        
                                    Forms\Components\TextInput::make('panjang_lahir')
                                        ->label('Panjang Lahir (Cm)')
                                        ->placeholder('60')
                                        ->numeric(),
                                        
                                    Forms\Components\TextInput::make('lingkar_kepala_lahir')
                                        ->label('Lingkar Kepala Lahir (Cm)')
                                        ->placeholder('30')
                                        ->numeric(),
                                        
                                    Forms\Components\Toggle::make('imd')
                                        ->label('Inisiasi Menyusu Dini (IMD)')
                                        ->inline(false),
                                        
                                    Forms\Components\Select::make('riwayat_asi')
                                        ->label('Riwayat ASI Eksklusif')
                                        ->options([
                                            'E1' => 'E1 (ASI Eksklusif 1 Bulan)',
                                            'E2' => 'E2 (ASI Eksklusif 2 Bulan)',
                                            'E3' => 'E3 (ASI Eksklusif 3 Bulan)',
                                            'E4' => 'E4 (ASI Eksklusif 4 Bulan)',
                                            'E5' => 'E5 (ASI Eksklusif 5 Bulan)',
                                            'E6' => 'E6 (ASI Eksklusif 6 Bulan)',
                                        ]),
                                ])->columnSpanFull(),
                        ]),

                        Forms\Components\Fieldset::make('Tatalaksana BBLR & Prematur')
                            ->visible(fn (Get $get) => is_numeric($get('berat_lahir')) && (float) $get('berat_lahir') < 2.5)
                            ->schema([
                                Forms\Components\Checkbox::make('buku_kia_bayi_kecil')->label('Mendapatkan Buku KIA Bayi Kecil (BBLR & Prematur)'),
                                Forms\Components\Checkbox::make('tatalaksana_bblr')->label('Mendapatkan Tatalaksana BBLR'),
                            ])->columns(1),
                            

                Forms\Components\Section::make('Informasi Orang Tua & Wilayah')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('nama_ibu')->label('Nama Ibu')->placeholder('Nurhayati'),
                            Forms\Components\TextInput::make('nik_ibu')->label('NIK Ibu')->numeric()->maxLength(16)->placeholder('3306181892556661'),
                            Forms\Components\TextInput::make('pendidikan_pekerjaan_ibu')->label('Pendidikan/Pekerjaan Ibu')->placeholder('PNS'),
                            Forms\Components\TextInput::make('nama_ayah')->label('Nama Ayah')->placeholder('Bagas Prasetyo'),
                            Forms\Components\TextInput::make('nik_ayah')->label('NIK Ayah')->numeric()->maxLength(16)->placeholder('33051919020001'),
                            Forms\Components\TextInput::make('pendidikan_pekerjaan_ayah')->label('Pendidikan/Pekerjaan Ayah')->placeholder('PNS'),
                            Forms\Components\TextInput::make('nama_wali')->label('Nama Wali (Jika Ada)')->placeholder('Nur Hidayati'),
                        ])->columns(2),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('provinsi')
                                ->label('Provinsi')
                                // 1. Opsi Dropdown menggunakan model Laravolt yang benar
                                ->options(fn () => Province::pluck('name', 'name')
                                    ->mapWithKeys(fn($name) => [ucwords(strtolower($name)) => ucwords(strtolower($name))])
                                    ->toArray()
                                )
                                ->default(function (string $operation) {
                                    if ($operation !== 'create') return null;
                                    $posyanduProv = auth()->user()?->posyandu?->provinsi ?? \App\Models\MasterPosyandu::find(auth()->user()?->posyandu_id)?->provinsi;
                                    $dbProv = $posyanduProv ? Province::where('name', 'LIKE', "%{$posyanduProv}%")->value('name') : null;
                                    
                                    return $dbProv ? ucwords(strtolower($dbProv)) : null;
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->dehydrateStateUsing(fn ($state) => $state ? ucwords(strtolower($state)) : null)
                                ->afterStateUpdated(function (Forms\Set $set) {
                                    $set('kabupaten', null);
                                    $set('kecamatan', null);
                                    $set('desa_kelurahan', null);
                                }),
                        
                            Forms\Components\Select::make('kabupaten')
                                ->label('Kabupaten/Kota')
                                ->options(function (Forms\Get $get) {
                                    $provinsiNama = $get('provinsi');
                                    if (! $provinsiNama) return [];
                        
                                    $province = Province::where('name', 'LIKE', "%{$provinsiNama}%")->first();
                                    return $province ? City::where('province_code', $province->code)->pluck('name', 'name')
                                        ->mapWithKeys(fn($name) => [ucwords(strtolower($name)) => ucwords(strtolower($name))])
                                        ->toArray() : [];
                                })
                                ->default(function (string $operation) {
                                    if ($operation !== 'create') return null;
                                    $posyanduKab = auth()->user()?->posyandu?->kabupaten_kota ?? \App\Models\MasterPosyandu::find(auth()->user()?->posyandu_id)?->kabupaten_kota;
                                    $dbKab = $posyanduKab ? City::where('name', 'LIKE', "%{$posyanduKab}%")->value('name') : null;
                                    return $dbKab ? ucwords(strtolower($dbKab)) : null;
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->dehydrateStateUsing(fn ($state) => $state ? ucwords(strtolower($state)) : null)
                                ->afterStateUpdated(function (Forms\Set $set) {
                                    $set('kecamatan', null);
                                    $set('desa_kelurahan', null);
                                }),
                        
                            Forms\Components\Select::make('kecamatan')
                                ->label('Kecamatan')
                                ->options(function (Forms\Get $get) {
                                    $kabupatenNama = $get('kabupaten');
                                    $provinsiNama = $get('provinsi');
                                    if (! $kabupatenNama || ! $provinsiNama) return [];
                        
                                    $province = Province::where('name', 'LIKE', "%{$provinsiNama}%")->first();
                                    if (! $province) return [];
                        
                                    $city = City::where('name', 'LIKE', "%{$kabupatenNama}%")->where('province_code', $province->code)->first();
                                    return $city ? District::where('city_code', $city->code)->pluck('name', 'name')
                                        ->mapWithKeys(fn($name) => [ucwords(strtolower($name)) => ucwords(strtolower($name))])
                                        ->toArray() : [];
                                })
                                ->default(function (string $operation) {
                                    if ($operation !== 'create') return null;
                                    $posyanduKec = auth()->user()?->posyandu?->kecamatan ?? \App\Models\MasterPosyandu::find(auth()->user()?->posyandu_id)?->kecamatan;
                                    $dbKec = $posyanduKec ? District::where('name', 'LIKE', "%{$posyanduKec}%")->value('name') : null;
                                    return $dbKec ? ucwords(strtolower($dbKec)) : null;
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->dehydrateStateUsing(fn ($state) => $state ? ucwords(strtolower($state)) : null)
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('desa_kelurahan', null)),
                        
                            Forms\Components\Select::make('desa_kelurahan')
                                ->label('Desa/Kelurahan')
                                ->options(function (Forms\Get $get) {
                                    $kecamatanNama = $get('kecamatan');
                                    $kabupatenNama = $get('kabupaten');
                                    $provinsiNama = $get('provinsi');
                                    if (! $kecamatanNama || ! $kabupatenNama || ! $provinsiNama) return [];
                        
                                    $province = Province::where('name', 'LIKE', "%{$provinsiNama}%")->first();
                                    if (! $province) return [];
                        
                                    $city = City::where('name', 'LIKE', "%{$kabupatenNama}%")->where('province_code', $province->code)->first();
                                    if (! $city) return [];
                        
                                    $district = District::where('name', 'LIKE', "%{$kecamatanNama}%")->where('city_code', $city->code)->first();
                                    return $district ? Village::where('district_code', $district->code)->pluck('name', 'name')
                                        ->mapWithKeys(fn($name) => [ucwords(strtolower($name)) => ucwords(strtolower($name))])
                                        ->toArray() : [];
                                })
                                ->default(function (string $operation) {
                                    if ($operation !== 'create') return null;
                                    $posyanduDesa = auth()->user()?->posyandu?->desa_kelurahan ?? \App\Models\MasterPosyandu::find(auth()->user()?->posyandu_id)?->desa_kelurahan;
                                    $dbDesa = $posyanduDesa ? Village::where('name', 'LIKE', "%{$posyanduDesa}%")->value('name') : null;
                                    return $dbDesa ? ucwords(strtolower($dbDesa)) : null;
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->dehydrateStateUsing(fn ($state) => $state ? ucwords(strtolower($state)) : null),
                            ]),
                        
                            Forms\Components\TextInput::make('nama_puskesmas')
                                ->label('Nama Puskesmas')
                                ->default(fn (string $operation) => $operation === 'create' ? (auth()->user()?->posyandu?->nama_puskesmas ?? \App\Models\MasterPosyandu::find(auth()->user()?->posyandu_id)?->nama_puskesmas) : null),
                        
                            Forms\Components\TextInput::make('nama_posyandu')
                                ->label('Nama Posyandu')
                                ->default(fn (string $operation) => $operation === 'create' ? (auth()->user()?->nama_posyandu ?? \App\Models\MasterPosyandu::find(auth()->user()?->posyandu_id)?->nama_posyandu) : null),
                        ]),

                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\Textarea::make('alamat')->label('Alamat Lengkap')->columnSpan(2)->placeholder('Jl.panglima,No.52 Perumahan Asri'),
                            Forms\Components\TextInput::make('rt')->label('RT')->numeric()->placeholder('02'),
                            Forms\Components\TextInput::make('rw')->label('RW')->numeric()->placeholder('02'),
                        ]),
                        Forms\Components\TextInput::make('no_hp')->label('Nomor WhatsApp Aktif')->tel()->placeholder('089440727164'),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nik')
                ->label('NIK')
                ->searchable(query: function ($query, $search) {
                    $query->orWhere('nik_hash', hash_hmac('sha256', $search, config('app.key')));
                }),
                Tables\Columns\TextColumn::make('nama')->label('Nama Balita')->searchable(),
                Tables\Columns\TextColumn::make('jenis_kelamin')->label('L/P'),
                Tables\Columns\TextColumn::make('tgl_lahir')->label('Tanggal Lahir')->date('d M Y'),
                Tables\Columns\TextColumn::make('nama_ibu')->label('Nama Ibu')->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->modalHeading('Ubah Identitas Utama Balita')
                    ->modalWidth('2xl')
                    
                    ->mountUsing(function (Forms\ComponentContainer $form, Pasien $record) {
                        $riwayat = $record->riwayatKelahiran;
                        
                        $form->fill([
                            'nik' => $record->nik,
                            'no_kk' => $record->no_kk,
                            'nama' => $record->nama,
                            'jenis_kelamin' => $record->jenis_kelamin,
                            'tgl_lahir' => $record->tgl_lahir,
                            'tempat_lahir' => $record->tempat_lahir,
                            
                            'nama_ibu' => $record->nama_ibu,
                            'nik_ibu' => $record->nik_ibu,
                            'pendidikan_pekerjaan_ibu' => $record->pendidikan_pekerjaan_ibu,
                            'nama_ayah' => $record->nama_ayah,
                            'nik_ayah' => $record->nik_ayah,
                            'pendidikan_pekerjaan_ayah' => $record->pendidikan_pekerjaan_ayah,
                            'nama_wali' => $record->nama_wali,
                            
                            'provinsi' => $record->provinsi,
                            'kabupaten' => $record->kabupaten,
                            'kecamatan' => $record->kecamatan,
                            'desa_kelurahan' => $record->desa_kelurahan,
                            'nama_puskesmas' => $record->nama_puskesmas,
                            'nama_posyandu' => $record->nama_posyandu,
                            'alamat' => $record->alamat,
                            'rt' => $record->rt,
                            'rw' => $record->rw,
                            'no_hp' => $record->no_hp,
                            
                            'riwayatKelahiran' => [
                                'anak_ke' => $riwayat?->anak_ke,
                                'usia_kehamilan' => $riwayat?->usia_kehamilan,
                                'berat_lahir' => $riwayat?->berat_lahir,
                                'panjang_lahir' => $riwayat?->panjang_lahir,
                                'lingkar_kepala_lahir' => $riwayat?->lingkar_kepala_lahir,
                                'imd' => $riwayat?->imd ?? 0,
                                'riwayat_asi' => $riwayat?->riwayat_asi,
                            ],
                        ]);
                    })
                    
                    ->using(function (Pasien $record, array $data): Pasien {
                        $record->update(collect($data)->only([
                            'nik', 'no_kk', 'nama', 'jenis_kelamin', 'tgl_lahir', 'tempat_lahir', 
                            'nama_ibu', 'nik_ibu', 'pendidikan_pekerjaan_ibu', 
                            'nama_ayah', 'nik_ayah', 'pendidikan_pekerjaan_ayah', 'nama_wali',
                            'provinsi', 'kabupaten', 'kecamatan', 'desa_kelurahan', 
                            'nama_puskesmas', 'nama_posyandu', 'alamat', 'rt', 'rw', 'no_hp'
                        ])->toArray());

                        $record->riwayatKelahiran()->updateOrCreate(
                            ['pasien_id' => $record->id],
                            collect($data['riwayatKelahiran'] ?? [])->only([
                                'anak_ke', 'usia_kehamilan', 'berat_lahir', 
                                'panjang_lahir', 'lingkar_kepala_lahir', 'imd', 'riwayat_asi'
                            ])->toArray()
                        );

                        return $record;
                    }),

                Tables\Actions\Action::make('hapus_atau_arsip')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading(fn (Pasien $record) => "Manajemen Status Data: {$record->nama}")
                    ->modalWidth('md')
                    ->modalSubmitActionLabel('Konfirmasi & Simpan')
                    ->form([
                        Forms\Components\Select::make('status_tindakan')
                            ->label('Alasan Penghapusan / Pengarsipan')
                            ->options([
                                'salah_input' => 'Salah Input (Hapus Permanen)',
                                'pindah' => 'Pindah Domisili / Wilayah (Arsipkan)',
                                'meninggal' => 'Meninggal Dunia (Arsipkan)',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\Placeholder::make('peringatan_salah_input')
                            ->label('⚠️ PERINGATAN KRITIS')
                            ->content('Data balita beserta seluruh riwayat pemeriksaan bulanan akan DIHAPUS PERMANEN dari database.')
                            ->visible(fn (Forms\Get $get) => $get('status_tindakan') === 'salah_input'),

                        Forms\Components\Textarea::make('keterangan_pindah')
                            ->label('Keterangan Pindah')
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('status_tindakan') === 'pindah'),

                        Forms\Components\DatePicker::make('tgl_meninggal')
                            ->label('Tanggal Meninggal')
                            ->required()
                            ->maxDate(now())
                            ->visible(fn (Forms\Get $get) => $get('status_tindakan') === 'meninggal'),

                        Forms\Components\TextInput::make('tempat_pemakaman')
                            ->label('Tempat Pemakaman')
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('status_tindakan') === 'meninggal'),

                        Forms\Components\TextInput::make('penyebab_meninggal')
                            ->label('Penyebab meninggal')
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('status_tindakan') === 'meninggal'),
                    ])
                    ->action(function (Pasien $record, array $data) {
                        switch ($data['status_tindakan']) {
                            case 'salah_input':
                                $record->delete();
                                \Filament\Notifications\Notification::make()->title('Berhasil Dihapus secara permanen.')->success()->send();
                                break;

                            case 'pindah':
                                $record->update(['is_arsip' => 1]);
                                $record->kondisiKhusus()->updateOrCreate(
                                    ['pasien_id' => $record->id],
                                    ['keterangan_pindah' => $data['keterangan_pindah']]
                                );
                                \Filament\Notifications\Notification::make()->title('Balita dipindahkan ke arsip pindah domisili.')->success()->send();
                                break;

                            case 'meninggal':
                                $record->update(['is_arsip' => 1]);
                                $record->kondisiKhusus()->updateOrCreate(
                                    ['pasien_id' => $record->id],
                                    [
                                        'tgl_meninggal' => $data['tgl_meninggal'],
                                        'tempat_pemakaman' => $data['tempat_pemakaman'],
                                        'penyebab_meninggal' => $data['penyebab_meninggal'],
                                    ]
                                );
                                \Filament\Notifications\Notification::make()->title('Balita dipindahkan ke arsip meninggal dunia.')->success()->send();
                                break;
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPasiens::route('/'),
            'create' => Pages\CreatePasien::route('/create'),
            'edit' => Pages\EditPasien::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool {
        $user = Auth::user();
        
        if (is_null($user)) {
            return false;
        }

        if ($user->email === 'admin@posyandu.com') {
            return true;
        }

        $tugasMeja = $user->meja_tugas;

        return in_array($tugasMeja, ['meja_1', 'superadmin', 'superuser']);
    }

    public static function canAccess(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') {
            return true;
        }

        return in_array('pasiens', $user->akses_menu ?? []);
    }
}