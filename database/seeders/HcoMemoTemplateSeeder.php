<?php

namespace Database\Seeders;

use App\Models\Memos\MemoTeam;
use App\Models\Memos\MemoTemplate;
use Illuminate\Database\Seeder;

class HcoMemoTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $team = MemoTeam::where('team_name', 'HCO')->first();

        if (!$team) {
            $this->command?->error('Tim "HCO" belum ada. Buat tim HCO dulu di menu Kelola Tim, baru jalankan seeder ini lagi.');
            return;
        }

        // Dipakai sebagai created_by/updated_by. Diambil dari admin tim HCO yang
        // sudah terdaftar; kalau tim HCO belum punya admin, seeder dibatalkan
        // supaya tidak menyimpan created_by kosong.
        $actorId = $team->admins()->value('users.id');

        if (!$actorId) {
            $this->command?->error('Tim HCO belum punya admin terdaftar. Tambahkan admin tim HCO dulu (menu Kelola Tim) sebelum menjalankan seeder ini.');
            return;
        }

        foreach ($this->templates() as $data) {
            MemoTemplate::updateOrCreate(
                ['team_id' => $team->id, 'name' => $data['name']],
                array_merge($data, [
                    'team_id'    => $team->id,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ])
            );
        }

        $this->command?->info('5 template HCO berhasil disimpan untuk tim "' . $team->team_name . '".');
    }

    private function blankItem(array $dynamicColumns): array
    {
        return [[
            'keterangan'      => '',
            'dynamic_columns' => array_fill(0, count($dynamicColumns), ''),
            'tagihan'         => 0,
        ]];
    }

    private function templates(): array
    {
        $t1Cols = ['Beban PT', 'Unit Kerja', 'No. Doc.', 'Tagihan', 'Pembebanan Perusahaan', 'Pembebanan Karyawan'];
        $t2Cols = ['Beban PT', 'Unit Kerja', 'No. Invoice', 'Pembebanan Perusahaan', 'Pembebanan Karyawan', 'Tagihan'];
        $t3Cols = ['No. Memo', 'No. Invoice Rumah Sakit', 'Nama RS'];
        $t5Cols = ['Keterangan Pembayaran', 'Status Over Plafon'];

        return [
            [
                'name'                        => '1. Pembayaran Medical Karyawan - Rawat Jalan',
                'perihal'                     => 'Pembayaran Claim Pengobatan Karyawan',
                'kepada'                      => 'Head Finance & Accounting',
                'dari'                        => 'HCO Corporate',
                'instruksi'                   => 'Demikian atas kerjasamanya, kami ucapkan terimakasih.',
                'bank'                        => null,
                'atas_nama'                   => null,
                'no_rek'                      => null,
                'sertakan_rekening'           => true,
                'paragraf_pembuka'            => 'Mohon dapat dibayarkan claim medical karyawan dan dapat di transfer ke rekening sebagai berikut:',
                'keterangan_label'            => 'Nama Karyawan',
                'dynamic_columns_definition'  => $t1Cols,
                'items'                       => $this->blankItem($t1Cols),
            ],
            [
                'name'                        => '2a. Pembayaran Tagihan RSMT Sudirman',
                'perihal'                     => 'Pembayaran Tagihan Biaya Pengobatan',
                'kepada'                      => 'Head Finance & Accounting',
                'dari'                        => 'HCO Corporate',
                'instruksi'                   => 'Demikian atas perhatiannya, kami ucapkan terima kasih.',
                'bank'                        => 'BCA',
                'atas_nama'                   => 'PT. SAHID SAHIRMAN MEMORIAL HOSPITAL',
                'no_rek'                      => '526.530.1212',
                'sertakan_rekening'           => true,
                'paragraf_pembuka'            => 'Mohon dapat dibantu untuk pembayaran invoice biaya pengobatan karyawan di Rumah Sakit Murni Teguh Sudirman Jakarta dengan rincian sebagai berikut:',
                'keterangan_label'            => 'Nama Karyawan',
                'dynamic_columns_definition'  => $t2Cols,
                'items'                       => $this->blankItem($t2Cols),
            ],
            [
                'name'                        => '2b. Pembayaran Tagihan RSMT Pejaten',
                'perihal'                     => 'Pembayaran Tagihan Biaya Pengobatan',
                'kepada'                      => 'Head Finance & Accounting',
                'dari'                        => 'HCO Corporate',
                'instruksi'                   => 'Demikian atas perhatiannya, kami ucapkan terima kasih.',
                'bank'                        => 'BANK MANDIRI CAB JW MARRIOT',
                'atas_nama'                   => 'PT MURNI SADAR, TBK',
                'no_rek'                      => '1050022990000',
                'sertakan_rekening'           => true,
                'paragraf_pembuka'            => 'Mohon dapat dibantu untuk pembayaran invoice biaya pengobatan karyawan di Rumah Sakit Murni Teguh Pejaten Jakarta dengan rincian sebagai berikut:',
                'keterangan_label'            => 'Nama Karyawan',
                'dynamic_columns_definition'  => $t2Cols,
                'items'                       => $this->blankItem($t2Cols),
            ],
            [
                'name'                        => '2c. Pembayaran Tagihan RSMT Ciledug',
                'perihal'                     => 'Pembayaran Tagihan Biaya Pengobatan',
                'kepada'                      => 'Head Finance & Accounting',
                'dari'                        => 'HCO Corporate',
                'instruksi'                   => 'Demikian atas perhatiannya, kami ucapkan terima kasih.',
                'bank'                        => 'BANK MANDIRI',
                'atas_nama'                   => 'PT. MEDIKARYA AMINAH UTAMA',
                'no_rek'                      => '127.00.0231112.2',
                'sertakan_rekening'           => true,
                'paragraf_pembuka'            => 'Mohon dapat dibantu untuk pembayaran invoice biaya pengobatan karyawan di Rumah Sakit Murni Teguh Ciledug dengan rincian sebagai berikut:',
                'keterangan_label'            => 'Nama Karyawan',
                'dynamic_columns_definition'  => $t2Cols,
                'items'                       => $this->blankItem($t2Cols),
            ],
            [
                'name'                        => '2d. Pembayaran Tagihan Klinik Gama',
                'perihal'                     => 'Pembayaran Tagihan Biaya Pengobatan',
                'kepada'                      => 'Head Finance & Accounting',
                'dari'                        => 'HCO Corporate',
                'instruksi'                   => 'Demikian atas perhatiannya, kami ucapkan terima kasih.',
                'bank'                        => 'BANK MANDIRI',
                'atas_nama'                   => 'PT BERKAT TEGUH UTAMA',
                'no_rek'                      => '105-00138-8484-0',
                'sertakan_rekening'           => true,
                'paragraf_pembuka'            => 'Mohon dapat dibantu untuk pembayaran invoice biaya pengobatan karyawan di Murni Teguh Clinic Gama dengan rincian sebagai berikut:',
                'keterangan_label'            => 'Nama Karyawan',
                'dynamic_columns_definition'  => $t2Cols,
                'items'                       => $this->blankItem($t2Cols),
            ],
            [
                'name'                        => '3. Pemberitahuan Over Plafon Medical',
                'perihal'                     => 'Pemberitahuan Selisih (Beban Pribadi) Biaya Pengobatan',
                'kepada'                      => '',
                'dari'                        => 'HRD Corporate',
                'instruksi'                   => 'Mohon agar selisih biaya tersebut dikembalikan ke Perusahaan melalui pemotongan gaji Saudara/i atau dibayarkan langsung ke rekening perusahaan. Demikian informasi yang kami sampaikan. Terima kasih.',
                'bank'                        => null,
                'atas_nama'                   => null,
                'no_rek'                      => null,
                'sertakan_rekening'           => true,
                'paragraf_pembuka'            => 'Terkait dengan memo di bawah ini perihal informasi selisih biaya pengobatan dan perawatan yang tidak dapat ditanggung oleh perusahaan (plafon rawat jalan habis). Dengan rincian sebagai berikut:',
                'keterangan_label'            => 'Nama Karyawan',
                'dynamic_columns_definition'  => $t3Cols,
                'items'                       => $this->blankItem($t3Cols),
            ],
            [
                'name'                        => '4. Pemberitahuan Biaya Medical Tidak Tertanggung',
                'perihal'                     => 'Pemberitahuan Selisih (Beban Pribadi) Biaya Pengobatan',
                'kepada'                      => '',
                'dari'                        => 'HRD Corporate',
                'instruksi'                   => 'Mohon agar selisih biaya tersebut dikembalikan ke Perusahaan melalui pemotongan gaji Saudara/i atau dibayarkan langsung ke rekening perusahaan. Demikian informasi yang kami sampaikan. Terima kasih.',
                'bank'                        => null,
                'atas_nama'                   => null,
                'no_rek'                      => null,
                'sertakan_rekening'           => true,
                'paragraf_pembuka'            => 'Terkait dengan memo di bawah ini perihal informasi selisih biaya pengobatan dan perawatan yang tidak dapat ditanggung oleh perusahaan (plafon rawat jalan tahun berjalan). Dengan rincian sebagai berikut:',
                'keterangan_label'            => 'Nama Karyawan',
                'dynamic_columns_definition'  => $t3Cols,
                'items'                       => $this->blankItem($t3Cols),
            ],
            [
                'name'                        => '5. Pemberitahuan Lunas Plafon Medical',
                'perihal'                     => 'Pemberitahuan Status Pembayaran Selisih (Beban Pribadi) Biaya Pengobatan',
                'kepada'                      => 'Head Finance & Accounting',
                'dari'                        => 'HC Corporate',
                'instruksi'                   => 'Mohon agar biaya pengobatan dan perawatan tidak tertanggung tersebut dihapuskan dari pencatatan jurnal tim Accounting. Demikian informasi yang kami sampaikan. Terima kasih.',
                'bank'                        => null,
                'atas_nama'                   => null,
                'no_rek'                      => null,
                'sertakan_rekening'           => false,
                'paragraf_pembuka'            => 'Terkait dengan memo di bawah ini perihal informasi biaya pengobatan dan perawatan yang tidak tertanggung. Dengan rincian sebagai berikut:',
                'keterangan_label'            => 'Nama Karyawan',
                'dynamic_columns_definition'  => $t5Cols,
                'items'                       => $this->blankItem($t5Cols),
            ],
        ];
    }
}