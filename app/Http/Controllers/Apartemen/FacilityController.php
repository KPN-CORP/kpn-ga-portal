<?php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use App\Models\Apartemen\Fasilitas;
use App\Models\Apartemen\FasilitasBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FacilityController extends Controller
{
    // List all active facilities
    public function index()
    {
        $facilities = Fasilitas::where('is_active', true)->get();
        return view('apartemen.user.facilities.index', compact('facilities'));
    }

    // Show booking form for a specific facility
    public function bookForm($id)
    {
        $facility = Fasilitas::findOrFail($id);
        return view('apartemen.user.facilities.book', compact('facility'));
    }

    // Store booking request
    public function store(Request $request, $id)
    {
        $facility = Fasilitas::findOrFail($id);

        $validated = $request->validate([
            'tanggal_booking' => 'required|date|after_or_equal:today',
            'jam_mulai'       => 'required',
            'jam_selesai'     => 'required|after:jam_mulai',
            'jumlah_orang'    => 'required|integer|min:1|max:' . $facility->kapasitas,
            'catatan'         => 'nullable|string|max:500',
        ]);

        // Check for overlapping bookings on same facility
        $overlap = FasilitasBooking::where('fasilitas_id', $facility->id)
            ->where('tanggal_booking', $validated['tanggal_booking'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('jam_mulai', [$validated['jam_mulai'], $validated['jam_selesai']])
                      ->orWhereBetween('jam_selesai', [$validated['jam_mulai'], $validated['jam_selesai']])
                      ->orWhere(function ($q) use ($validated) {
                          $q->where('jam_mulai', '<=', $validated['jam_mulai'])
                            ->where('jam_selesai', '>=', $validated['jam_selesai']);
                      });
            })
            ->whereIn('status', ['APPROVED', 'CHECKED_IN'])
            ->exists();

        if ($overlap) {
            return back()->with('error', 'Waktu yang dipilih sudah dibooking oleh orang lain.');
        }

        DB::beginTransaction();
        try {
            FasilitasBooking::create([
                'user_id'        => Auth::id(),
                'fasilitas_id'   => $facility->id,
                'tanggal_booking'=> $validated['tanggal_booking'],
                'jam_mulai'      => $validated['jam_mulai'],
                'jam_selesai'    => $validated['jam_selesai'],
                'jumlah_orang'   => $validated['jumlah_orang'],
                'catatan'        => $validated['catatan'],
                'status'         => 'PENDING',
            ]);
            DB::commit();
            return redirect()->route('apartemen.user.facilities.history')
                ->with('success', 'Permintaan booking fasilitas berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan booking: ' . $e->getMessage());
        }
    }

    // User's booking history
    public function history(Request $request)
    {
        $bookings = FasilitasBooking::with('fasilitas')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('apartemen.user.facilities.history', compact('bookings'));
    }

    // Cancel a pending booking (user action)
    public function cancel($id)
    {
        $booking = FasilitasBooking::where('user_id', Auth::id())
            ->where('status', 'PENDING')
            ->findOrFail($id);

        $booking->update(['status' => 'CANCELLED']);
        return back()->with('success', 'Booking dibatalkan.');
    }
}