<?php

namespace App\Http\Controllers\Memos;

use App\Http\Controllers\Controller;
use App\Models\Memos\Memos;
use App\Models\Memos\MemosItems;
use App\Models\Memos\MemosAttachments;
use App\Models\ApiEmpHcis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MemosController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Memos::class, 'memo');
    }

    public function index()
    {
        $memos = Memos::viewable(auth()->user())->with('creator')->latest()->paginate(15);
        return view('Memos.index', compact('memos'));
    }

    public function create()
    {
        $employees = ApiEmpHcis::limit(100)->get(['employee_id', 'fullname', 'group_company']);
        return view('Memos.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'perihal'       => 'required|string',
            'kepada'        => 'required|string',
            'dari'          => 'required|string',
            'penandatangan' => 'required|string',
            'jabatan'       => 'required|string',
            'items'         => 'required',
            'status'        => 'in:draft,submitted'
        ]);

        // Decode items jika dalam bentuk JSON string
        $items = $request->items;
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        if (!is_array($items) || count($items) === 0) {
            return response()->json(['success' => false, 'message' => 'Items tidak valid'], 422);
        }

        // Validasi setiap item
        foreach ($items as $index => $item) {
            if (empty($item['nama']) || !isset($item['tagihan']) || !is_numeric($item['tagihan'])) {
                return response()->json(['success' => false, 'message' => "Item ke-" . ($index+1) . " tidak lengkap"], 422);
            }
        }

        // Decode dynamicColumns (judul kolom dinamis)
        $dynamicColumns = $request->dynamicColumns;
        if (is_string($dynamicColumns)) {
            $dynamicColumns = json_decode($dynamicColumns, true);
        }
        if (!is_array($dynamicColumns)) {
            $dynamicColumns = [];
        }

        DB::beginTransaction();
        try {
            $total = collect($items)->sum('tagihan');
            $businessUnit = auth()->user()->getBusinessUnitAttribute();

            $memo = Memos::create([
                'perihal'       => $request->perihal,
                'kepada'        => $request->kepada,
                'dari'          => $request->dari,
                'instruksi'     => $request->instruksi,
                'bank'          => $request->bank,
                'atas_nama'     => $request->atas_nama,
                'no_rek'        => $request->no_rek,
                'penandatangan' => $request->penandatangan,
                'jabatan'       => $request->jabatan,
                'total_amount'  => $total,
                'status'        => $request->status,
                'business_unit' => $businessUnit,
                'dynamic_columns_definition' => $dynamicColumns,
                'created_by'    => auth()->id(),
                'expires_at'    => $request->status === 'draft' ? now()->addHours(24) : null
            ]);

            foreach ($items as $index => $item) {
                MemosItems::create([
                    'memo_id'         => $memo->id,
                    'nama'            => $item['nama'],
                    'pt_unit'         => $item['pt_unit'] ?? null,
                    'dynamic_columns' => $item['dynamic_columns'] ?? [],
                    'tagihan'         => $item['tagihan'],
                    'sort_order'      => $index
                ]);
            }

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

            DB::commit();
            return response()->json(['success' => true, 'memo_id' => $memo->id, 'message' => 'Memo tersimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Memos $memo)
    {
        $memo->load('items', 'attachments', 'creator');
        return view('Memos.show', compact('memo'));
    }

    public function updateChecklist(Request $request, MemosAttachments $attachment)
    {
        $this->authorize('update', $attachment->memo);
        $attachment->update(['is_checked' => $request->has('is_checked')]);
        return back()->with('success', 'Checklist diperbarui');
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
}