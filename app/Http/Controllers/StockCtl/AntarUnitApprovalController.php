<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\AntarUnitRequest;
use App\Models\StockCtl\Stok;
use App\Models\StockCtl\Transaksi;
use App\Models\StockCtl\AreaKerja;
use App\Models\StockCtl\UserProfil; // ← untuk ambil area penerima dari profil pemohon
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AntarUnitApprovalController extends Controller
{
    public function index()
    {
        $access = session('stock_ctl_access');
        if (!$access['is_admin'] && !$access['is_super']) {
            abort(403);
        }

        $query = AntarUnitRequest::with('barang', 'unitAsal', 'unitTujuan', 'pemohon')
            ->where('status', AntarUnitRequest::STATUS_PENDING);

        // Jika bukan superadmin, filter berdasarkan unit tujuan (unit pengirim)
        if (!$access['is_super']) {
            $query->where('id_bisnis_unit_tujuan', $access['id_bisnis_unit']);
        }

        $requests = $query->orderBy('created_at')->get();
        $pendingCount = $requests->count();

        return view('stock-ctl.antar-unit.approval', compact('requests', 'pendingCount'));
    }

    /**
     * AJAX: Ambil daftar area berdasarkan id_bisnis_unit
     */
    public function getAreasByUnit(Request $request)
    {
        $request->validate([
            'id_bisnis_unit' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
        ]);

        $areas = AreaKerja::where('id_bisnis_unit', $request->id_bisnis_unit)
            ->select('id_area_kerja', 'nama_area')
            ->get();

        return response()->json($areas);
    }

    /**
     * AJAX: Cek stok barang di area tertentu
     */
    public function cekStokUnit(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|exists:stock_ctl_barang,id_barang',
            'id_area'   => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
        ]);

        $stok = Stok::where('id_barang', $request->id_barang)
                    ->where('id_area_kerja', $request->id_area)
                    ->first();

        return response()->json(['stok' => $stok ? $stok->jumlah : 0]);
    }

    public function approve(Request $request, $id)
    {
        $access = session('stock_ctl_access');
        if (!$access['is_admin'] && !$access['is_super']) {
            abort(403);
        }

        $request->validate([
            'jumlah_setuju'       => 'required|numeric|min:0.01',
            'catatan'             => 'nullable|string|max:500',
            'id_area_pengirim'    => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
        ]);

        DB::beginTransaction();
        try {
            $antarRequest = AntarUnitRequest::with('barang')->findOrFail($id);

            // Jika bukan superadmin, pastikan admin unit tujuan yang approve
            if (!$access['is_super'] && $antarRequest->id_bisnis_unit_tujuan != $access['id_bisnis_unit']) {
                throw new \Exception('Anda bukan admin unit pengirim.');
            }

            if ($antarRequest->status != AntarUnitRequest::STATUS_PENDING) {
                throw new \Exception('Permintaan sudah diproses.');
            }

            $jumlahSetuju = $request->jumlah_setuju;
            $catatan      = $request->catatan;
            $areaPengirim = AreaKerja::findOrFail($request->id_area_pengirim);

            // Validasi area yang dipilih harus sesuai dengan unit tujuan request
            if ($areaPengirim->id_bisnis_unit != $antarRequest->id_bisnis_unit_tujuan) {
                throw new \Exception('Area yang dipilih tidak sesuai dengan unit pengirim.');
            }

            // === AMBIL AREA PENERIMA DARI PROFIL PEMOHON ===
            $profilPemohon = UserProfil::where('id_user', $antarRequest->id_user_pemohon)->first();
            if (!$profilPemohon || !$profilPemohon->id_area_kerja) {
                throw new \Exception('Pemohon belum memiliki area kerja. Hubungi admin untuk mengatur profil.');
            }
            $areaPenerima = AreaKerja::find($profilPemohon->id_area_kerja);
            if (!$areaPenerima) {
                throw new \Exception('Area kerja pemohon tidak ditemukan.');
            }
            // ============================================

            // Cek stok di area pengirim
            $stokPengirim = Stok::where('id_barang', $antarRequest->id_barang)
                ->where('id_area_kerja', $areaPengirim->id_area_kerja)
                ->first();

            if (!$stokPengirim || $stokPengirim->jumlah < $jumlahSetuju) {
                $tersedia = $stokPengirim ? $stokPengirim->jumlah : 0;
                $satuan = $antarRequest->barang->satuan ?? '';
                throw new \Exception("Stok di area {$areaPengirim->nama_area} tidak mencukupi. Tersedia: {$tersedia} {$satuan}");
            }

            // Kurangi stok pengirim
            $stokPengirim->decrement('jumlah', $jumlahSetuju);

            // Tambah stok penerima (area dari profil pemohon)
            Stok::updateOrCreate(
                ['id_barang' => $antarRequest->id_barang, 'id_area_kerja' => $areaPenerima->id_area_kerja],
                ['jumlah' => DB::raw('jumlah + ' . $jumlahSetuju)]
            );

            // Catat transaksi transfer antar unit
            $keterangan = "Transfer antar unit (Request #{$antarRequest->id}) dari area {$areaPengirim->nama_area} ke area {$areaPenerima->nama_area}";
            if ($catatan) $keterangan .= " - Catatan admin: {$catatan}";
            if ($jumlahSetuju != $antarRequest->jumlah) {
                $keterangan .= " (Jumlah direvisi dari {$antarRequest->jumlah} menjadi {$jumlahSetuju})";
            }

            Transaksi::create([
                'jenis'          => 'transfer',
                'id_barang'      => $antarRequest->id_barang,
                'jumlah'         => $jumlahSetuju,
                'id_area_asal'   => $areaPengirim->id_area_kerja,
                'id_area_tujuan' => $areaPenerima->id_area_kerja,
                'keterangan'     => $keterangan,
                'id_user'        => Auth::id(),
                'no_ref'         => 'AUR-' . $antarRequest->id,
            ]);

            // Update status request
            $updateData = [
                'status'      => AntarUnitRequest::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ];

            if ($jumlahSetuju != $antarRequest->jumlah) {
                $updateData['jumlah'] = $jumlahSetuju;
                $newKeterangan = $antarRequest->keterangan;
                $newKeterangan .= "\n[REVISI ADMIN] Jumlah disetujui: {$jumlahSetuju} (awal: {$antarRequest->jumlah}), area: {$areaPengirim->nama_area}";
                if ($catatan) $newKeterangan .= "\nCatatan: {$catatan}";
                $updateData['keterangan'] = $newKeterangan;
            } elseif ($catatan) {
                $updateData['keterangan'] = $antarRequest->keterangan . "\n[Catatan Admin] " . $catatan . " (area: {$areaPengirim->nama_area})";
            } else {
                $updateData['keterangan'] = ($antarRequest->keterangan ? $antarRequest->keterangan . "\n" : '') . "[INFO] Stok diambil dari area: {$areaPengirim->nama_area} dan ditambahkan ke area profil pemohon: {$areaPenerima->nama_area}";
            }

            $antarRequest->update($updateData);
            DB::commit();

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Permintaan antar unit disetujui.']);
            }
            return redirect()->route('stock-ctl.antar-unit.approval')->with('success', 'Permintaan antar unit disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return back()->withErrors('Gagal approve: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $access = session('stock_ctl_access');
        if (!$access['is_admin'] && !$access['is_super']) {
            abort(403);
        }

        $request->validate(['alasan' => 'required|string']);

        try {
            $antarRequest = AntarUnitRequest::findOrFail($id);

            // Jika bukan superadmin, pastikan admin unit tujuan yang reject
            if (!$access['is_super'] && $antarRequest->id_bisnis_unit_tujuan != $access['id_bisnis_unit']) {
                abort(403);
            }

            $antarRequest->update([
                'status'        => AntarUnitRequest::STATUS_REJECTED,
                'rejected_by'   => Auth::id(),
                'rejected_at'   => now(),
                'alasan_tolak'  => $request->alasan,
            ]);
            return redirect()->route('stock-ctl.antar-unit.approval')->with('success', 'Permintaan antar unit ditolak.');
        } catch (\Exception $e) {
            return back()->withErrors('Gagal menolak: ' . $e->getMessage());
        }
    }
}