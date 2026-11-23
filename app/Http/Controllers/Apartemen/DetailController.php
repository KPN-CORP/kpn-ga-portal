<?php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use App\Models\Apartemen\Apartemen;
use App\Models\Apartemen\ApartemenUnit;
use App\Models\Apartemen\ApartemenUnitAset;
use Illuminate\Http\Request;

class DetailController extends Controller
{
    public function show($id)
    {
        $unit = ApartemenUnit::with(['apartemen', 'asets.aset', 'activeAssign.penghuni'])
            ->findOrFail($id);

        return view('apartemen.detail', compact('unit'));
    }

    public function history(Request $request)
    {
        $query = ApartemenUnit::with(['apartemen', 'assigns.penghuni'])
            ->whereHas('assigns');

        if ($request->filled('apartemen_id')) {
            $query->where('apartemen_id', $request->apartemen_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $units = $query->orderBy('nomor_unit')->paginate(20);
        $apartements = Apartemen::all();

        return view('apartemen.history', compact('units', 'apartements'));
    }
}