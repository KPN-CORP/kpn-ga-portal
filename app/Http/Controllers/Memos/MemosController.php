<?php

namespace App\Http\Controllers\Memos;

use App\Http\Controllers\Controller;
use App\Models\Memos\Memos;
use App\Models\Memos\MemosItems;
use App\Models\Memos\MemosAttachments;
use App\Models\Memos\MemoNumberSetting;
use App\Models\ApiEmpHcis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class MemosController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Memos::class, 'memo');
    }

    public function index()
    {
        $memos = Memos::viewable(auth()->user())->with(['creator', 'attachments'])->latest()->paginate(15);
        return view('Memos.Memos.index', compact('memos'));
    }

    public function create()
    {
        $employees = ApiEmpHcis::limit(100)->get(['employee_id', 'fullname', 'group_company']);
        $signer = MemoNumberSetting::resolveSigner(auth()->user());
        return view('Memos.Memos.create', compact('employees', 'signer'));
    }

    /**
     * Form edit, hanya untuk memo berstatus draft milik user yang bersangkutan
     * (otorisasi 'update' sudah dicek lewat authorizeResource di constructor).
     */
    public function edit(Memos $memo)
    {
        if ($memo->status !== 'draft') {
            return redirect()->route('memos.show', $memo)->with('error', 'Hanya draft yang bisa diedit');
        }

        $memo->load('items', 'attachments');
        $employees = ApiEmpHcis::limit(100)->get(['employee_id', 'fullname', 'group_company']);
        $signer = MemoNumberSetting::resolveSigner(auth()->user());
        return view('Memos.Memos.create', compact('memo', 'employees', 'signer'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'perihal' => 'required|string',
            'kepada'  => 'required|string',
            'dari'    => 'required|string',
            'items'   => 'required',
            'status'  => 'in:draft,submitted'
        ]);

        $items = $this->decodeItems($request);
        if ($items === null) {
            return response()->json(['success' => false, 'message' => 'Items tidak valid'], 422);
        }
        $itemsError = $this->validateItems($items);
        if ($itemsError) {
            return response()->json(['success' => false, 'message' => $itemsError], 422);
        }

        $dynamicColumns = $this->decodeDynamicColumns($request);

        DB::beginTransaction();
        try {
            $total = collect($items)->sum('tagihan');
            $businessUnit = auth()->user()->getBusinessUnitAttribute();

            // Penandatangan & Jabatan diambil otomatis dari data admin tim, bukan input manual.
            $signer = MemoNumberSetting::resolveSigner(auth()->user());

            $memo = Memos::create([
                'perihal'       => $request->perihal,
                'kepada'        => $request->kepada,
                'dari'          => $request->dari,
                'instruksi'     => $request->instruksi,
                'bank'          => $request->bank,
                'atas_nama'     => $request->atas_nama,
                'no_rek'        => $request->no_rek,
                'penandatangan' => $signer['penandatangan'],
                'jabatan'       => $signer['jabatan'],
                'total_amount'  => $total,
                'status'        => $request->status,
                'business_unit' => $businessUnit,
                'dynamic_columns_definition' => $dynamicColumns,
                'created_by'    => auth()->id(),
                'expires_at'    => $request->status === 'draft' ? now()->addHours(24) : null
            ]);

            $this->syncItems($memo, $items);
            $this->storeAttachments($request, $memo);

            DB::commit();
            return response()->json(['success' => true, 'memo_id' => $memo->id, 'message' => 'Memo tersimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update draft yang sudah ada. Kalau statusnya diubah jadi 'submitted' di sini,
     * nomor memo otomatis digenerate (lihat event 'updating' di model Memos).
     */
    public function update(Request $request, Memos $memo)
    {
        if ($memo->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Hanya draft yang bisa diedit'], 422);
        }

        $request->validate([
            'perihal' => 'required|string',
            'kepada'  => 'required|string',
            'dari'    => 'required|string',
            'items'   => 'required',
            'status'  => 'in:draft,submitted'
        ]);

        $items = $this->decodeItems($request);
        if ($items === null) {
            return response()->json(['success' => false, 'message' => 'Items tidak valid'], 422);
        }
        $itemsError = $this->validateItems($items);
        if ($itemsError) {
            return response()->json(['success' => false, 'message' => $itemsError], 422);
        }

        $dynamicColumns = $this->decodeDynamicColumns($request);

        DB::beginTransaction();
        try {
            $total = collect($items)->sum('tagihan');
            $signer = MemoNumberSetting::resolveSigner(auth()->user());

            $memo->update([
                'perihal'       => $request->perihal,
                'kepada'        => $request->kepada,
                'dari'          => $request->dari,
                'instruksi'     => $request->instruksi,
                'bank'          => $request->bank,
                'atas_nama'     => $request->atas_nama,
                'no_rek'        => $request->no_rek,
                'penandatangan' => $signer['penandatangan'],
                'jabatan'       => $signer['jabatan'],
                'total_amount'  => $total,
                'status'        => $request->status,
                'dynamic_columns_definition' => $dynamicColumns,
                'expires_at'    => $request->status === 'draft' ? now()->addHours(24) : null
            ]);

            $memo->items()->delete();
            $this->syncItems($memo, $items);
            $this->storeAttachments($request, $memo);

            DB::commit();
            return response()->json(['success' => true, 'memo_id' => $memo->id, 'message' => 'Memo diperbarui']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Memos $memo)
    {
        $memo->load('items', 'attachments', 'creator');
        return view('Memos.Memos.show', compact('memo'));
    }

    public function downloadPdf(Memos $memo)
    {
        $memo->load('items');

        $pdf = Pdf::loadView('Memos.Memos.pdf', compact('memo'))->setPaper('a4');

        $filename = 'Memo-' . ($memo->memo_number ?? 'draft') . '.pdf';

        // Tidak pakai $pdf->download() karena Laravel/Symfony menolak "/" di nama file.
        // Response dibuat manual supaya "/" tetap bisa dipakai apa adanya.
        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function updateChecklist(Request $request, MemosAttachments $attachment)
    {
        $this->authorize('update', $attachment->memo);
        $attachment->update(['is_checked' => $request->has('is_checked')]);
        return back()->with('success', 'Checklist diperbarui');
    }

    public function deleteAttachment(MemosAttachments $attachment)
    {
        $this->authorize('update', $attachment->memo);
        if ($attachment->memo->status !== 'draft') {
            return back()->with('error', 'Lampiran hanya bisa dihapus selagi masih draft');
        }
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
        return back()->with('success', 'Lampiran dihapus');
    }

    public function destroy(Memos $memo)
    {
        if ($memo->status === 'draft') {
            foreach ($memo->attachments as $att) {
                Storage::disk('public')->delete($att->file_path);
            }
            $memo->delete();
            return redirect()->route('memos.index')->with('success', 'Draft dihapus');
        }
        return back()->with('error', 'Hanya draft yang bisa dihapus');
    }

    /**
     * API endpoint untuk mendapatkan terbilang dari jumlah angka
     */
    public function terbilang($amount)
    {
        return response()->json([
            'terbilang' => terbilang($amount)
        ]);
    }

    // ========== HELPERS ==========

    private function decodeItems(Request $request): ?array
    {
        $items = $request->items;
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        if (!is_array($items) || count($items) === 0) {
            return null;
        }
        return $items;
    }

    private function decodeDynamicColumns(Request $request): array
    {
        $dynamicColumns = $request->dynamicColumns;
        if (is_string($dynamicColumns)) {
            $dynamicColumns = json_decode($dynamicColumns, true);
        }
        return is_array($dynamicColumns) ? $dynamicColumns : [];
    }

    private function validateItems(array $items): ?string
    {
        foreach ($items as $index => $item) {
            if (empty($item['keterangan']) || !isset($item['tagihan']) || !is_numeric($item['tagihan'])) {
                return "Item ke-" . ($index + 1) . " tidak lengkap";
            }
        }
        return null;
    }

    private function syncItems(Memos $memo, array $items): void
    {
        foreach ($items as $index => $item) {
            MemosItems::create([
                'memo_id'         => $memo->id,
                'nama'            => $item['keterangan'],
                'dynamic_columns' => $item['dynamic_columns'] ?? [],
                'tagihan'         => $item['tagihan'],
                'sort_order'      => $index
            ]);
        }
    }

    private function storeAttachments(Request $request, Memos $memo): void
    {
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('memos/' . $memo->id, 'public');
                MemosAttachments::create([
                    'memo_id'       => $memo->id,
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getMimeType(),
                    'is_checked'    => false
                ]);
            }
        }
    }
}