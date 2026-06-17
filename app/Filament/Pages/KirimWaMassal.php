<?php

namespace App\Filament\Pages;

use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use App\Models\Pasien;
use App\Models\TemplatePesan;
use App\Jobs\ProsesKirimWa;
use Carbon\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;

class KirimWaMassal extends Page implements \Filament\Forms\Contracts\HasForms
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Kirim WA Massal';
    protected static ?string $navigationGroup = 'Pelayanan';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.kirim-wa-massal';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Kirim Pesan Massal (Siaran)')
                    ->description('Pilih kelompok target, isi variabel jadwal, dan kirim pesan secara otomatis.')
                    ->schema([
                        Select::make('target_kategori')
                            ->label('Target Kategori Pasien / Orang Tua')
                            ->options([
                                'semua' => 'Semua Pasien',
                                'balita' => 'Khusus Orang Tua Balita',
                                'remaja' => 'Khusus Remaja',
                                'lansia' => 'Khusus Lansia',
                            ])
                            ->required(),

                        // 1. Pilih Template Menggunakan Select Component yang Reaktif
                        Select::make('template_pesan_id')
                            ->label('Gunakan Template Master')
                            ->options(TemplatePesan::all()->pluck('nama_template', 'id')) // Ambil data dari tabel SQL
                            ->placeholder('Kustom (Ketik Manual)')
                            ->live() // 🟢 Membuat komponen ini reaktif secara real-time
                            ->afterStateUpdated(function (?string $state, Set $set) {
                                // 🟢 Jalankan pencarian otomatis ketika template dipilih
                                if ($state) {
                                    $template = TemplatePesan::find($state);
                                    if ($template) {
                                        // Tembakkan isi_pesan dari database ke input teks bernama 'isi_pesan'
                                        $set('isi_pesan', $template->isi_pesan); 
                                    }
                                } else {
                                    // Jika dikosongkan (pilih kustom), kosongkan kotak input pesan
                                    $set('isi_pesan', null);
                                }
                            }),

                        DatePicker::make('tanggal_kegiatan')
                            ->label('Tanggal Jadwal Pelayanan')
                            ->default(now())
                            ->required(),

                        TextInput::make('lokasi_kegiatan')
                            ->label('Lokasi Pelayanan / Posyandu')
                            ->placeholder('Contoh: Gedung Olahraga Bancarkembar')
                            ->required(),

                        // 2. 🟢 SATU INPUTAN PESAN UTAMA: Terintegrasi dengan Template & Variabel Dinamis
                        Textarea::make('isi_pesan')
                            ->label('Struktur Isi Pesan Siaran WhatsApp')
                            ->placeholder('Ketik pesan di sini atau pilih dari template master di atas...')
                            ->helperText('Anda bisa menggunakan kode placeholder otomatis: {nama}, {tanggal}, dan {lokasi} untuk mempermudah isi pesan.')
                            ->rows(8)
                            ->required()
                            ->columnSpan('full'), // Membuat kotak pesan melebar penuh agar rapi
                    ])->columns(2), // 🟢 Penutup Section yang benar sekarang ada di sini
            ])
            ->statePath('data');
    }

    public function eksekusiKirim(): void
    {
        $formData = $this->form->getState();
        $kategori = $formData['target_kategori'];
        $pesanMentah = $formData['isi_pesan'];
        
        $tanggalFormat = Carbon::parse($formData['tanggal_kegiatan'])->translatedFormat('l, d F Y');
        $lokasiFormat = $formData['lokasi_kegiatan'];

        // 1. Inisialisasi Query Pasien Aktif
        $query = Pasien::query()->where('is_arsip', 0);

        // 2. SOLUSI BUG 1: Memproses Filter Kategori Sesuai Pilihan UI
        if ($kategori !== 'semua') {
            // Catatan: Sesuaikan nama kolom penanda kategori pada tabel pasien Anda (misal: 'kategori' atau 'jenis_pasien')
            $query->where('kategori', $kategori); 
        }

        // Ambil pasien yang nomor HP-nya tidak kosong
        $pasiens = $query->whereNotNull('no_hp')->where('no_hp', '!=', '')->get();

        if ($pasiens->count() === 0) {
            Notification::make()
                ->title('Gagal Mengirim')
                ->body('Tidak ditemukan nomor HP aktif untuk kategori target tersebut di database.')
                ->danger()
                ->send();
            return;
        }

        $totalAntrean = 0;
        
        foreach ($pasiens as $pasien) {
            $pesanFinal = str_replace('{nama}', $pasien->nama, $pesanMentah);
            $pesanFinal = str_replace('{tanggal}', $tanggalFormat, $pesanFinal);
            $pesanFinal = str_replace('{lokasi}', $lokasiFormat, $pesanFinal);

            // SOLUSI BUG 2: Alihkan dari synchronous API ke Queue Job Asinkronus
            ProsesKirimWa::dispatch($pasien->no_hp, $pesanFinal);
            $totalAntrean++;
        }

        if ($totalAntrean > 0) {
            Notification::make()
                ->title('Berhasil Masuk Antrean!')
                ->body("Sebanyak {$totalAntrean} pesan WhatsApp dijadwalkan dalam sistem antrean background job.")
                ->success()
                ->send();

            $this->form->fill();
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin' || $user->mejaPelayanan?->kode_meja === 'superadmin') {
            return true;
        }
        
        return in_array('kirim-wa-massals', $user->akses_menu ?? []);
    }
    
}