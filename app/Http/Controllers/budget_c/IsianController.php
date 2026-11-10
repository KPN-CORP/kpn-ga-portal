<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Models\budget_m\BudgetForm;
use App\Models\budget_m\BudgetFormIsian;
use App\Models\budget_m\BudgetPengaturan;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetFormService;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Http\Request;

/** Mengisi form realisasi: satu isian = satu invoice = satu baris tagihan di item tujuan form. */
class IsianController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();
        $tahun = BudgetTahun::pilih($r->query('tahun'));

        $semuaForm = BudgetForm::terlihatOleh($user)->with('item.sub.kategori')->orderByDesc('aktif')->orderBy('nama')->get();
        $form = $r->filled('form')
            ? $semuaForm->firstWhere('id', (int) $r->query('form'))
            : $semuaForm->firstWhere('aktif', true);

        $daftar = null;
        $ringkas = ['dpp' => 0, 'ppn' => 0, 'materai' => 0, 'total' => 0];
        $kolom = collect();
        $bisaIsi = false;

        if ($form) {
            $form->load('fields');
            $bisaIsi = $form->aktif && BudgetPeran::bisaInputTagihan($form->item, $user);

            $q = $form->isian()->with('penginput')
                ->whereBetween('tanggal', [$tahun . '-01-01', $tahun . '-12-31'])
                ->when($r->query('bulan'), fn ($x, $v) => $x->whereMonth('tanggal', (int) $v))
                ->when(trim((string) $r->query('q')) !== '', function ($x) use ($r) {
                    $v = '%' . trim((string) $r->query('q')) . '%';
                    $x->where('no_dokumen', 'like', $v);
                });

            $ringkas = [
                'dpp' => (float) (clone $q)->sum('dpp'),
                'ppn' => (float) (clone $q)->sum('ppn'),
                'materai' => (float) (clone $q)->sum('materai'),
                'total' => (float) (clone $q)->sum('total'),
            ];
            $perHalaman = in_array((int) $r->query('per'), [25, 50, 100], true) ? (int) $r->query('per') : 25;
            $daftar = $q->orderByDesc('tanggal')->orderByDesc('id')->paginate($perHalaman)->withQueryString();

            // Kolom: semua field aktif, ditambah field arsip yang masih punya nilai di halaman ini (data lama tetap terbaca)
            $kolom = $form->fields->filter(function ($f) use ($daftar) {
                return $f->aktif || $daftar->contains(fn ($i) => array_key_exists($f->id, (array) ($i->data ?? [])));
            })->values();
        }

        return view('budget_p.form.isi', [
            'tahun' => $tahun,
            'daftarTahun' => BudgetTahun::daftar(),
            'tahunRow' => BudgetTahun::where('tahun', $tahun)->first(),
            'semuaForm' => $semuaForm,
            'form' => $form,
            'daftar' => $daftar,
            'kolom' => $kolom,
            'ringkas' => $ringkas,
            'bisaIsi' => $bisaIsi,
            'perHalaman' => $daftar ? $daftar->perPage() : 25,
            'filter' => ['bulan' => $r->query('bulan'), 'q' => trim((string) $r->query('q'))],
            'ppnPersen' => BudgetPengaturan::ppnPersen(),
            'materai' => BudgetPengaturan::materaiNominal(),
        ]);
    }

    public function simpan(Request $r)
    {
        $user = $r->user();
        $form = BudgetForm::terlihatOleh($user)->with(['item', 'fieldAktif'])->findOrFail($r->input('form_id'));
        abort_unless(BudgetPeran::bisaInputTagihan($form->item, $user), 403);

        $isian = $r->filled('id') ? BudgetFormIsian::where('form_id', $form->id)->findOrFail($r->input('id')) : null;

        // Aturan dasar + satu aturan per field aktif (tipe dan wajib ditentukan pembuat form)
        $aturan = [
            'tanggal' => ['required', 'date'],
            'no_dokumen' => ['nullable', 'string', 'max:100'],
            'dpp' => ['required', 'numeric', 'min:0', 'max:99999999999999'],
            'kena_ppn' => ['nullable', 'boolean'],
            'kena_materai' => ['nullable', 'boolean'],
        ];
        $nama = ['dpp' => 'nilai (DPP)', 'no_dokumen' => 'nomor dokumen'];
        foreach ($form->fieldAktif as $f) {
            $aturan['f.' . $f->id] = $f->aturan();
            $nama['f.' . $f->id] = $f->label;
        }
        $d = $r->validate($aturan, [], $nama);

        $data = [];
        foreach ($form->fieldAktif as $f) {
            $v = $d['f'][$f->id] ?? null;
            $data[(string) $f->id] = ($v === null || $v === '') ? null : $v;
        }

        try {
            $baru = BudgetFormService::simpan($form, $isian, [
                'tanggal' => $d['tanggal'],
                'no_dokumen' => $d['no_dokumen'] ?? null,
                'dpp' => $d['dpp'],
                'kena_ppn' => $r->boolean('kena_ppn'),
                'kena_materai' => $r->boolean('kena_materai'),
                'data' => $data,
            ], $user);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['isian' => $e->getMessage()]);
        }

        return redirect()->route('budgeting.isian', ['form' => $form->id, 'tahun' => $baru->tanggal->year])
            ->with('ok', 'Isian disimpan. Total ' . 'Rp ' . number_format((float) $baru->total, 0, ',', '.') . ' tercatat sebagai realisasi di item "' . $form->item->nama . '" bulan ' . $baru->tanggal->format('m/Y') . '.');
    }

    public function hapus(Request $r, BudgetFormIsian $isian)
    {
        $user = $r->user();
        $form = BudgetForm::terlihatOleh($user)->with('item')->findOrFail($isian->form_id);
        abort_unless(BudgetPeran::bisaInputTagihan($form->item, $user), 403);

        BudgetFormService::hapus($isian, $user);
        return back()->with('ok', 'Isian dihapus dan realisasinya dikurangi dari budget.');
    }
}
