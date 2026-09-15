<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\VehicleDocument;
use App\Models\Drms\Vehicle;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VehicleDocumentController extends Controller
{
    private function getBusinessUnitId()
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) return null;
        return $user->drmsProfile->business_unit_id ?? abort(403);
    }

    public function index(Request $request)
    {
        $buId = $this->getBusinessUnitId();
        $query = VehicleDocument::with('vehicle');

        if ($buId) {
            $query->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
        }

        // Filter pencarian
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->whereHas('vehicle', function ($q) use ($search) {
                $q->where('plate_number', 'LIKE', $search)
                  ->orWhere('type', 'LIKE', $search);
            });
        }

        // Filter kendaraan
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        // Filter status kadaluarsa
        if ($request->filled('expiry_status')) {
            $today = now()->format('Y-m-d');
            $h30 = now()->addDays(30)->format('Y-m-d');
            
            if ($request->expiry_status == 'expired') {
                $query->where(function ($q) use ($today) {
                    $q->where('stnk_expiry', '<', $today)
                      ->orWhere('tax_yearly_expiry', '<', $today)
                      ->orWhere('tax_5year_expiry', '<', $today)
                      ->orWhere('insurance_expiry', '<', $today);
                });
            } elseif ($request->expiry_status == 'h30') {
                $query->where(function ($q) use ($today, $h30) {
                    $q->whereBetween('stnk_expiry', [$today, $h30])
                      ->orWhereBetween('tax_yearly_expiry', [$today, $h30])
                      ->orWhereBetween('tax_5year_expiry', [$today, $h30])
                      ->orWhereBetween('insurance_expiry', [$today, $h30]);
                });
            }
        }

        $documents = $query->latest()->get();

        // Data untuk dropdown filter
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();

        return view('drms.vehicle_documents.index', compact('documents', 'vehicles'));
    }

    public function create()
    {
        $buId = $this->getBusinessUnitId();
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))
            ->whereDoesntHave('document')
            ->get();
        return view('drms.vehicle_documents.create', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:drms_vehicles,id|unique:drms_vehicle_documents,vehicle_id',
            'stnk_expiry' => 'nullable|date',
            'tax_yearly_expiry' => 'nullable|date',
            'tax_5year_expiry' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'stnk_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'tax_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'insurance_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string',
        ]);

        foreach (['stnk_file', 'tax_file', 'insurance_file'] as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = ImageHelper::compressAndStore($request->file($field), 'vehicle_docs', 80);
            }
        }
        VehicleDocument::create($validated);
        return redirect()->route('drms.vehicle-documents.index')
            ->with('success', 'Dokumen berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $document = VehicleDocument::findOrFail($id);
        $buId = $this->getBusinessUnitId();
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();
        return view('drms.vehicle_documents.edit', compact('document', 'vehicles'));
    }

    public function update(Request $request, $id)
    {
        $document = VehicleDocument::findOrFail($id);
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:drms_vehicles,id|unique:drms_vehicle_documents,vehicle_id,'.$id,
            'stnk_expiry' => 'nullable|date',
            'tax_yearly_expiry' => 'nullable|date',
            'tax_5year_expiry' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'stnk_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'tax_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'insurance_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string',
        ]);

        foreach (['stnk_file', 'tax_file', 'insurance_file'] as $field) {
            if ($request->hasFile($field)) {
                if ($document->$field) ImageHelper::deleteImage($document->$field);
                $validated[$field] = ImageHelper::compressAndStore($request->file($field), 'vehicle_docs', 80);
            }
        }
        $document->update($validated);
        return redirect()->route('drms.vehicle-documents.index')
            ->with('success', 'Dokumen diperbarui.');
    }

    public function show($id)
    {
        $document = VehicleDocument::with('vehicle')->findOrFail($id);
        return view('drms.vehicle_documents.show', compact('document'));
    }
    public function destroy($id)
    {
        $doc = VehicleDocument::findOrFail($id);
        foreach (['stnk_file', 'tax_file', 'insurance_file'] as $field) {
            if ($doc->$field) ImageHelper::deleteImage($doc->$field);
        }
        $doc->delete();
        return redirect()->route('drms.vehicle-documents.index')
            ->with('success', 'Dokumen dihapus.');
    }
}