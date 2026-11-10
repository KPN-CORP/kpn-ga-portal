<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Http\Requests\budget_r\SimpanFormRequest;
use App\Models\budget_m\BudgetForm;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetPengaturan;
use App\Models\budget_m\BudgetTagihan;
use App\Support\budget_s\BudgetCatat;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pembuat form realisasi. Admin, Dept, dan PIC (hanya untuk item milik Dept-nya).
 * Aturan: item tujuan harus milik Dept pembuat form; item otomatis terkunci dari input manual selama form aktif;
 * field yang sudah punya isian tidak dihapus (diarsipkan); tarif PPN dan nominal materai dari pengaturan.
 */
class FormController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();

        $forms = BudgetForm::terlihatOleh($user)->with(['item.sub.kategori', 'fields', 'pembuat'])->withCount('isian')
            ->orderByDesc('aktif')->orderBy('nama')->get();

        $items = BudgetItem::terlihatOleh($user)->where('aktif', true)->with('sub.kategori')->orderBy('nama')->get()
            ->filter(fn ($i) => BudgetPeran::bisaInputTagihan($i, $user))->values();
        $dataItem = $items->map(fn ($i) => [
            'id' => $i->id,
            'label' => $i->sub->kategori->nama . ' / ' . $i->sub->nama . ' / ' . $i->nama . ' (' . ($i->pt ?: '-') . ') - ' . BudgetPeran::namaDept($i->dept_head_user_id),
        ])->values();

        $manual = BudgetTagihan::whereNull('isian_id')->whereIn('item_id', $forms->pluck('item_id')->unique())
            ->selectRaw('item_id, COUNT(*) AS c')->groupBy('item_id')->pluck('c', 'item_id');

        // Data form untuk modal ubah (disimpan di atribut tombol)
        $dataForm = $forms->mapWithKeys(fn ($f) => [$f->id => [
            'id' => $f->id, 'nama' => $f->nama, 'deskripsi' => $f->deskripsi, 'item_id' => $f->item_id,
            'pakai_ppn' => $f->pakai_ppn, 'pakai_materai' => $f->pakai_materai, 'ada_isian' => $f->isian_count > 0,
            'fields' => $f->fields->where('aktif', true)->map(fn ($x) => [
                'id' => $x->id, 'label' => $x->label, 'tipe' => $x->tipe, 'pilihan' => $x->pilihan, 'wajib' => $x->wajib,
            ])->values(),
        ]]);

        return view('budget_p.form.index', [
            'forms' => $forms,
            'dataItem' => $dataItem,
            'dataForm' => $dataForm,
            'manual' => $manual,
            'admin' => BudgetPeran::isAdmin($user),
            'ppnPersen' => BudgetPengaturan::ppnPersen(),
            'materai' => BudgetPengaturan::materaiNominal(),
        ]);
    }

    public function simpan(SimpanFormRequest $r)
    {
        $user = $r->user();
        $item = BudgetItem::findOrFail($r->input('item_id'));
        abort_unless(BudgetPeran::bisaInputTagihan($item, $user), 403);

        $edit = $r->filled('id');
        $form = $edit ? BudgetForm::terlihatOleh($user)->findOrFail($r->input('id')) : null;
        $adaIsian = $form ? $form->isian()->exists() : false;

        // Item tujuan tidak boleh diganti bila form sudah punya isian (isian lama terikat ke item itu)
        if ($form && $adaIsian && (int) $form->item_id !== (int) $item->id) {
            return back()->withInput()->withErrors(['item_id' => 'Item tujuan tidak bisa diganti karena form ini sudah punya isian. Arsipkan form ini dan buat form baru bila perlu.']);
        }

        // Dept pemilik form = Dept pemilik item (data tiap Dept tetap terpisah)
        $dept = (int) $item->dept_head_user_id;
        $kembar = BudgetForm::where('nama', $r->input('nama'))->where('dept_head_user_id', $dept)
            ->when($form, fn ($q) => $q->where('id', '<>', $form->id))->exists();
        if ($kembar) {
            return back()->withInput()->withErrors(['nama' => 'Sudah ada form dengan nama itu di departemen ini.']);
        }

        $data = [
            'nama' => trim($r->input('nama')),
            'deskripsi' => $r->input('deskripsi'),
            'item_id' => $item->id,
            'dept_head_user_id' => $dept,
            'pakai_ppn' => $r->boolean('pakai_ppn'),
            'pakai_materai' => $r->boolean('pakai_materai'),
        ];

        $hasil = DB::transaction(function () use ($r, $form, $data, $user, $adaIsian) {
            $sebelum = $form?->getAttributes();
            if ($form) {
                $form->update($data);
            } else {
                $form = BudgetForm::create($data + ['aktif' => true, 'dibuat_oleh' => $user->id]);
            }

            $ada = $form->fields()->get()->keyBy('id');
            $dipakai = [];
            foreach (array_values((array) $r->input('fields', [])) as $i => $f) {
                $tipe = $f['tipe'];
                $isi = [
                    'label' => trim($f['label']),
                    'tipe' => $tipe,
                    'pilihan' => $tipe === 'pilihan' ? implode("\n", array_filter(array_map('trim', preg_split('/\R/', (string) ($f['pilihan'] ?? ''))))) : null,
                    'wajib' => !empty($f['wajib']),
                    'urut' => $i + 1,
                    'aktif' => true,
                ];
                $fid = !empty($f['id']) ? (int) $f['id'] : null;
                if ($fid && $ada->has($fid)) {
                    $lama = $ada[$fid];
                    // Tipe field yang sudah punya isian tidak diubah (nilai lama bisa tidak cocok lagi)
                    if ($adaIsian) {
                        $isi['tipe'] = $lama->tipe;
                        $isi['pilihan'] = $lama->tipe === 'pilihan' ? $isi['pilihan'] : null;
                    }
                    $lama->update($isi);
                    $dipakai[] = $lama->id;
                } else {
                    $dipakai[] = $form->fields()->create($isi)->id;
                }
            }
            // Field yang dibuang dari daftar: diarsipkan bila form sudah punya isian, dihapus bila belum
            foreach ($ada as $id => $fl) {
                if (in_array($id, $dipakai, true)) {
                    continue;
                }
                $adaIsian ? $fl->update(['aktif' => false]) : $fl->delete();
            }

            BudgetCatat::tulis($sebelum ? 'ubah' : 'tambah', 'form', $form->id, $form->nama, $sebelum, $form->fresh()->getAttributes(), ['item_id' => $form->item_id]);
            return $form;
        });

        BudgetForm::lupakanKunci();

        $manual = BudgetTagihan::whereNull('isian_id')->where('item_id', $item->id)->count();
        $pesan = 'Form "' . $hasil->nama . '" disimpan. Item "' . $item->nama . '" sekarang terkunci dari input manual.';
        if ($manual > 0) {
            $pesan .= ' Item ini punya ' . $manual . ' tagihan manual lama: datanya tetap tersimpan dan tetap dihitung, tetapi tidak bisa diubah selama form ini aktif.';
        }
        return redirect()->route('budgeting.form')->with('ok', $pesan);
    }

    /** Arsipkan atau aktifkan kembali. Form yang diarsipkan tidak mengunci item lagi dan tidak bisa diisi. */
    public function arsip(Request $r, BudgetForm $form)
    {
        $user = $r->user();
        abort_unless(BudgetForm::terlihatOleh($user)->whereKey($form->id)->exists(), 403);
        abort_unless(BudgetPeran::bisaInputTagihan($form->item, $user), 403);

        $sebelum = $form->getAttributes();
        $form->update(['aktif' => !$form->aktif]);
        BudgetForm::lupakanKunci();
        BudgetCatat::tulis($form->aktif ? 'aktifkan' : 'arsip', 'form', $form->id, $form->nama, $sebelum, $form->getAttributes(), ['item_id' => $form->item_id]);

        return back()->with('ok', $form->aktif
            ? 'Form "' . $form->nama . '" diaktifkan. Item tujuan terkunci dari input manual.'
            : 'Form "' . $form->nama . '" diarsipkan. Item tujuan bisa diisi manual lagi; baris dari form ini tetap tidak bisa diubah dari halaman Realisasi.');
    }

    /** Hanya form yang belum punya isian yang boleh dihapus. */
    public function hapus(Request $r, BudgetForm $form)
    {
        $user = $r->user();
        abort_unless(BudgetForm::terlihatOleh($user)->whereKey($form->id)->exists(), 403);
        abort_unless(BudgetPeran::bisaInputTagihan($form->item, $user), 403);

        if ($form->isian()->exists()) {
            return back()->withErrors(['form' => 'Form ini sudah punya isian, tidak bisa dihapus. Arsipkan saja.']);
        }
        $sebelum = $form->getAttributes();
        DB::transaction(function () use ($form) {
            $form->fields()->delete();
            $form->delete();
        });
        BudgetForm::lupakanKunci();
        BudgetCatat::tulis('hapus', 'form', $form->id, $form->nama, $sebelum, null, ['item_id' => $form->item_id]);
        return back()->with('ok', 'Form dihapus.');
    }

    /** Tarif PPN dan nominal materai (Admin). Isian lama tidak berubah; hanya isian baru yang memakai angka baru. */
    public function pengaturan(Request $r)
    {
        abort_unless(BudgetPeran::isAdmin($r->user()), 403);
        $d = $r->validate([
            'ppn_persen' => ['required', 'numeric', 'between:0,100'],
            'materai_nominal' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ]);
        $sebelum = ['ppn_persen' => BudgetPengaturan::ppnPersen(), 'materai_nominal' => BudgetPengaturan::materaiNominal()];
        BudgetPengaturan::simpan('ppn_persen', $d['ppn_persen']);
        BudgetPengaturan::simpan('materai_nominal', $d['materai_nominal']);
        BudgetCatat::tulis('ubah', 'pengaturan', null, 'Tarif PPN dan materai', $sebelum, $d);
        return back()->with('ok', 'Pengaturan disimpan. Isian yang sudah dicatat tidak berubah.');
    }
}
