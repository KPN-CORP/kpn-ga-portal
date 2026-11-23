<?php

namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\Transaksi;
use App\Models\StockCtl\Barang;
use App\Models\StockCtl\AreaKerja;
use App\Models\StockCtl\Stok;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $access = session('stock_ctl_access');
        $query = Transaksi::with('barang', 'areaAsal', 'areaTujuan', 'user', 'pembatal');

        if (!$access['is_super']) {
            $query->where(function($q) use ($access) {
                $q->whereHas('areaAsal', function($sub) use ($access) {
                    $sub->where('id_bisnis_unit', $access['id_bisnis_unit']);
                })->orWhereHas('areaTujuan', function($sub) use ($access) {
                    $sub->where('id_bisnis_unit', $access['id_bisnis_unit']);
                });
            });
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('tanggal_awal')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_awal);
        }
        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_akhir);
        }

        $transaksi = $query->orderBy('tanggal', 'desc')->paginate(15);
        $jenisList = ['masuk', 'keluar', 'transfer', 'opname'];

        return view('stock-ctl.transaksi.index', compact('transaksi', 'jenisList'));
    }

    public function createMasuk()
    {
        $access = session('stock_ctl_access');
        // Urutkan barang berdasarkan nama
        $barang = Barang::orderBy('nama_barang', 'asc')->get();
        $areas = $access['is_super'] 
            ? AreaKerja::with('bisnisUnit')->get() 
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();

        return view('stock-ctl.transaksi.masuk', compact('barang', 'areas'));
    }

    public function storeMasuk(Request $request)
    {
        $request->validate([
            'id_area_tujuan' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'id_barang'      => 'required|exists:stock_ctl_barang,id_barang',
            'jumlah'         => 'required|numeric|min:1',
            'no_ref'         => 'required|string|max:100',
            'keterangan'     => 'nullable|string',
        ], [
            'no_ref.required' => 'Nomor referensi (faktur/PO) wajib diisi untuk barang masuk agar bisa ditelusuri.',
        ]);

        $access = session('stock_ctl_access');
        if (!$access['is_super']) {
            $area = AreaKerja::find($request->id_area_tujuan);
            if (!$area || $area->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403);
            }
        }

        DB::transaction(function () use ($request) {
            Stok::updateOrCreate(
                ['id_barang' => $request->id_barang, 'id_area_kerja' => $request->id_area_tujuan],
                ['jumlah' => DB::raw('jumlah + ' . $request->jumlah)]
            );

            Transaksi::create([
                'jenis'          => 'masuk',
                'id_barang'      => $request->id_barang,
                'jumlah'         => $request->jumlah,
                'id_area_tujuan' => $request->id_area_tujuan,
                'no_ref'         => $request->no_ref,
                'keterangan'     => $request->keterangan,
                'id_user'        => Auth::id(),
            ]);
        });

        return redirect()->route('stock-ctl.stok.index')->with('success', 'Barang masuk berhasil dicatat.');
    }

    public function createKeluar()
    {
        $access = session('stock_ctl_access');
        // Urutkan barang berdasarkan nama
        $barang = Barang::orderBy('nama_barang', 'asc')->get();
        $areas = $access['is_super'] 
            ? AreaKerja::with('bisnisUnit')->get() 
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();

        return view('stock-ctl.transaksi.keluar', compact('barang', 'areas'));
    }

    public function storeKeluar(Request $request)
    {
        $request->validate([
            'id_area_asal' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'id_barang'    => 'required|exists:stock_ctl_barang,id_barang',
            'jumlah'       => 'required|numeric|min:1',
            'no_ref'       => 'nullable|string',
            'keterangan'   => 'nullable|string',
        ]);

        $access = session('stock_ctl_access');
        if (!$access['is_super']) {
            $area = AreaKerja::find($request->id_area_asal);
            if (!$area || $area->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403);
            }
        }

        DB::transaction(function () use ($request) {
            $stok = Stok::where('id_barang', $request->id_barang)
                ->where('id_area_kerja', $request->id_area_asal)
                ->first();

            if (!$stok || $stok->jumlah < $request->jumlah) {
                throw new \Exception('Stok tidak mencukupi.');
            }

            $stok->decrement('jumlah', $request->jumlah);

            Transaksi::create([
                'jenis'        => 'keluar',
                'id_barang'    => $request->id_barang,
                'jumlah'       => $request->jumlah,
                'id_area_asal' => $request->id_area_asal,
                'no_ref'       => $request->no_ref,
                'keterangan'   => $request->keterangan,
                'id_user'      => Auth::id(),
            ]);
        });

        return redirect()->route('stock-ctl.stok.index')->with('success', 'Barang keluar berhasil dicatat.');
    }

    public function createTransfer()
    {
        $access = session('stock_ctl_access');
        $barang = Barang::all();
        $areas = $access['is_super'] 
            ? AreaKerja::with('bisnisUnit')->get() 
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();
        return view('stock-ctl.transaksi.transfer', compact('barang', 'areas'));
    }

    public function storeTransfer(Request $request)
    {
        $request->validate([
            'id_area_asal'   => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'id_area_tujuan' => 'required|exists:stock_ctl_area_kerja,id_area_kerja|different:id_area_asal',
            'id_barang'      => 'required|exists:stock_ctl_barang,id_barang',
            'jumlah'         => 'required|numeric|min:1',
            'keterangan'     => 'nullable|string',
        ]);

        $access = session('stock_ctl_access');
        if (!$access['is_super']) {
            $areaAsal = AreaKerja::find($request->id_area_asal);
            $areaTujuan = AreaKerja::find($request->id_area_tujuan);
            if (!$areaAsal || $areaAsal->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403, 'Area asal tidak sesuai dengan unit Anda.');
            }
            if (!$areaTujuan || $areaTujuan->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403, 'Area tujuan harus berada dalam unit yang sama.');
            }
        }

        $stokAsal = Stok::where('id_barang', $request->id_barang)
                        ->where('id_area_kerja', $request->id_area_asal)
                        ->first();

        if (!$stokAsal || $stokAsal->jumlah < $request->jumlah) {
            $stokTersedia = $stokAsal ? $stokAsal->jumlah : 0;
            return redirect()->back()
                             ->with('error', "Stok di area asal tidak mencukupi. Tersedia: {$stokTersedia}")
                             ->withInput();
        }

        try {
            DB::transaction(function () use ($request) {
                Stok::where('id_barang', $request->id_barang)
                    ->where('id_area_kerja', $request->id_area_asal)
                    ->decrement('jumlah', $request->jumlah);

                Stok::updateOrCreate(
                    ['id_barang' => $request->id_barang, 'id_area_kerja' => $request->id_area_tujuan],
                    ['jumlah' => DB::raw('jumlah + ' . $request->jumlah)]
                );

                Transaksi::create([
                    'jenis'          => 'transfer',
                    'id_barang'      => $request->id_barang,
                    'jumlah'         => $request->jumlah,
                    'id_area_asal'   => $request->id_area_asal,
                    'id_area_tujuan' => $request->id_area_tujuan,
                    'keterangan'     => $request->keterangan,
                    'id_user'        => Auth::id(),
                ]);
            });

            return redirect()->route('stock-ctl.stok.index')
                             ->with('success', 'Transfer berhasil.');
        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Gagal melakukan transfer: ' . $e->getMessage())
                             ->withInput();
        }
    }

    /**
     * Batalkan sebuah transaksi. Tidak menghapus data (agar riwayat tetap utuh),
     * melainkan:
     * 1) Membalik efek stok yang ditimbulkan transaksi asal
     * 2) Menandai transaksi asal sebagai 'dibatalkan'
     * 3) Membuat transaksi baru berstatus 'koreksi' sebagai jejak pembalik
     */
    public function batalkan(Request $request, $id)
    {
        $request->validate([
            'alasan' => 'required|string|max:500',
        ]);

        $access = session('stock_ctl_access');
        if (!$access['is_admin'] && !$access['is_super']) {
            abort(403, 'Hanya admin yang bisa membatalkan transaksi.');
        }

        $transaksi = Transaksi::with('areaAsal', 'areaTujuan', 'barang')->findOrFail($id);

        if (!$access['is_super']) {
            $unitAsal   = $transaksi->areaAsal->id_bisnis_unit ?? null;
            $unitTujuan = $transaksi->areaTujuan->id_bisnis_unit ?? null;
            if ($unitAsal != $access['id_bisnis_unit'] && $unitTujuan != $access['id_bisnis_unit']) {
                abort(403, 'Transaksi ini bukan milik unit Anda.');
            }
        }

        if ($transaksi->status !== Transaksi::STATUS_AKTIF) {
            return back()->withErrors('Transaksi ini sudah ' . $transaksi->status . ', tidak bisa dibatalkan lagi.');
        }

        try {
            DB::transaction(function () use ($transaksi, $request) {
                // Balikkan efek stok sesuai jenis transaksi asal
                if ($transaksi->jenis === 'masuk') {
                    $stok = Stok::where('id_barang', $transaksi->id_barang)
                        ->where('id_area_kerja', $transaksi->id_area_tujuan)
                        ->first();
                    if (!$stok || $stok->jumlah < $transaksi->jumlah) {
                        throw new \Exception('Stok saat ini sudah tidak cukup untuk membatalkan transaksi masuk ini (kemungkinan sebagian sudah terpakai).');
                    }
                    $stok->decrement('jumlah', $transaksi->jumlah);
                } elseif ($transaksi->jenis === 'keluar') {
                    Stok::updateOrCreate(
                        ['id_barang' => $transaksi->id_barang, 'id_area_kerja' => $transaksi->id_area_asal],
                        ['jumlah' => DB::raw('jumlah + ' . $transaksi->jumlah)]
                    );
                } elseif (in_array($transaksi->jenis, ['transfer', 'opname'])) {
                    // Balikkan: tambahkan kembali ke asal (jika ada), kurangi dari tujuan (jika ada)
                    if ($transaksi->id_area_asal) {
                        Stok::updateOrCreate(
                            ['id_barang' => $transaksi->id_barang, 'id_area_kerja' => $transaksi->id_area_asal],
                            ['jumlah' => DB::raw('jumlah + ' . $transaksi->jumlah)]
                        );
                    }
                    if ($transaksi->id_area_tujuan) {
                        $stokTujuan = Stok::where('id_barang', $transaksi->id_barang)
                            ->where('id_area_kerja', $transaksi->id_area_tujuan)
                            ->first();
                        if (!$stokTujuan || $stokTujuan->jumlah < $transaksi->jumlah) {
                            throw new \Exception('Stok di area tujuan sudah tidak cukup untuk membatalkan transaksi ini (kemungkinan sebagian sudah terpakai).');
                        }
                        $stokTujuan->decrement('jumlah', $transaksi->jumlah);
                    }
                }

                // Catat transaksi koreksi (jejak pembalik, arah area asal/tujuan dibalik dari transaksi asli)
                Transaksi::create([
                    'jenis'            => $transaksi->jenis,
                    'id_barang'        => $transaksi->id_barang,
                    'jumlah'           => $transaksi->jumlah,
                    'id_area_asal'     => $transaksi->id_area_tujuan,
                    'id_area_tujuan'   => $transaksi->id_area_asal,
                    'keterangan'       => 'Pembalik dari pembatalan transaksi #' . $transaksi->id_transaksi . '. Alasan: ' . $request->alasan,
                    'id_user'          => Auth::id(),
                    'no_ref'           => $transaksi->no_ref,
                    'status'           => Transaksi::STATUS_KOREKSI,
                    'id_transaksi_ref' => $transaksi->id_transaksi,
                ]);

                // Tandai transaksi asal sebagai dibatalkan
                $transaksi->update([
                    'status'            => Transaksi::STATUS_DIBATALKAN,
                    'dibatalkan_oleh'   => Auth::id(),
                    'dibatalkan_pada'   => now(),
                    'alasan_pembatalan' => $request->alasan,
                ]);
            });

            return redirect()->route('stock-ctl.transaksi.index')->with('success', "Transaksi #{$id} berhasil dibatalkan dan stok sudah dikembalikan.");
        } catch (\Exception $e) {
            return back()->withErrors('Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }

    public function cekStok(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|exists:stock_ctl_barang,id_barang',
            'id_area'   => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
        ]);

        $stok = Stok::where('id_barang', $request->id_barang)
                    ->where('id_area_kerja', $request->id_area)
                    ->first();

        $jumlah = $stok ? $stok->jumlah : 0;

        return response()->json(['stok' => $jumlah]);
    }
}