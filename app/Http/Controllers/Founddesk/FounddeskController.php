<?php

namespace App\Http\Controllers\Founddesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Founddesk\FounddeskItem;
use App\Models\Founddesk\FounddeskCategory;
use App\Models\Founddesk\FounddeskLocation;
use App\Models\Founddesk\FounddeskCondition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FounddeskController extends Controller
{
    /**
     * Display a listing of items
     */
    public function index(Request $request)
    {
        $query = FounddeskItem::with(['category', 'location', 'condition', 'dispositions']);
        
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('found_by', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->has('location_id') && $request->location_id != '') {
            $query->where('location_id', $request->location_id);
        }
        
        if ($request->has('condition_id') && $request->condition_id != '') {
            $query->where('condition_id', $request->condition_id);
        }
        
        if ($request->has('found_date_from') && !empty($request->found_date_from)) {
            $query->whereDate('found_date', '>=', $request->found_date_from);
        }
        
        if ($request->has('found_date_to') && !empty($request->found_date_to)) {
            $query->whereDate('found_date', '<=', $request->found_date_to);
        }
        
        if ($request->has('min_stock') && !empty($request->min_stock)) {
            $query->where('current_stock', '>=', $request->min_stock);
        }
        
        $items = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Data untuk filter
        $categories = FounddeskCategory::all();
        $locations = FounddeskLocation::all();
        $conditions = FounddeskCondition::all();
        $statuses = ['tersedia', 'diklaim', 'dikirim', 'diserahkan', 'kadaluarsa'];
        
        return view('founddesk.listuser', compact(
            'items', 
            'categories', 
            'locations', 
            'conditions', 
            'statuses'
        ));
    }

    /**
     * Show form to create new item
     */
    public function create()
    {
        $categories = FounddeskCategory::all();
        $locations = FounddeskLocation::all();
        $conditions = FounddeskCondition::all();
        $itemCode = FounddeskItem::generateItemCode();
        
        return view('founddesk.create', compact('categories', 'locations', 'conditions', 'itemCode'));
    }

    /**
     * Store new item
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'category_id' => 'nullable|exists:founddesk_categories,id',
            'location_id' => 'nullable|exists:founddesk_locations,id',
            'condition_id' => 'nullable|exists:founddesk_conditions,id',
            'found_date' => 'nullable|date',
            'found_by' => 'nullable|string|max:100',
            'found_location_detail' => 'nullable|string',
            'description' => 'nullable|string',
            'current_stock' => 'nullable|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'photo' => 'nullable|image|max:2048'
        ]);

        $data = $request->except('photo');
        $data['item_code'] = FounddeskItem::generateItemCode();
        $data['created_by'] = Auth::id();
        $data['status'] = 'tersedia';
        
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('founddesk/items', $fileName, 'private');
            $data['photo'] = $path;
        }

        FounddeskItem::create($data);

        return redirect()->route('founddesk.index')
                         ->with('success', 'Barang temuan berhasil ditambahkan.');
    }

    /**
     * Display item detail
     */
    public function show($id)
    {
        $item = FounddeskItem::with(['category', 'location', 'condition', 'creator', 'dispositions' => function($q) {
            $q->latest();
        }])->findOrFail($id);
        
        return view('founddesk.show', compact('item'));
    }

    /**
     * Delete item
     */
    public function destroy($id)
    {
        $item = FounddeskItem::findOrFail($id);
        
        if ($item->photo && Storage::disk('private')->exists($item->photo)) {
            Storage::disk('private')->delete($item->photo);
        }
        
        $item->delete();

        return redirect()->route('founddesk.index')
                         ->with('success', 'Barang temuan berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $query = FounddeskItem::with(['category', 'location', 'condition', 'dispositions']);
        
        // Terapkan filter yang sama seperti di index()
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('found_by', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->has('location_id') && $request->location_id != '') {
            $query->where('location_id', $request->location_id);
        }
        
        if ($request->has('condition_id') && $request->condition_id != '') {
            $query->where('condition_id', $request->condition_id);
        }
        
        if ($request->has('found_date_from') && !empty($request->found_date_from)) {
            $query->whereDate('found_date', '>=', $request->found_date_from);
        }
        
        if ($request->has('found_date_to') && !empty($request->found_date_to)) {
            $query->whereDate('found_date', '<=', $request->found_date_to);
        }
        
        $items = $query->orderBy('created_at', 'desc')->get();
        
        // Generate CSV
        $filename = 'lost-found-' . date('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($items) {
            $file = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($file, [
                'Kode Barang',
                'Nama Barang',
                'Kategori',
                'Lokasi',
                'Kondisi',
                'Ditemukan Oleh',
                'Tanggal Ditemukan',
                'Status',
                'Penerima Terakhir',
                'Tanggal Penyerahan'
            ]);
            
            // Data
            foreach ($items as $item) {
                $disposition = $item->dispositions->sortByDesc('created_at')->first();
                
                fputcsv($file, [
                    $item->item_code,
                    $item->name,
                    $item->category->name ?? '-',
                    $item->location->name ?? '-',
                    $item->condition->name ?? '-',
                    $item->found_by ?? '-',
                    $item->found_date ? date('d/m/Y', strtotime($item->found_date)) : '-',
                    ucfirst($item->status),
                    // $item->current_stock,
                    // $item->unit ?? 'pcs',
                    $disposition->recipient_name ?? '-',
                    $disposition->disposition_date ? date('d/m/Y', strtotime($disposition->disposition_date)) : '-'
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show item photo
     */
    public function showPhoto($id)
    {
        $item = FounddeskItem::findOrFail($id);
        
        if (!$item->photo || !Storage::disk('private')->exists($item->photo)) {
            abort(404);
        }
        
        return response()->file(Storage::disk('private')->path($item->photo));
    }
}