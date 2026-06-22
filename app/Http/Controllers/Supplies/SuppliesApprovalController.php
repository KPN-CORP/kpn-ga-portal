<?php
namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplies\SuppliesPermintaan;
use App\Models\Supplies\SuppliesStok;
use App\Models\Supplies\SuppliesTransaksi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SuppliesApprovalController extends Controller
{
    public function __construct() { $this->middleware('supplies.access:admin'); }

    public function index()
    {
        $permintaan = SuppliesPermintaan::with('barang', 'bisnisUnit', 'pemohon')->where('status', 'pending')->orderBy('created_at')->get();
        $pendingCount = $permintaan->count();
        return view('supplies.approval.index', compact('permintaan', 'pendingCount'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'jumlah_setuju' => 'required|numeric|min:0.01',
            'catatan' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $permintaan = SuppliesPermintaan::with(['barang', 'pemohon'])->findOrFail($id);
            
            if ($permintaan->status != 'pending') {
                throw new \Exception('Permintaan sudah diproses.');
            }
            
            $jumlahSetuju = $request->jumlah_setuju;
            
            $stok = SuppliesStok::where('id_barang', $permintaan->id_barang)
                ->where('id_bisnis_unit', $permintaan->id_bisnis_unit)
                ->first();
                
            if (!$stok || $stok->jumlah < $jumlahSetuju) {
                $tersedia = $stok ? $stok->jumlah : 0;
                throw new \Exception("Stok tidak mencukupi. Tersedia: {$tersedia} {$permintaan->barang->satuan}");
            }
            
            $stok->decrement('jumlah', $jumlahSetuju);
            
            // Hanya catatan admin yang disimpan (tanpa nama pemohon)
            $keterangan = $request->catatan ? trim($request->catatan) : null;
            
            SuppliesTransaksi::create([
                'jenis' => 'keluar',
                'id_barang' => $permintaan->id_barang,
                'jumlah' => $jumlahSetuju,
                'id_bisnis_unit' => $permintaan->id_bisnis_unit,
                'id_permintaan' => $permintaan->id,
                'no_ref' => 'REQ-' . $permintaan->id,
                'keterangan' => $keterangan,
                'id_user' => Auth::id(),
            ]);
            
            $permintaan->update([
                'status' => 'disetujui',
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ]);
            
            DB::commit();
            return redirect()->route('supplies.approval.index')
                ->with('success', 'Permintaan disetujui.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal approve: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['alasan' => 'required|string']);
        $permintaan = SuppliesPermintaan::findOrFail($id);
        $permintaan->update(['status' => 'ditolak', 'approved_by' => Auth::id(), 'approved_at' => now(), 'alasan_tolak' => $request->alasan]);
        return redirect()->route('supplies.approval.index')->with('success', 'Permintaan ditolak.');
    }
}