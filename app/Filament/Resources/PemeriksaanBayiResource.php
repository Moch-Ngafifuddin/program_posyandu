<?php

namespace App\Filament\Resources;

use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Carbon\Carbon;
use App\Models\Pasien;
use App\Filament\Resources\PemeriksaanBayiResource\Pages;
use App\Models\PemeriksaanBayi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Toggle;

class PemeriksaanBayiResource extends Resource
{
    protected static ?string $model = PemeriksaanBayi::class;
    
    protected static bool $shouldRegisterNavigation = true;
    protected static ?string $navigationIcon = 'fas-baby'; 
    protected static ?string $navigationGroup = 'Pelayanan';
    protected static ?string $navigationLabel = 'Pemeriksaan Balita';
    protected static ?string $pluralModelLabel = 'Pendaftaran Pemeriksaan Balita';

    public static function form(Form $form): Form
    {
        $hitungUmur = function (Forms\Set $set, Forms\Get $get) {
            $pasienId = $get('pasien_id');
            $tglPeriksa = $get('tgl_periksa');
            
            if (!$pasienId || !$tglPeriksa) {
                $set('keterangan_umur', null);
                $set('usia_bulan', null);
                return;
            }
            
            $pasien = Pasien::find($pasienId);
            if (!$pasien || !$pasien->tgl_lahir) return;
            
            $lahir = Carbon::parse($pasien->tgl_lahir);
            $periksa = Carbon::parse($tglPeriksa);
            
            $hari = (int) $lahir->diffInDays($periksa);
            $bulan = (int) $lahir->diffInMonths($periksa);
            $tahun = (int) $lahir->diffInYears($periksa);
            
            $set('usia_bulan', max(0, $bulan));
            
            if ($tahun >= 1) {
                $sisaBulan = $bulan % 12;
                $set('keterangan_umur', $sisaBulan > 0 ? "{$tahun} Thn {$sisaBulan} Bln" : "{$tahun} Thn");
            } elseif ($bulan >= 1) {
                $set('keterangan_umur', "{$bulan} Bulan");
            } else {
                $set('keterangan_umur', "{$hari} Hari");
            }
        };

        $kalkulasiStatusGizi = function (Forms\Set $set, Forms\Get $get) {
            $bb = $get('berat_badan');
            $tb = $get('tinggi_badan');
            $caraUkur = $get('cara_ukur');
            $usia = $get('usia_bulan');
            $pasienId = $get('pasien_id');

            if (!$pasienId || $usia === null) return;
            
            $pasien = Pasien::find($pasienId);
            if (!$pasien) return;

            $jk = $pasien->jenis_kelamin;

            if (!empty($bb) && is_numeric($bb)) {
                if (method_exists(\App\Helpers\AntropometriHelper::class, 'hitungBbu')) {
                    $set('status_gizi', \App\Helpers\AntropometriHelper::hitungBbu($jk, $usia, $bb));
                }
                if (method_exists(\App\Helpers\AntropometriHelper::class, 'hitungZScoreBBU')) {
                    $zscoreBbu = \App\Helpers\AntropometriHelper::hitungZScoreBBU($jk, $usia, $bb);
                    $set('zscore_bbu', is_null($zscoreBbu) ? '0.00' : number_format($zscoreBbu, 2));
                }
            }

            if (!empty($tb) && is_numeric($tb) && !empty($caraUkur)) {
                $tbKoreksi = (float) $tb;
                if ($usia < 24 && $caraUkur === 'berdiri') $tbKoreksi += 0.7;
                if ($usia >= 24 && $caraUkur === 'terlentang') $tbKoreksi -= 0.7;

                if (method_exists(\App\Helpers\AntropometriHelper::class, 'hitungTbu')) {
                    $set('status_stunting', \App\Helpers\AntropometriHelper::hitungTbu($jk, $usia, $tbKoreksi));
                }
                if (method_exists(\App\Helpers\AntropometriHelper::class, 'hitungZScoreTBU')) {
                    $zscoreTbu = \App\Helpers\AntropometriHelper::hitungZScoreTBU($jk, $usia, $tbKoreksi);
                    $set('zscore_tbu', is_null($zscoreTbu) ? '0.00' : number_format($zscoreTbu, 2));
                }

                if (!empty($bb) && is_numeric($bb)) {
                    if (method_exists(\App\Helpers\AntropometriHelper::class, 'hitungBbtb')) {
                        $set('status_bbtb', \App\Helpers\AntropometriHelper::hitungBbtb($jk, $tbKoreksi, $bb));
                    }
                    if (method_exists(\App\Helpers\AntropometriHelper::class, 'hitungZScoreBBTB')) {
                        $zscoreBbtb = \App\Helpers\AntropometriHelper::hitungZScoreBBTB($jk, $tbKoreksi, $bb);
                        $set('zscore_bbtb', is_null($zscoreBbtb) ? '0.00' : number_format($zscoreBbtb, 2));
                    }
                }
            }
        };

        return $form
        ->schema([
            Forms\Components\Tabs::make('Zona Pelayanan Terintegrasi')
                ->columnSpanFull()
                
                ->activeTab(function() {
                    $paramTab = request()->query('activeTab'); 
                    
                    if ($paramTab === 'meja_2' || $paramTab === 'meja_4') {
                        return 2; 
                    }
                    if ($paramTab === 'meja_5') {
                        return 3; 
                    }
                    
                    return 1; 
                })
                    ->tabs([
                        Tabs\Tab::make('ZONA A (Registrasi & Lingkar Tubuh)')                        
                        ->icon('heroicon-o-user-plus')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('pasien_id')
                                        ->label('Nama / NIK Balita')
                                        ->relationship('pasien', 'nama')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live() 
                                        
                                        ->getSearchResultsUsing(function (string $search) {
                                            $nikHash = hash_hmac('sha256', $search, config('app.key'));

                                            return \App\Models\Pasien::query()
                                                ->where('nama', 'like', "%{$search}%")
                                                ->orWhere('nik_hash', $nikHash)
                                                ->limit(50)
                                                ->get()
                                                ->mapWithKeys(function ($pasien) {
                                                    $nikTampil = (!empty($pasien->nik)) ? $pasien->nik : 'Terenkripsi';
                                                    return [$pasien->id => "{$pasien->nama} (NIK: {$nikTampil})"];
                                                })
                                                ->toArray();
                                        })

                                        ->getOptionLabelUsing(function ($value) {
                                            $pasien = \App\Models\Pasien::find($value);
                                            if (!$pasien) return '';
                                            
                                            $nikTampil = (!empty($pasien->nik)) ? $pasien->nik : 'Terenkripsi';
                                            return "{$pasien->nama} (NIK: {$nikTampil})";
                                        })

                                        ->afterStateUpdated(function(Forms\Set $set, Forms\Get $get) use ($hitungUmur, $kalkulasiStatusGizi) {
                                            $hitungUmur($set, $get);
                                            $kalkulasiStatusGizi($set, $get);
                                        })
                                        ->disabled(function() {
                                            $user = Auth::user();
                                            
                                            if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') {
                                                return false;
                                            }
                                            
                                            return $user->meja_tugas !== 'meja_1';
                                        })

                                        ->dehydrated()
                                        
                                        ->createOptionForm([
                                            Forms\Components\Tabs::make('Pendaftaran Balita Baru')
                                                ->columnSpanFull()
                                                ->tabs([
                                                    Tabs\Tab::make('Data Diri Balita')
                                                        ->icon('heroicon-o-user')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('nik')
                                                                ->label('NIK Balita')
                                                                ->required()
                                                                ->numeric()
                                                                ->length(16)
                                                                ->unique('pasien', 'nik'),
                                                            Forms\Components\Checkbox::make('belum_punya_nik')
                                                                ->label('Anak belum memiliki NIK')
                                                                ->live()
                                                                ->dehydrated(false),
                                                            Forms\Components\TextInput::make('no_kk')
                                                                ->label('Nomor Kartu Keluarga (KK)')
                                                                ->numeric()
                                                                ->length(16),
                                                            Forms\Components\TextInput::make('nama')
                                                                ->label('Nama Lengkap Balita')
                                                                ->required(),
                                                            Forms\Components\Select::make('jenis_kelamin')
                                                                ->label('Jenis Kelamin')
                                                                ->options(['L' => 'Laki-laki', 'P' => 'Perempuan'])
                                                                ->required(),
                                                            Forms\Components\TextInput::make('tempat_lahir')
                                                                ->label('Tempat Lahir')
                                                                ->required(),
                                                            Forms\Components\DatePicker::make('tgl_lahir')
                                                                ->label('Tanggal Lahir')
                                                                ->required()
                                                                ->maxDate(now()),
                                                            Forms\Components\TextInput::make('no_hp')
                                                                ->label('No. HP Orang Tua / WhatsApp')
                                                                ->tel()
                                                                ->maxLength(15),
                                                            Forms\Components\Textarea::make('alamat')
                                                                ->label('Alamat Rumah Lengkap')
                                                                ->columnSpanFull(),
                                                            Forms\Components\Grid::make(2)->schema([
                                                                Forms\Components\TextInput::make('rt')->label('RT')->numeric(),
                                                                Forms\Components\TextInput::make('rw')->label('RW')->numeric(),
                                                            ]),
                                                        ])->columns(2),

                                                        Tabs\Tab::make('Data Orang Tua')
                                                            ->icon('heroicon-o-users')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('nama_ibu')
                                                                    ->label('Nama Lengkap Ibu')
                                                                    ->required(),
                                                                Forms\Components\TextInput::make('nik_ibu')
                                                                    ->label('NIK Ibu')
                                                                    ->numeric()
                                                                    ->length(16),
                                                                Forms\Components\TextInput::make('pendidikan_pekerjaan_ibu')
                                                                    ->label('Pendidikan/Pekerjaan Ibu'),

                                                                Forms\Components\TextInput::make('nama_ayah')
                                                                    ->label('Nama Lengkap Ayah'),
                                                                Forms\Components\TextInput::make('nik_ayah')
                                                                    ->label('NIK Ayah')
                                                                    ->numeric()
                                                                    ->length(16),
                                                                Forms\Components\TextInput::make('pendidikan_pekerjaan_ayah')
                                                                    ->label('Pendidikan/Pekerjaan Ayah'),

                                                                Forms\Components\TextInput::make('nama_wali')
                                                                    ->label('Nama Wali (Jika Ada / Opsional)')
                                                                    ->columnSpanFull(),
                                                            ])->columns(2),

                                                        Tabs\Tab::make('Riwayat Kelahiran')
                                                            ->icon('heroicon-o-clipboard-document-check')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('anak_ke')
                                                                    ->label('Anak Ke-')
                                                                    ->numeric()
                                                                    ->required(),
                                                                Forms\Components\TextInput::make('usia_kehamilan')
                                                                    ->label('Usia Kehamilan (Minggu)')
                                                                    ->numeric(),
                                                                Forms\Components\TextInput::make('berat_lahir')
                                                                    ->label('Berat Badan Lahir (Kg)')
                                                                    ->numeric()
                                                                    ->required(),
                                                                Forms\Components\TextInput::make('panjang_lahir')
                                                                    ->label('Panjang Badan Lahir (Cm)')
                                                                    ->numeric()
                                                                    ->required(),
                                                                Forms\Components\TextInput::make('lingkar_kepala_lahir')
                                                                    ->label('Lingkar Kepala Lahir (Cm)')
                                                                    ->numeric(),
                                                                Forms\Components\Select::make('imd')
                                                                    ->label('Inisiasi Menyusu Dini (IMD)')
                                                                    ->options([1 => 'Ya', 0 => 'Tidak'])
                                                                    ->required(),
                                                                Forms\Components\Select::make('riwayat_asi')
                                                                    ->label('Riwayat Pemberian ASI')
                                                                    ->options([
                                                                        'E1' => 'ASI Eksklusif 1 Bulan',
                                                                        'E2' => 'ASI Eksklusif 2 Bulan',
                                                                        'E3' => 'ASI Eksklusif 3 Bulan',
                                                                        'E4' => 'ASI Eksklusif 4 Bulan',
                                                                        'E5' => 'ASI Eksklusif 5 Bulan',
                                                                        'E6' => 'ASI Eksklusif 6 Bulan',
                                                                    ])->required(),
                                                            ])->columns(2),
                                                    ])
                                            ]),

                                        Forms\Components\DatePicker::make('tgl_periksa')
                                            ->label('Tanggal Kunjungan')
                                            ->default(now())
                                            ->required()
                                            ->live() 
                                            ->afterStateUpdated(function(Forms\Set $set, Forms\Get $get) use ($hitungUmur, $kalkulasiStatusGizi) {
                                                $hitungUmur($set, $get);
                                                $kalkulasiStatusGizi($set, $get);
                                            })
                                            ->disabled(function() {
                                                $user = Auth::user();
                                                
                                                if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') {
                                                    return false;
                                                }
                                                
                                                return !in_array($user->meja_tugas, ['meja_2', 'meja_4']);
                                            })                                            
                                            ->dehydrated(),

                                        Forms\Components\TextInput::make('keterangan_umur')
                                            ->label('Usia Saat Diperiksa')
                                            ->disabled() 
                                            ->dehydrated() 
                                            ->required(),
                                            
                                        Forms\Components\Hidden::make('usia_bulan')->dehydrated(), 
                                    ]),


                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('lingkar_kepala')
                                            ->label('Lingkar Kepala (Cm)')
                                            ->numeric()
                                            ->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_1', 'superadmin']))
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('lila')
                                            ->label('Lingkar Lengan Atas / LiLA (Cm)')
                                            ->numeric()
                                            ->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_1', 'superadmin']))
                                            ->dehydrated(),
                                    ]),
                            ]),

                            Tabs\Tab::make('ZONA B (Pengukuran & Penimbangan)')                            
                            ->icon('heroicon-o-scale')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('tinggi_badan')
                                            ->label('Tinggi/Panjang Badan (Cm)')
                                            ->numeric()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated($kalkulasiStatusGizi)
                                            ->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_2', 'meja_4', 'superadmin']))
                                            ->dehydrated(),
                                            
                                        Forms\Components\Select::make('cara_ukur')
                                            ->label('Cara Ukur')
                                            ->options(['berdiri' => 'Berdiri', 'terlentang' => 'Terlentang'])
                                            ->live()
                                            ->afterStateUpdated($kalkulasiStatusGizi)
                                            ->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_2', 'meja_4', 'superadmin']))
                                            ->dehydrated(),

                                        Forms\Components\TextInput::make('berat_badan')
                                            ->label('Berat Badan (Kg)')
                                            ->numeric()
                                            ->live(debounce: 500) 
                                            ->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_2', 'meja_4', 'superadmin']))
                                            ->dehydrated()
                                            ->afterStateUpdated(function (?string $state, Get $get, Set $set, ?PemeriksaanBayi $record) use ($kalkulasiStatusGizi) {
                                                $kalkulasiStatusGizi($set, $get);
                                                $pasienId = $get('pasien_id'); 
                                                $bbSekarang = (float) $state;
                                                $usiaBulan = (int) $get('usia_bulan');

                                                if ($pasienId && $bbSekarang > 0) {
                                                    $pasien = \App\Models\Pasien::find($pasienId);
                                                    $jk = $pasien ? $pasien->jenis_kelamin : 'L';
                                                    $tglPeriksaHariIni = $get('tgl_periksa') ?? now()->toDateString();

                                                    $kbm = 0.2; 
                                                    if ($usiaBulan === 1) { $kbm = 0.8; }
                                                    elseif ($usiaBulan === 2) { $kbm = ($jk === 'L') ? 0.9 : 0.8; }
                                                    elseif ($usiaBulan === 3) { $kbm = ($jk === 'L') ? 0.8 : 0.6; }
                                                    elseif ($usiaBulan === 4) { $kbm = ($jk === 'L') ? 0.6 : 0.5; }
                                                    elseif ($usiaBulan === 5) { $kbm = ($jk === 'L') ? 0.5 : 0.4; }
                                                    elseif ($usiaBulan === 6) { $kbm = ($jk === 'L') ? 0.4 : 0.3; }
                                                    elseif ($usiaBulan >= 7 && $usiaBulan <= 11) { $kbm = 0.3; }

                                                    $queryLalu = \App\Models\PemeriksaanBayi::where('pasien_id', $pasienId)
                                                        ->whereDate('tgl_periksa', '<', \Carbon\Carbon::parse($tglPeriksaHariIni)->startOfMonth()->toDateString());

                                                    if ($record && $record->exists) {
                                                        $queryLalu->where('id', '!=', $record->id);
                                                    }
                                                    
                                                    $pemeriksaanLalu = $queryLalu->orderBy('tgl_periksa', 'desc')->first();

                                                    if ($pemeriksaanLalu && $pemeriksaanLalu->berat_badan) {
                                                        $bbLalu = (float) $pemeriksaanLalu->berat_badan;
                                                        $kenaikanRiil = $bbSekarang - $bbLalu;

                                                        if ($kenaikanRiil >= $kbm) {
                                                            $set('kenaikan_bb', 'naik'); 
                                                            $set('keterangan_bb', "N (Naik). Timbangan naik " . number_format($kenaikanRiil, 2) . " Kg (Memenuhi standar KBM Kemenkes usia {$usiaBulan} bulan sebesar {$kbm} Kg).");
                                                        } else {
                                                            $set('kenaikan_bb', 'not_naik'); 
                                                            if ($kenaikanRiil > 0) {
                                                                $set('keterangan_bb', "T (Tidak Naik). Timbangan hanya naik " . number_format($kenaikanRiil, 2) . " Kg (TIDAK LOLOS standar KBM Kemenkes usia {$usiaBulan} bulan sebesar {$kbm} Kg).");
                                                            } else {
                                                                $set('keterangan_bb', "T (Tidak Naik). Timbangan menyusut / tetap sebesar " . number_format($kenaikanRiil, 2) . " Kg dari bulan lalu.");
                                                            }
                                                        }
                                                    } else {
                                                        $adaRiwayatSamaSekali = \App\Models\PemeriksaanBayi::where('pasien_id', $pasienId)->exists();
                                                        if ($adaRiwayatSamaSekali) {
                                                            $set('kenaikan_bb', 'not_naik');
                                                            $set('keterangan_bb', "Bulan lalu tidak menimbang (Status: T).");
                                                        } else {
                                                            $set('kenaikan_bb', 'naik');
                                                            $set('keterangan_bb', "Bulan ini dihitung N (Naik) karena merupakan penimbangan pertama kali di sistem.");
                                                        }
                                                    }
                                                }
                                            }),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('kenaikan_bb')
                                            ->label('Status Kenaikan Berat Badan (N/T) Kemenkes')
                                            ->placeholder('Otomatis...')
                                            ->options([
                                                'naik' => 'N (Berat Badan Naik Sesuai KBM)',
                                                'not_naik' => 'T (Berat Badan Tidak Naik / Kurang Dari KBM)',
                                            ])
                                            ->disabled() 
                                            ->dehydrated(),

                                        Forms\Components\TextInput::make('keterangan_bb')
                                            ->label('Analisis Kenaikan Minimal (KMS)')
                                            ->placeholder('Otomatis...')
                                            ->readOnly()
                                            ->dehydrated(),
                                    ]),
                            ]),

                            Tabs\Tab::make('ZONA C (Evaluasi Medis & Intervensi)')                            
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Forms\Components\Fieldset::make('Kesimpulan Gizi Anak (Murni Komputasi Sistem)')
                                    ->schema([
                                        Forms\Components\TextInput::make('status_gizi')->label('Status Gizi (BB/U)')->readOnly()->dehydrated(),
                                        Forms\Components\TextInput::make('zscore_bbu')->label('Z-Score (BB/U)')->readOnly()->dehydrated(),
                                        Forms\Components\TextInput::make('status_stunting')->label('Status Stunting (TB/U)')->readOnly()->dehydrated(),
                                        Forms\Components\TextInput::make('zscore_tbu')->label('Z-Score (TB/U)')->readOnly()->dehydrated(),
                                        Forms\Components\TextInput::make('status_bbtb')->label('Status (BB/TB)')->readOnly()->dehydrated(),
                                        Forms\Components\TextInput::make('zscore_bbtb')->label('Z-Score (BB/TB)')->readOnly()->dehydrated(),
                                    ])->columns(2),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('rambu_gizi')->label('Rambu Gizi (N/T/O)')->placeholder('N')->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']))->dehydrated(),
                                        Forms\Components\TextInput::make('titik_pertumbuhan')->label('Titik Grafik (H/K/BGM)')->placeholder('H')->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']))->dehydrated(),
                                        Forms\Components\Select::make('pitting_edema')
                                            ->label('Pitting Edema Bilateral')
                                            ->options(['tidak ada' => 'Tidak Ada', 'derajat +1' => 'Derajat +1', 'derajat +2' => 'Derajat +2', 'derajat +3' => 'Derajat +3'])
                                            ->default('tidak ada')
                                            ->disabled(function() {
                                                $user = Auth::user();
                                                if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') return false;
                                                return $user->meja_tugas !== 'meja_5';
                                            })                                            
                                            ->dehydrated(),
                                    ]),
                                    
                                Forms\Components\Grid::make(4) 
                                    ->schema([
                                        Forms\Components\Toggle::make('vitamin_a')->label('Vit A?')->inline(false)->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']))->dehydrated(),
                                        Forms\Components\Toggle::make('obat_cacing')->label('Obat Cacing?')->inline(false)->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']))->dehydrated(),
                                        Forms\Components\Toggle::make('asi_eksklusif')->label('ASI Eksklusif?')->inline(false)->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']))->dehydrated(),
                                        Forms\Components\Toggle::make('pmba')->label('PMBA?')->inline(false)->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']))->dehydrated(),
                                        Forms\Components\Toggle::make('sdidtk')->label('SDIDTK?')->inline(false)->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']))->dehydrated(),
                                        Forms\Components\Toggle::make('kelas_ibu')->label('Ikut Kelas Ibu?')->inline(false)->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']))->dehydrated(),
                                        Forms\Components\Toggle::make('menerima_mbg')->label('Dapat MBG?')->inline(false)->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']))->dehydrated(),
                                    ]),
                                    
                                Forms\Components\Select::make('jenis_imunisasi')
                                    ->label('Jenis Imunisasi Hari Ini')
                                    ->multiple()
                                    ->searchable()
                                    ->disabled(function() {
                                        $user = Auth::user();
                                        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') return false;
                                        return $user->meja_tugas !== 'meja_5';
                                    })                                   
                                    ->dehydrated()
                                    ->options([
                                        'HB0' => 'HB0 (0 Bulan)', 'BCG' => 'BCG (1 Bulan)', 'Polio 1' => 'Polio 1 (1 Bulan)',
                                        'DPT-HB-Hib 1' => 'DPT-HB-Hib 1 (2 Bulan)', 'Polio 2' => 'Polio 2 (2 Bulan)', 'PCV 1' => 'PCV 1 (2 Bulan)', 'Rotavirus 1' => 'Rotavirus 1 (2 Bulan)',
                                        'DPT-HB-Hib 2' => 'DPT-HB-Hib 2 (3 Bulan)', 'Polio 3' => 'Polio 3 (3 Bulan)', 'DPT-HB-Hib 3' => 'DPT-HB-Hib 3 (4 Bulan)',
                                        'Polio 4' => 'Polio 4 (4 Bulan)', 'IPV 1' => 'IPV 1 (4 Bulan)', 'Campak-MR 1' => 'Campak/MR 1 (9 Bulan)',
                                    ]),
                                    
                                Forms\Components\Textarea::make('catatan')
                                    ->label('Catatan / KIE (Konseling)')
                                    ->columnSpanFull()
                                    ->disabled(function() {
                                        $user = Auth::user();
                                        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') return false;
                                        return $user->meja_tugas !== 'meja_5';
                                    })                                   
                                    ->dehydrated(),
                                    
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Toggle::make('deteksi_tbc')
                                            ->label('S. TBC (Deteksi)')
                                            ->disabled(function() {
                                                $user = Auth::user();
                                                if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') return false;
                                                return $user->meja_tugas !== 'meja_5';
                                            })                                           
                                            ->dehydrated()
                                            ->helperText(new \Illuminate\Support\HtmlString("<span class='text-xs text-rose-600 block mt-1'>⚠️ Aktifkan jika Batuk/Demam &ge; 2 minggu, BB 2T, atau lesu.</span>")),
                                        Forms\Components\Toggle::make('kie')->label('Sudah KIE/Konseling?')->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']))->dehydrated(),
                                        Forms\Components\Toggle::make('rujuk')->label('Rujuk Ke Puskesmas?')->disabled(fn () => !in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']))->dehydrated(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['pasien']))
            ->columns([
                Tables\Columns\TextColumn::make('pasien.nama')->label('Nama Balita')->searchable(),
                Tables\Columns\TextColumn::make('keterangan_umur')->label('Usia')->badge()->color('success'),
                Tables\Columns\TextColumn::make('berat_badan')->label('Berat (Zona B)')->suffix(' Kg')->placeholder('⏳ Mengantre'),
                Tables\Columns\TextColumn::make('tinggi_badan')->label('Tinggi (Zona B)')->suffix(' Cm')->placeholder('⏳ Mengantre'),
                Tables\Columns\TextColumn::make('lila')->label('LiLA (Zona A)')->suffix(' Cm')->placeholder('-'),
                Tables\Columns\IconColumn::make('menerima_mbg')->label('MBG')->boolean(),
            ])

            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->label(function() {
                    $user = Auth::user();
                    $namaMeja = $user?->mejaPelayanan?->nama_meja;
                    $kodeMeja = $user?->mejaPelayanan?->kode_meja;
            
                    $labelTombol = 'Input Baru ZONA A (Registrasi & Lingkar Tubuh)';
            
                    if (!is_null($user) && $namaMeja && $kodeMeja !== 'superadmin' && $kodeMeja !== 'superuser') {
                        $labelTombol = "Input Baru " . $namaMeja;
                    }
            
                    return $labelTombol;
                })
                ->color('success')
                ->icon('heroicon-o-plus-circle')
                ->url(function() {
                    $user = Auth::user();
                    $kodeMeja = $user?->mejaPelayanan?->kode_meja;
                    
                    // Jika superadmin atau superuser, default buka meja_1 (Zona A)
                    // Jika kader biasa, gunakan kode_meja tugasnya masing-masing
                    $tabAktif = in_array($kodeMeja, ['superadmin', 'superuser']) ? 'meja_1' : $user?->meja_tugas;
            
                    return static::getUrl('create') . '?activeTab=' . $tabAktif;
                }),

                Tables\Actions\Action::make('import_pemeriksaan')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->modalHeading('Import Data')
                    ->modalDescription('Upload rekap timbangan bulanan balita. Pastikan NIK Balita sudah sesuai dengan database.')
                    ->modalSubmitActionLabel('Proses Simpan Rekam Medis')
                    ->form([
                        Forms\Components\FileUpload::make('file_excel')
                            ->label('Pilih Berkas Excel (.xlsx)')
                            ->disk('local')
                            ->directory('import-pemeriksaan-temp')
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ]),
                    ])
                    ->action(function (array $data) {
                        $disk = \Illuminate\Support\Facades\Storage::disk('local');
            
                        if (!$disk->exists($data['file_excel'])) {
                            \Filament\Notifications\Notification::make()
                                ->title('File Tidak Ditemukan')
                                ->danger()
                                ->send();
                            return;
                        }
            
                        $filePath = $disk->path($data['file_excel']);
            
                        try {
                            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\PemeriksaanImport, $filePath);
            
                            \Filament\Notifications\Notification::make()
                                ->title('Import Pemeriksaan Sukses!')
                                ->body('Seluruh riwayat timbangan bulanan berhasil disimpan dan diintegrasikan.')
                                ->success()
                                ->send();
            
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Melakukan Import')
                                ->body('Penyebab: ' . $e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            
                Tables\Actions\Action::make('export_pemeriksaan')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->with(['pasien'])->get();
                        
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\PemeriksaanExport($records), 
                            'rekap_pemeriksaan_balita_' . now()->format('Ymd_His') . '.xlsx'
                        );
                    }),

                
            ])

            ->poll('3s') 
            ->actions([
                // 📋 1. TOMBOL UTAMA (Membuka Detail Form secara umum)
                Tables\Actions\EditAction::make()
                    ->label('Detail Form')
                    ->icon('heroicon-o-pencil-square')
                    ->color('secondary'),

                // ⚖️ 2. TOMBOL TIMBANG (Otomatis melompat ke halaman Edit & membuka Tab ZONA B)
                Tables\Actions\EditAction::make('timbang_action')
                    ->label('Timbang (Zona B)')
                    ->icon('heroicon-o-scale')
                    ->color('success')
                    ->visible(fn () => in_array(Auth::user()?->meja_tugas, ['meja_2', 'meja_4', 'superadmin']) || Auth::user()?->email === 'admin@posyandu.com')
                    ->url(fn (PemeriksaanBayi $record) => static::getUrl('edit', ['record' => $record]) . '?activeTab=meja_2'),

                // 🩺 3. TOMBOL EVALUASI (Otomatis melompat ke halaman Edit & membuka Tab ZONA C)
                Tables\Actions\EditAction::make('evaluasi_action')
                    ->label('Evaluasi (Zona C)')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->visible(fn () => in_array(Auth::user()?->meja_tugas, ['meja_5', 'superadmin']) || Auth::user()?->email === 'admin@posyandu.com')
                    ->url(fn (PemeriksaanBayi $record) => static::getUrl('edit', ['record' => $record]) . '?activeTab=meja_5'),
            ]);
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ListPemeriksaanBayis::route('/'),
            'create' => Pages\CreatePemeriksaanBayi::route('/create'),
            'edit' => Pages\EditPemeriksaanBayi::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['pasien'])
            ->whereDate('tgl_periksa', \Carbon\Carbon::today()->toDateString());
    }

    public static function canCreate(): bool {
        $user = Auth::user();
        $kodeMeja = $user?->mejaPelayanan?->kode_meja;
        
        return $user?->email === 'admin@posyandu.com' || $kodeMeja === 'superadmin' || $kodeMeja === 'meja_1';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin' || $user->mejaPelayanan?->kode_meja === 'superadmin') {
            return true;
        }
        
        return in_array('pemeriksaan-bayis', $user->akses_menu ?? []);
    }
}