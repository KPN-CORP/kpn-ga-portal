<?php

namespace App\Http\Controllers\Founddesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Founddesk\FounddeskItem;
use App\Models\Founddesk\FounddeskDisposition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class FounddeskDispositionController extends Controller
{
    /**
     * Display a listing of dispositions
     */
    public function index(Request $request)
    {
        $query = FounddeskDisposition::with(['item', 'creator']);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('disposition_no', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhereHas('item', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('item_code', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
        
        $dispositions = $query->orderBy('disposition_date', 'desc')->paginate(15);
        $statuses = ['pending', 'diserahkan', 'dibatalkan'];
        
        return view('founddesk.index', compact('dispositions', 'statuses'));
    }

    /**
     * Show form to create new disposition
     */
    public function create(Request $request)
    {
        $itemId = $request->get('item_id');
        $item = null;
        
        if ($itemId) {
            $item = FounddeskItem::findOrFail($itemId);
        }
        
        $items = FounddeskItem::where('status', 'tersedia')
                              ->where('current_stock', '>', 0)
                              ->orderBy('name')
                              ->get();
        
        $dispositionNo = FounddeskDisposition::generateDispositionNo();
        
        return view('founddesk.disposition.create', compact('items', 'dispositionNo', 'item'));
    }

    /**
     * Store new disposition
     */
    public function store(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:founddesk_items,id',
            'quantity' => 'required|integer|min:1',
            'disposition_date' => 'required|date',
            'recipient_name' => 'required|string|max:100',
            'recipient_id' => 'nullable|string|max:100',
            'recipient_contact' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'id_card_photo' => 'nullable|image|max:2048', // Foto KTP
            'handover_photo' => 'nullable|image|max:2048' // Foto penyerahan
        ]);

        $item = FounddeskItem::findOrFail($request->item_id);
        
        // Cek stok
        if ($item->current_stock < $request->quantity) {
            return back()->withErrors(['quantity' => 'Stok tidak mencukupi. Stok tersedia: ' . $item->current_stock]);
        }

        DB::beginTransaction();
        
        try {
            $data = $request->except(['id_card_photo', 'handover_photo']);
            $data['disposition_no'] = FounddeskDisposition::generateDispositionNo();
            $data['created_by'] = Auth::id();
            $data['status'] = 'diserahkan'; // Langsung diserahkan, tanpa approval
            
            // Upload foto KTP
            if ($request->hasFile('id_card_photo')) {
                $file = $request->file('id_card_photo');
                $fileName = time() . '_id_' . $file->getClientOriginalName();
                $path = $file->storeAs('founddesk/dispositions/idcard', $fileName, 'private');
                $data['id_card_photo'] = $path;
            }
            
            // Upload foto penyerahan
            if ($request->hasFile('handover_photo')) {
                $file = $request->file('handover_photo');
                $fileName = time() . '_handover_' . $file->getClientOriginalName();
                $path = $file->storeAs('founddesk/dispositions/handover', $fileName, 'private');
                $data['handover_photo'] = $path;
            }
            
            $disposition = FounddeskDisposition::create($data);
            
            // Kurangi stok
            $item->current_stock -= $request->quantity;
            
            // Update status jika stok habis
            if ($item->current_stock == 0) {
                $item->status = 'diserahkan';
            }
            
            $item->save();
            
            DB::commit();
            
            return redirect()->route('founddesk.index')
                             ->with('success', 'Barang berhasil diserahkan.');
                             
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Display disposition detail
     */
    public function show($id)
    {
        $disposition = FounddeskDisposition::with(['item', 'creator'])->findOrFail($id);
        
        return view('founddesk.disposition.show', compact('disposition'));
    }

    /**
     * Show photo (KTP or handover)
     */
    public function showPhoto($id, $type)
    {
        $disposition = FounddeskDisposition::findOrFail($id);
        
        $photoField = $type === 'idcard' ? 'id_card_photo' : 'handover_photo';
        
        if (!$disposition->$photoField || !Storage::disk('private')->exists($disposition->$photoField)) {
            abort(404);
        }
        
        return response()->file(Storage::disk('private')->path($disposition->$photoField));
    }

    /**
     * Cancel disposition (kembalikan stok)
     */
    public function cancel($id)
    {
        $disposition = FounddeskDisposition::findOrFail($id);
        
        DB::beginTransaction();
        
        try {
            // Kembalikan stok
            $item = $disposition->item;
            $item->current_stock += $disposition->quantity;
            
            if ($item->status == 'diserahkan' && $item->current_stock > 0) {
                $item->status = 'tersedia';
            }
            
            $item->save();
            
            $disposition->update([
                'status' => 'dibatalkan'
            ]);
            
            DB::commit();
            
            return redirect()->route('founddesk.index')
                             ->with('success', 'Penyerahan dibatalkan, stok dikembalikan.');
                             
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }
}