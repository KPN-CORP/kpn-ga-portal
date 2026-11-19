<?php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use App\Models\Apartemen\Fasilitas;
use App\Models\Apartemen\FasilitasBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FacilityAdminController extends Controller
{
    // Manage facilities (CRUD)
    public function index()
    {
        $facilities = Fasilitas::orderBy('nama_fasilitas')->paginate(10);
        return view('apartemen.admin.facilities.index', compact('facilities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_fasilitas' => 'required|string|max:100',
            'deskripsi'      => 'nullable|string',
            'kapasitas'      => 'required|integer|min:1',
            'jam_operasional'=> 'nullable|string|max:100',
            'is_active'      => 'boolean',
        ]);
        Fasilitas::create($validated);
        return back()->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $facility = Fasilitas::findOrFail($id);
        $validated = $request->validate([
            'nama_fasilitas' => 'required|string|max:100',
            'deskripsi'      => 'nullable|string',
            'kapasitas'      => 'required|integer|min:1',
            'jam_operasional'=> 'nullable|string|max:100',
            'is_active'      => 'boolean',
        ]);
        $facility->update($validated);
        return back()->with('success', 'Fasilitas diperbarui.');
    }

    public function destroy($id)
    {
        $facility = Fasilitas::findOrFail($id);
        // Prevent deletion if there are any bookings
        if ($facility->bookings()->count() > 0) {
            return back()->with('error', 'Fasilitas memiliki riwayat booking, tidak dapat dihapus.');
        }
        $facility->delete();
        return back()->with('success', 'Fasilitas dihapus.');
    }

    // Booking management
    public function bookings(Request $request)
    {
        $query = FasilitasBooking::with(['user', 'fasilitas'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('fasilitas_id')) {
            $query->where('fasilitas_id', $request->fasilitas_id);
        }

        $bookings = $query->paginate(15);
        $facilities = Fasilitas::all();

        return view('apartemen.admin.facilities.bookings', compact('bookings', 'facilities'));
    }

    public function approve($id, Request $request)
    {
        $booking = FasilitasBooking::findOrFail($id);
        $booking->update([
            'status'      => 'APPROVED',
            'approved_by' => Auth::user()->name,
            'approved_at' => now(),
        ]);
        return back()->with('success', 'Booking disetujui.');
    }

    public function reject($id, Request $request)
    {
        $request->validate(['reject_reason' => 'required|string|min:5']);
        $booking = FasilitasBooking::findOrFail($id);
        $booking->update([
            'status'        => 'REJECTED',
            'reject_reason' => $request->reject_reason,
            'approved_by'   => Auth::user()->name,
            'approved_at'   => now(),
        ]);
        return back()->with('success', 'Booking ditolak.');
    }

    // Check-in/out for facilities (similar to apartment check-in)
    public function checkin($id)
    {
        $booking = FasilitasBooking::where('status', 'APPROVED')
            ->findOrFail($id);
        $booking->update(['status' => 'CHECKED_IN']);
        return back()->with('success', 'Check‑in fasilitas berhasil.');
    }

    public function checkout($id)
    {
        $booking = FasilitasBooking::where('status', 'CHECKED_IN')
            ->findOrFail($id);
        $booking->update(['status' => 'CHECKED_OUT']);
        return back()->with('success', 'Check‑out fasilitas berhasil.');
    }
}