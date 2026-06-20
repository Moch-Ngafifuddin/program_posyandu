<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Faker\Factory as Faker;
use Carbon\Carbon;

class PasienPemeriksaanSeeder extends Seeder
{

    private function hitungZScore($nilai, $median, $plus1, $min1)
    {
        if ($nilai == $median) return 0;
        if ($nilai > $median) {
            return round(($nilai - $median) / ($plus1 - $median), 2);
        } else {
            return round(($nilai - $median) / ($median - $min1), 2);
        }
    }


    private function generateValueFromZScore($zscore, $median, $plus1, $min1)
    {
        if ($zscore >= 0) {
            $sd_diff = $plus1 - $median;
            return round($median + ($zscore * $sd_diff), 2);
        } else {
            $sd_diff = $median - $min1;
            return round($median + ($zscore * $sd_diff), 2);
        }
    }

    public function run()
    {
        $faker = Faker::create('id_ID');
        $posyandu_id = 1; 
        $petugas_id = 1;  

        for ($i = 0; $i < 500; $i++) {
            $gender = $faker->randomElement(['L', 'P']);
            $tgl_lahir = Carbon::now()->subMonths(rand(1, 60))->subDays(rand(1, 28));
            $usia_bulan_sekarang = $tgl_lahir->diffInMonths(Carbon::now());
            
            $nik_raw = $faker->nik();
            $nama_ayah = $faker->name('male');
            $nama_ibu = $faker->name('female');
            

            $pasien_id = DB::table('pasien')->insertGetId([
                'posyandu_id' => $posyandu_id,
                'nik' => Crypt::encryptString($nik_raw),
                'nik_hash' => hash('sha256', $nik_raw),
                'no_kk' => Crypt::encryptString($faker->nik()), 
                'nama' => $faker->name($gender == 'L' ? 'male' : 'female'),
                'jenis_kelamin' => $gender,
                'tgl_lahir' => $tgl_lahir->toDateString(),
                'tempat_lahir' => $faker->city(),
                'alamat' => $faker->streetAddress(),
                'rt' => $faker->numberBetween(1, 10),
                'rw' => $faker->numberBetween(1, 10),
                'no_hp' => Crypt::encryptString($faker->phoneNumber()),
                'nama_wali' => $faker->randomElement([$nama_ayah, $nama_ibu]),
                'nama_ayah' => $nama_ayah,
                'nik_ayah' => Crypt::encryptString($faker->nik()),
                'pendidikan_pekerjaan_ayah' => $faker->randomElement(['SMA / Wiraswasta', 'S1 / PNS', 'SMP / Buruh']),
                'nama_ibu' => $nama_ibu,
                'nik_ibu' => Crypt::encryptString($faker->nik()),
                'pendidikan_pekerjaan_ibu' => $faker->randomElement(['SMA / Ibu Rumah Tangga', 'S1 / PNS', 'SMA / Pedagang']),
                'provinsi' => 'JAWA TENGAH',
                'kabupaten' => $faker->randomElement(['KABUPATEN BANYUMAS', 'KABUPATEN CILACAP']),
                'kecamatan' => $faker->randomElement(['PURWOKERTO TIMUR', 'BATURRADEN']),
                'desa_kelurahan' => $faker->randomElement(['MERSI', 'KARANGKLESEM']),
                'nama_puskesmas' => 'Puskesmas Kembaran Kulon', 
                'nama_posyandu' => 'Posyandu Anyelir',        
                'is_arsip' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);


            $kategori_kesehatan = $i % 5;
            
            $target_z_bb = 0;
            $target_z_tb = 0;

            if ($kategori_kesehatan == 0) { // Normal
                $target_z_bb = $faker->randomFloat(2, -1.5, 1.0);
                $target_z_tb = $faker->randomFloat(2, -1.5, 1.5);
            } elseif ($kategori_kesehatan == 1) { // Stunting (TB jauh tertinggal)
                $target_z_bb = $faker->randomFloat(2, -2.0, -1.0);
                $target_z_tb = $faker->randomFloat(2, -3.8, -2.1);
            } elseif ($kategori_kesehatan == 2) { // Gizi Buruk (BB sangat tertinggal)
                $target_z_bb = $faker->randomFloat(2, -4.5, -3.1); // Di bawah -3 SD
                $target_z_tb = $faker->randomFloat(2, -2.5, -1.5);
            } elseif ($kategori_kesehatan == 3) { // BB Kurang
                $target_z_bb = $faker->randomFloat(2, -2.9, -2.1); // Di antara -3 dan -2 SD
                $target_z_tb = $faker->randomFloat(2, -1.5, -0.5);
            } elseif ($kategori_kesehatan == 4) { // Obesitas
                $target_z_bb = $faker->randomFloat(2, 2.5, 3.5);
                $target_z_tb = $faker->randomFloat(2, 0.5, 2.0);
            }

            $master_tbu_0 = DB::table('master_tbu')->where('jenis_kelamin', $gender)->where('umur_bulan', 0)->first();
            $master_bbu_0 = DB::table('master_bbu')->where('jenis_kelamin', $gender)->where('umur_bulan', 0)->first();
            
            $berat_lahir = $this->generateValueFromZScore($target_z_bb, $master_bbu_0->median, $master_bbu_0->plus_1_sd, $master_bbu_0->minus_1_sd);
            $panjang_lahir = $this->generateValueFromZScore($target_z_tb, $master_tbu_0->median, $master_tbu_0->plus_1_sd, $master_tbu_0->minus_1_sd);

            $berat_lahir = max(2.0, $berat_lahir); 

            DB::table('pasien_riwayat_kelahiran')->insert([
                'pasien_id' => $pasien_id,
                'anak_ke' => rand(1, 3),
                'usia_kehamilan' => rand(36, 40),
                'berat_lahir' => $berat_lahir,
                'panjang_lahir' => $panjang_lahir,
                'lingkar_kepala_lahir' => $faker->randomFloat(2, 30, 35),
                'imd' => rand(0, 1),
                'riwayat_asi' => 'E1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $bb_sebelumnya = $berat_lahir;

            for ($bulan = 0; $bulan <= $usia_bulan_sekarang; $bulan++) {
                $tgl_periksa = clone $tgl_lahir;
                $tgl_periksa->addMonths($bulan);

                $master_tbu = DB::table('master_tbu')->where('jenis_kelamin', $gender)->where('umur_bulan', $bulan)->first();
                $master_bbu = DB::table('master_bbu')->where('jenis_kelamin', $gender)->where('umur_bulan', $bulan)->first();

                if (!$master_tbu || !$master_bbu) continue;

                $z_bb_current = $target_z_bb + $faker->randomFloat(2, -0.2, 0.2);
                $z_tb_current = $target_z_tb + $faker->randomFloat(2, -0.2, 0.2);

                $tb = $this->generateValueFromZScore($z_tb_current, $master_tbu->median, $master_tbu->plus_1_sd, $master_tbu->minus_1_sd);
                $bb = $this->generateValueFromZScore($z_bb_current, $master_bbu->median, $master_bbu->plus_1_sd, $master_bbu->minus_1_sd);

                $bb = max($bb_sebelumnya - 0.2, $bb); 
                $bb_sebelumnya = $bb;

                $zscore_tbu = $this->hitungZScore($tb, $master_tbu->median, $master_tbu->plus_1_sd, $master_tbu->minus_1_sd);
                $zscore_bbu = $this->hitungZScore($bb, $master_bbu->median, $master_bbu->plus_1_sd, $master_bbu->minus_1_sd);

                
                if ($zscore_bbu < -3.0) $status_gizi = 'Sangat Kurang';
                elseif ($zscore_bbu >= -3.0 && $zscore_bbu < -2.0) $status_gizi = 'Kurang';
                elseif ($zscore_bbu >= -2.0 && $zscore_bbu <= 1.0) $status_gizi = 'Berat Badan Normal';
                else $status_gizi = 'Risiko Berat Badan Lebih';

                if ($zscore_tbu < -3.0) $status_stunting = 'Sangat Pendek (Severely Stunted)';
                elseif ($zscore_tbu >= -3.0 && $zscore_tbu < -2.0) $status_stunting = 'Pendek (Stunted)';
                elseif ($zscore_tbu >= -2.0 && $zscore_tbu <= 3.0) $status_stunting = 'Normal';
                else $status_stunting = 'Tinggi';

                $master_bbtb = DB::table('master_bbtb')->where('jenis_kelamin', $gender)->orderByRaw('ABS(tinggi_badan_cm - ?)', [$tb])->first();
                $zscore_bbtb = 0;
                $status_bbtb = 'Gizi Baik (Normal)';
                
                if ($master_bbtb) {
                    $zscore_bbtb = $this->hitungZScore($bb, $master_bbtb->median, $master_bbtb->plus_1_sd, $master_bbtb->minus_1_sd);
                    if ($zscore_bbtb < -3.0) $status_bbtb = 'Gizi Buruk (Severely Wasted)';
                    elseif ($zscore_bbtb >= -3.0 && $zscore_bbtb < -2.0) $status_bbtb = 'Gizi Kurang (Wasted)';
                    elseif ($zscore_bbtb >= -2.0 && $zscore_bbtb <= 1.0) $status_bbtb = 'Gizi Baik (Normal)';
                    elseif ($zscore_bbtb > 1.0 && $zscore_bbtb <= 2.0) $status_bbtb = 'Berisiko Gizi Lebih';
                    elseif ($zscore_bbtb > 2.0 && $zscore_bbtb <= 3.0) $status_bbtb = 'Gizi Lebih (Overweight)';
                    else $status_bbtb = 'Obesitas (Obese)';
                }

                $kenaikan_bb = ($bulan == 0) ? 'naik' : $faker->randomElement(['naik', 'tidak_naik']);
                $keterangan_bb = ($bulan == 0) ? 'Pemeriksaan awal / berat lahir awal (Bulan ke-0)' : 
                                 ($kenaikan_bb == 'naik' ? 'Naik (N). Berat badan bertambah sesuai target KBM.' : 'Tidak Naik (T). Kenaikan kurang dari KBM.');

                $pemeriksaan_id = DB::table('pemeriksaan_bayi')->insertGetId([
                    'pasien_id' => $pasien_id,
                    'petugas_id' => $petugas_id,
                    'tgl_periksa' => $tgl_periksa->toDateString(),
                    'keterangan_umur' => $bulan . ' Bulan',
                    'usia_bulan' => $bulan,
                    'berat_badan' => $bb,
                    'tinggi_badan' => $tb,
                    'cara_ukur' => $bulan < 24 ? 'terlentang' : 'berdiri',
                    'lila' => $faker->randomFloat(1, 11, 16),
                    'lingkar_kepala' => $faker->randomFloat(1, 33, 50),
                    'status_gizi' => $status_gizi,
                    'status_stunting' => $status_stunting,
                    'status_bbtb' => $status_bbtb,
                    'zscore_bbu' => (string)$zscore_bbu,
                    'zscore_tbu' => (string)$zscore_tbu,
                    'zscore_bbtb' => (string)$zscore_bbtb,
                    'kenaikan_bb' => $kenaikan_bb,
                    'keterangan_bb' => $keterangan_bb,
                    'created_at' => $tgl_periksa,
                    'updated_at' => $tgl_periksa,
                ]);

                DB::table('pemeriksaan_intervensi_klinis')->insert([
                    'pemeriksaan_bayi_id' => $pemeriksaan_id,
                    'rambu_gizi' => $zscore_bbu >= -2.0 ? 'Hijau' : ($zscore_bbu < -3.0 ? 'Merah' : 'Kuning'),
                    'titik_pertumbuhan' => $kenaikan_bb == 'naik' ? 'Normal' : 'Perlu Perhatian',
                    'pitting_edema' => 'tidak ada',
                    'vitamin_a' => ($bulan % 6 == 0 && $bulan > 0) ? 1 : 0, 
                    'obat_cacing' => ($bulan % 12 == 0 && $bulan >= 12) ? 1 : 0, 
                    'asi_eksklusif' => $bulan <= 6 ? 1 : 0,
                    'created_at' => $tgl_periksa,
                    'updated_at' => $tgl_periksa,
                ]);
            }
        }
    }
}