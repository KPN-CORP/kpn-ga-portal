<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Models\Supplies\SuppliesBarang;
use App\Models\Supplies\SuppliesPermintaan;
use App\Models\Supplies\SuppliesStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SuppliesPermintaanController extends Controller
{
    /** Jeda minimal (menit) sebelum user boleh mengajukan barang yang sama lagi. */
    public const JEDA_MENIT = 2;

    public function __construct()
    {
        $this->middleware('supplies.access:user');
    }

    public function index(Request $request)
    {
        $query = SuppliesPermintaan::with('barang')
            ->where('id_user_pemohon', Auth::id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $permintaan = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('supplies.permintaan.index', compact('permintaan'));
    }

    public function create()
    {
        return view('supplies.permintaan.create', [
            'barang'    => SuppliesBarang::all(),
            'jedaMenit' => self::JEDA_MENIT,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1|max:5',
            'items.*.id_barang'  => 'required|exists:supplies_barang,id',
            'items.*.jumlah'     => 'required|numeric|min:0.01',
            'items.*.keterangan' => 'nullable|string',
        ]);

        $userId        = Auth::id();
        $berhasil      = 0;
        $gagal         = [];
        $barangDiForm  = [];

        foreach ($request->items as $item) {
            $barang = SuppliesBarang::find($item['id_barang']);

            // Barang yang sama tidak boleh muncul dua kali dalam satu form.
            if (in_array($barang->id, $barangDiForm, true)) {
                $gagal[] = "{$barang->nama_barang} (barang yang sama tidak boleh diajukan dua kali dalam satu form)";
                continue;
            }
            $barangDiForm[] = $barang->id;

            $error = $this->ajukan($userId, $barang, $item);

            if ($error === null) {
                $berhasil++;
            } else {
                $gagal[] = "{$barang->nama_barang} ({$error})";
            }
        }

        if ($berhasil > 0 && empty($gagal)) {
            return redirect()->route('supplies.permintaan.index')
                ->with('success', "{$berhasil} permintaan berhasil diajukan.");
        }

        if ($berhasil > 0) {
            return redirect()->route('supplies.permintaan.index')
                ->with('warning', "{$berhasil} permintaan berhasil, namun beberapa gagal: " . implode('; ', $gagal));
        }

        return back()
            ->withErrors('Tidak ada permintaan yang bisa diajukan. ' . implode('; ', $gagal))
            ->withInput();
    }

    public function show($id)
    {
        $permintaan = SuppliesPermintaan::with('barang', 'pemohon', 'approver')->findOrFail($id);

        if ($permintaan->id_user_pemohon != Auth::id()) {
            abort(403);
        }

        return view('supplies.permintaan.show', compact('permintaan'));
    }

    /**
     * Ajukan satu item. Mengembalikan null bila berhasil, atau pesan alasan bila gagal.
     * Lock per (user, barang) mencegah dua request bersamaan (double-click) lolos bersamaan.
     */
    private function ajukan(int $userId, SuppliesBarang $barang, array $item): ?string
    {
        $lock = Cache::lock("supplies:permintaan:{$userId}:{$barang->id}", 10);

        if (! $lock->get()) {
            return 'permintaan sedang diproses, jangan klik dua kali';
        }

        try {
            $sisaDetik = $this->sisaJeda($userId, $barang->id);
            if ($sisaDetik > 0) {
                return 'baru saja diajukan, bisa diajukan lagi dalam ' . $this->formatDurasi($sisaDetik);
            }

            $stok = SuppliesStok::where('id_barang', $barang->id)->first();

            if (! $stok || $stok->jumlah < $item['jumlah']) {
                $tersedia = $stok ? $stok->jumlah : 0;
                return "butuh {$item['jumlah']}, tersedia {$tersedia} {$barang->satuan}";
            }

            SuppliesPermintaan::create([
                'id_user_pemohon' => $userId,
                'id_barang'       => $barang->id,
                'jumlah'          => $item['jumlah'],
                'keterangan'      => $item['keterangan'] ?? '',
                'status'          => 'pending',
            ]);

            return null;
        } finally {
            $lock->release();
        }
    }

    /** Sisa detik jeda untuk barang ini oleh user ini (0 = boleh mengajukan). Semua status dihitung. */
    private function sisaJeda(int $userId, int $barangId): int
    {
        $terakhir = SuppliesPermintaan::where('id_user_pemohon', $userId)
            ->where('id_barang', $barangId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first(['id', 'created_at']);

        if (! $terakhir) {
            return 0;
        }

        $bolehLagi = $terakhir->created_at->copy()->addMinutes(self::JEDA_MENIT);

        return max(0, $bolehLagi->timestamp - now()->timestamp);
    }

    private function formatDurasi(int $detik): string
    {
        $menit = intdiv($detik, 60);
        $sisa  = $detik % 60;

        return $menit > 0 ? "{$menit} menit {$sisa} detik" : "{$sisa} detik";
    }
}
