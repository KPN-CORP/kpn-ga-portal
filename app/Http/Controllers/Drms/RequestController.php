<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\RequestAttachment;
use App\Models\Drms\RequestLog;
use App\Models\User;
use App\Notifications\NewRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DriverRequest::with(['requester', 'approverL1', 'admin', 'driver', 'vehicle', 'voucher', 'vouchers.inputBusinessUnit', 'attachments'])
            ->where('requester_id', auth()->id());

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('request_no', 'LIKE', $search)
                  ->orWhere('pickup_location', 'LIKE', $search)
                  ->orWhere('destination', 'LIKE', $search)
                  ->orWhere('purpose', 'LIKE', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('dari')) {
            $query->whereDate('usage_date', '>=', $request->dari);
        }

        if ($request->filled('sampai')) {
            $query->whereDate('usage_date', '<=', $request->sampai);
        }

        $requests = $query->latest()->paginate(10)->appends($request->query());

        return view('drms.requests.index', compact('requests'));
    }

    public function create()
    {
        return view('drms.requests.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'trip_type'             => 'required|in:one_way,round_trip',
            'distance_type'         => 'required|in:jarak_dekat,jarak_jauh',
            'usage_date'            => 'required|date|after_or_equal:today',
            'start_hour'            => 'required|integer|between:0,23',
            'start_minute'          => 'required|integer|between:0,59',
            'end_hour'              => 'required|integer|between:0,23',
            'end_minute'            => 'required|integer|between:0,59',
            'pickup_location'       => 'required|string|max:255',
            'destination'           => 'required|string|max:255',
            'purpose'               => 'required|string',
            'pickup_maps_link'      => 'required|string|max:500',
            'destination_maps_link' => 'required|string|max:500',
        ];

        // Lampiran opsional: PDF / gambar, maks 3 file @ 5 MB
        $rules['attachments']   = 'nullable|array|max:3';
        $rules['attachments.*'] = 'file|mimes:jpg,jpeg,png,pdf|max:5120';

        if ($request->trip_type === 'round_trip') {
            $rules['return_date'] = 'required|date|after_or_equal:usage_date';
        }

        $request->merge([
            'start_hour'   => trim($request->input('start_hour', '')),
            'start_minute' => trim($request->input('start_minute', '')),
            'end_hour'     => trim($request->input('end_hour', '')),
            'end_minute'   => trim($request->input('end_minute', '')),
        ]);

        $messages = [
            'pickup_maps_link.required'      => 'Link Google Maps untuk lokasi penjemputan wajib diisi.',
            'destination_maps_link.required' => 'Link Google Maps untuk tujuan wajib diisi.',
            'attachments.max'                => 'Lampiran maksimal 3 file.',
            'attachments.*.mimes'            => 'Lampiran harus berupa PDF, JPG, atau PNG.',
            'attachments.*.max'              => 'Ukuran tiap lampiran maksimal 5 MB.',
        ];

        $data = $request->validate($rules, $messages);

        // Lampiran bukan kolom drms_requests: dipisah supaya tidak ikut DriverRequest::create()
        $files = $request->file('attachments', []);
        unset($data['attachments']);

        $start_time = sprintf('%02d:%02d', (int)$data['start_hour'], (int)$data['start_minute']);
        $end_time   = sprintf('%02d:%02d', (int)$data['end_hour'], (int)$data['end_minute']);

        if ($end_time <= $start_time) {
            return back()->withErrors(['end_time' => 'Jam selesai harus setelah jam berangkat.'])->withInput();
        }

        $data['start_time'] = $start_time;
        $data['end_time']   = $end_time;
        unset($data['start_hour'], $data['start_minute'], $data['end_hour'], $data['end_minute']);

        if ($data['trip_type'] === 'round_trip' && !empty($data['return_date'])) {
            $data['return_time'] = $data['end_time'];
        }

        $data['request_no']   = 'DRQ' . date('Ymd') . rand(100, 999);
        $data['requester_id'] = Auth::id();

        // Ambil profil DRMS user yang sedang login
        $profile = Auth::user()->drmsProfile;
        if (!$profile) {
            return back()->withErrors(['error' => 'Profil DRMS Anda tidak ditemukan. Hubungi administrator.'])->withInput();
        }

        // 🔥 PERBAIKAN: Wajib memiliki atasan (L1)
        if (empty($profile->approver_user_id)) {
            return back()->withErrors(['error' => 'Anda belum memiliki atasan (L1) yang ditetapkan. Silakan hubungi administrator DRMS.'])->withInput();
        }

        // Set approver_l1_id dari profil (otomatis terisi)
        $data['approver_l1_id'] = $profile->approver_user_id;

        $storedPaths = [];
        DB::beginTransaction();
        try {
            $driverRequest = DriverRequest::create($data);

            // Simpan lampiran (jika ada). Info file dibaca SEBELUM disimpan helper.
            foreach ($files as $file) {
                $meta = [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getMimeType(),
                    'file_size'     => $file->getSize(),
                ];
                $path = ImageHelper::compressOrStoreFile($file, 'request_attachments');
                $storedPaths[] = $path;

                RequestAttachment::create($meta + [
                    'request_id'  => $driverRequest->id,
                    'file_path'   => $path,
                    'uploaded_by' => Auth::id(),
                ]);
            }

            // Kirim notifikasi ke atasan (L1)
            $atasan = User::find($data['approver_l1_id']);
            if ($atasan) {
                $atasan->notify(new NewRequestNotification($driverRequest));
            }

            DB::commit();
            return redirect()->route('drms.requests.index')
                ->with('success', 'Permintaan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($storedPaths as $p) {
                ImageHelper::deleteImage($p);   // bersihkan file yatim kalau transaksi gagal
            }
            \Log::error('Gagal menyimpan DRMS request: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'data'    => $data,
            ]);
            return back()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(DriverRequest $driverRequest)
    {
        if ($driverRequest->requester_id !== Auth::id()) {
            abort(403);
        }
        $driverRequest->load('attachments');
        return view('drms.requests.show', compact('driverRequest'));
    }

    /**
     * Pemohon membatalkan pengajuannya sendiri.
     * Hanya boleh selama status pending_l1 / approved_l1 (belum diproses admin GA).
     * Row dikunci supaya tidak bentrok kalau admin menyetujui di detik yang sama.
     */
    public function cancel(Request $request, DriverRequest $driverRequest)
    {
        if ($driverRequest->requester_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        $cancelled = DB::transaction(function () use ($driverRequest, $data) {
            $req = DriverRequest::whereKey($driverRequest->id)
                ->where('requester_id', Auth::id())
                ->lockForUpdate()
                ->first();

            if (!$req || !$req->canBeCancelledByRequester()) {
                return false;
            }

            $from = $req->status;
            $req->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
            ]);

            RequestLog::create([
                'request_id'  => $req->id,
                'action'      => 'cancelled',
                'from_status' => $from,
                'to_status'   => 'cancelled',
                'note'        => $data['cancel_reason'] ?? null,
                'user_id'     => Auth::id(),
            ]);

            return true;
        });

        if (!$cancelled) {
            return redirect()->route('drms.requests.index')
                ->with('error', 'Permintaan ini sudah diproses admin GA atau sudah dibatalkan, sehingga tidak bisa dibatalkan lagi. Hubungi admin GA bila perlu.');
        }

        return redirect()->route('drms.requests.index')
            ->with('success', 'Permintaan berhasil dibatalkan.');
    }
}