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
                        Forms\Components\TextInput::make('nama')->label('Nama Lengkap Balita')->required(),
                        Forms\Components\Radio::make('jenis_kelamin')->label('Jenis Kelamin')->options(['L' => 'Laki-laki', 'P' => 'Perempuan'])->inline()->required(),
                        Forms\Components\TextInput::make('tempat_lahir')->label('Tempat Lahir')->required(),
                        Forms\Components\DatePicker::make('tgl_lahir')->label('Tanggal Lahir')->required()->native(false),
                        Forms\Components\TextInput::make('no_kk')->label('Nomor Kartu Keluarga (KK)')->numeric()->maxLength(16),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('nik')
                                ->label('Nomor NIK Anak')
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

                Forms\Components\Section::make('Data Kelahiran & Pengukuran Awal (Buku KIA)')
                    ->schema([
                        Forms\Components\TextInput::make('anak_ke')->label('Anak Ke-')->numeric(),
                        Forms\Components\TextInput::make('usia_kehamilan')->label('Usia Kehamilan Saat Lahir (Minggu)')->numeric(),
                        Forms\Components\TextInput::make('berat_lahir')->label('Berat Lahir (Kg)')->numeric()->live()->helperText('Gunakan titik, misal: 2.4'),
                        Forms\Components\TextInput::make('panjang_lahir')->label('Panjang Lahir (Cm)')->numeric(),
                        Forms\Components\TextInput::make('lingkar_kepala_lahir')->label('Lingkar Kepala Lahir (Cm)')->numeric(),
                        Forms\Components\Toggle::make('imd')->label('Inisiasi Menyusu Dini (IMD)')->inline(false),
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

                        Forms\Components\Fieldset::make('Tatalaksana BBLR & Prematur')
                            ->visible(fn (Get $get) => is_numeric($get('berat_lahir')) && (float) $get('berat_lahir') < 2.5)
                            ->schema([
                                Forms\Components\Checkbox::make('buku_kia_bayi_kecil')->label('Mendapatkan Buku KIA Bayi Kecil (BBLR & Prematur)'),
                                Forms\Components\Checkbox::make('tatalaksana_bblr')->label('Mendapatkan Tatalaksana BBLR'),
                            ])->columns(1)
                    ])->columns(3),

                Forms\Components\Section::make('Informasi Orang Tua & Wilayah')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('nama_ibu')->label('Nama Ibu'),
                            Forms\Components\TextInput::make('nik_ibu')->label('NIK Ibu')->numeric()->maxLength(16),
                            Forms\Components\TextInput::make('pendidikan_pekerjaan_ibu')->label('Pendidikan/Pekerjaan Ibu'),
                            Forms\Components\TextInput::make('nama_ayah')->label('Nama Ayah'),
                            Forms\Components\TextInput::make('nik_ayah')->label('NIK Ayah')->numeric()->maxLength(16),
                            Forms\Components\TextInput::make('pendidikan_pekerjaan_ayah')->label('Pendidikan/Pekerjaan Ayah'),
                            Forms\Components\TextInput::make('nama_wali')->label('Nama Wali (Jika Ada)'),
                        ])->columns(2),

                        Forms\Components\Grid::make(3)->schema([
                            // 1. Dropdown Provinsi (Default dari Akun Login, Bisa Diubah)
                            Forms\Components\Select::make('provinsi')
                                ->label('Provinsi')
                                ->options(fn () => Province::pluck('name', 'name'))
                                ->default(fn () => auth()->user()?->provinsi) // Auto-fill dari profil user login
                                ->searchable()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Forms\Set $set) {
                                    // Jika provinsi diganti, kosongkan semua field di bawahnya agar tidak terjadi mismatch data
                                    $set('kabupaten', null);
                                    $set('kecamatan', null);
                                    $set('desa_kelurahan', null);
                                }),
                        
                            // 2. Dropdown Kabupaten/Kota (Default dari Akun Login, Bisa Diubah)
                            Forms\Components\Select::make('kabupaten')
                                ->label('Kabupaten/Kota')
                                ->options(function (Get $get) {
                                    $provinsiNama = $get('provinsi');
                                    if (! $provinsiNama) return [];
                        
                                    $province = Province::where('name', $provinsiNama)->first();
                                    return $province ? City::where('province_code', $province->code)->pluck('name', 'name') : [];
                                })
                                ->default(fn () => auth()->user()?->kabupaten_kota) // Auto-fill dari profil user login
                                ->searchable()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Forms\Set $set) {
                                    // Jika kabupaten diganti, kosongkan kecamatan & desa di bawahnya
                                    $set('kecamatan', null);
                                    $set('desa_kelurahan', null);
                                }),
                        
                            // 3. Dropdown Kecamatan (Default dari Akun Login, Bisa Diubah)
                            Forms\Components\Select::make('kecamatan')
                                ->label('Kecamatan')
                                ->options(function (Get $get) {
                                    $kabupatenNama = $get('kabupaten');
                                    $provinsiNama = $get('provinsi');
                                    if (! $kabupatenNama || ! $provinsiNama) return [];
                        
                                    $province = Province::where('name', $provinsiNama)->first();
                                    if (! $province) return [];
                        
                                    $city = City::where('name', $kabupatenNama)->where('province_code', $province->code)->first();
                                    return $city ? District::where('city_code', $city->code)->pluck('name', 'name') : [];
                                })
                                ->default(fn () => auth()->user()?->kecamatan) // Auto-fill dari profil user login
                                ->searchable()
                                ->live()
                                ->required()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('desa_kelurahan', null)), // Jika kecamatan diganti, kosongkan desa
                        
                            // 4. Dropdown Desa/Kelurahan (Default dari Akun Login, Bisa Diubah)
                            Forms\Components\Select::make('desa_kelurahan')
                                ->label('Desa/Kelurahan')
                                ->options(function (Get $get) {
                                    $kecamatanNama = $get('kecamatan');
                                    $kabupatenNama = $get('kabupaten');
                                    $provinsiNama = $get('provinsi');
                                    if (! $kecamatanNama || ! $kabupatenNama || ! $provinsiNama) return [];
                        
                                    $province = Province::where('name', $provinsiNama)->first();
                                    if (! $province) return [];
                        
                                    $city = City::where('name', $kabupatenNama)->where('province_code', $province->code)->first();
                                    if (! $city) return [];
                        
                                    $district = District::where('name', $kecamatanNama)->where('city_code', $city->code)->first();
                                    return $district ? Village::where('district_code', $district->code)->pluck('name', 'name') : [];
                                })
                                ->default(fn () => auth()->user()?->desa_kelurahan) // Auto-fill dari profil user login
                                ->searchable()
                                ->required(),
                        
                            // 5. Nama Puskesmas & Posyandu (Auto-fill dari Akun Login, Bisa Diubah)
                            Forms\Components\TextInput::make('nama_puskesmas')
                                ->label('Nama Puskesmas')
                                ->default(fn () => auth()->user()?->nama_puskesmas),
                        
                            Forms\Components\TextInput::make('nama_posyandu')
                                ->label('Nama Posyandu')
                                ->default(fn () => auth()->user()?->nama_posyandu),
                        ]),

                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\Textarea::make('alamat')->label('Alamat Lengkap')->columnSpan(2),
                            Forms\Components\TextInput::make('rt')->label('RT')->numeric(),
                            Forms\Components\TextInput::make('rw')->label('RW')->numeric(),
                        ]),
                        Forms\Components\TextInput::make('no_hp')->label('Nomor WhatsApp Aktif')->tel(),
                    ])
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
                    
                    // 1. Fungsi penarik data dari sub-tabel saat modal pertama kali diklik/dibuka
                    ->mountUsing(function (Forms\ComponentContainer $form, Pasien $record) {
                        $riwayat = $record->riwayatKelahiran;
                        
                        $form->fill([
                            'nama' => $record->nama,
                            'jenis_kelamin' => $record->jenis_kelamin,
                            'tgl_lahir' => $record->tgl_lahir,
                            'tempat_lahir' => $record->tempat_lahir,
                            'alamat' => $record->alamat,
                            'rt' => $record->rt,
                            'rw' => $record->rw,
                            'no_hp' => $record->no_hp,
                            'nama_wali' => $record->nama_wali,
                            'nama_ayah' => $record->nama_ayah,
                            'nik_ayah' => $record->nik_ayah,
                            'pendidikan_pekerjaan_ayah' => $record->pendidikan_pekerjaan_ayah,
                            'nama_ibu' => $record->nama_ibu,
                            'nik_ibu' => $record->nik_ibu,
                            'pendidikan_pekerjaan_ibu' => $record->pendidikan_pekerjaan_ibu,
                            
                            // Ambil data dari sub-tabel riwayat_kelahiran dan masukkan ke form
                            'anak_ke' => $riwayat?->anak_ke,
                            'usia_kehamilan' => $riwayat?->usia_kehamilan,
                            'berat_lahir' => $riwayat?->berat_lahir,
                            'panjang_lahir' => $riwayat?->panjang_lahir,
                            'lingkar_kepala_lahir' => $riwayat?->lingkar_kepala_lahir,
                            'imd' => $riwayat?->imd ?? 0,
                            'riwayat_asi' => $riwayat?->riwayat_asi,
                        ]);
                    })
                    
                    // 2. Fungsi pembagian data ke masing-masing tabel saat tombol simpan diklik
                    ->using(function (Pasien $record, array $data): Pasien {
                        // Update data utama pada tabel pasien
                        $record->update(collect($data)->only([
                            'nama', 'jenis_kelamin', 'tgl_lahir', 'tempat_lahir', 
                            'alamat', 'rt', 'rw', 'no_hp', 'nama_wali', 
                            'nama_ayah', 'nik_ayah', 'pendidikan_pekerjaan_ayah', 
                            'nama_ibu', 'nik_ibu', 'pendidikan_pekerjaan_ibu'
                        ])->toArray());

                        // Update atau buat baru data medis pada tabel pasien_riwayat_kelahiran
                        $record->riwayatKelahiran()->updateOrCreate(
                            ['pasien_id' => $record->id],
                            collect($data)->only([
                                'anak_ke', 'usia_kehamilan', 'berat_lahir', 
                                'panjang_lahir', 'lingkar_kepala_lahir', 'imd', 'riwayat_asi'
                            ])->toArray()
                        );

                        return $record;
                    }),

                // 🟢 HAPUS/ARSIP ACTION (Pencatatan dialihkan ke tabel pasien_kondisi_khusus)
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
    
    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin' || $user->mejaPelayanan?->kode_meja === 'superadmin') {
            return true;
        }
        
        return in_array('pasiens', $user->akses_menu ?? []);
    }
}