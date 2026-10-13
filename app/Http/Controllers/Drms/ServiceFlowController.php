<?php

namespace App\Http\Controllers\Drms;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Drms\Repair;
use App\Models\Drms\ServiceSchedule;
use App\Services\DrmsServiceNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Alur admin untuk pengajuan Servis Rutin & Perbaikan:
 *   open --(approve)--> progress --(complete)--> done
 *   open --(reject)---> rejected
 *
 * Admin hanya bisa memproses kendaraan di Business Unit-nya sendiri
 * (superadmin bisa semua BU).
 */
class ServiceFlowController extends Controller
{
    private const MODELS = [
        'service' => ServiceSchedule::class,
        'repair'  => Repair::class,
    ];

    /** @return array{0:string,1:\Illuminate\Database\Eloquent\Model} */
    private function authorizeItem(string $type, int $id): array
    {
        $modelClass = self::MODELS[$type] ?? abort(404);
        $item = $modelClass::with('vehicle')->findOrFail($id);

        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $buId = optional($user->drmsProfile)->business_unit_id ?? abort(403);
            if (optional($item->vehicle)->business_unit_id != $buId) {
                abort(403, 'Kendaraan ini bukan milik Business Unit Anda.');
            }
        }

        return [$modelClass, $item];
    }

    /**
     * Ubah status secara atomik (row dikunci). Return false kalau status awal
     * sudah tidak sesuai (mis. admin lain sudah memproses duluan).
     */
    private function transition(string $modelClass, int $id, string $from, \Closure $apply): bool
    {
        return DB::transaction(function () use ($modelClass, $id, $from, $apply) {
            $row = $modelClass::whereKey($id)->lockForUpdate()->first();
            if (!$row || $row->status !== $from) {
                return false;
            }
            $apply($row);
            return true;
        });
    }

    public function approve(string $type, int $id)
    {
        [$modelClass, $item] = $this->authorizeItem($type, $id);

        $ok = $this->transition($modelClass, $item->id, 'open', function ($row) {
            $row->update([
                'status'      => 'progress',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        });

        if (!$ok) {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        DrmsServiceNotifier::queue($type, $modelClass::with('vehicle')->find($item->id), 'approved', Auth::user());

        return back()->with('success', 'Pengajuan disetujui. Status sekarang: Dalam Proses.');
    }

    public function reject(Request $request, string $type, int $id)
    {
        [$modelClass, $item] = $this->authorizeItem($type, $id);

        $data = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $ok = $this->transition($modelClass, $item->id, 'open', function ($row) use ($data) {
            $row->update([
                'status'           => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
                'approved_by'      => Auth::id(),   // dipakai sebagai "diproses oleh"
                'approved_at'      => now(),
            ]);
        });

        if (!$ok) {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        DrmsServiceNotifier::queue($type, $modelClass::with('vehicle')->find($item->id), 'rejected', Auth::user());

        return back()->with('success', 'Pengajuan ditolak.');
    }

    public function complete(Request $request, string $type, int $id)
    {
        [$modelClass, $item] = $this->authorizeItem($type, $id);

        if ($type === 'service') {
            $data = $request->validate([
                'cost'                  => 'required|numeric|min:0',
                'workshop_name'         => 'nullable|string|max:255',
                'odometer_at_service'   => 'nullable|integer|min:0',
                'next_service_odometer' => 'nullable|integer|min:0',
                'next_service_date'     => 'nullable|date',
                'completion_notes'      => 'nullable|string|max:2000',
                'invoice_file'          => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);
            $optionalKeys = ['workshop_name', 'odometer_at_service', 'next_service_odometer', 'next_service_date'];
            $invoiceDir = 'service_invoices';
        } else {
            $data = $request->validate([
                'diagnosis'        => 'nullable|string',
                'parts_replaced'   => 'nullable|string',
                'labor_cost'       => 'nullable|numeric|min:0',
                'parts_cost'       => 'nullable|numeric|min:0',
                'completion_notes' => 'nullable|string|max:2000',
                'invoice_file'     => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);
            $optionalKeys = ['diagnosis', 'parts_replaced', 'labor_cost', 'parts_cost'];
            $invoiceDir = 'repair_invoices';
        }

        $invoice = null;
        $oldInvoice = null;
        try {
            $ok = $this->transition($modelClass, $item->id, 'progress', function ($row) use ($request, $data, $type, $optionalKeys, $invoiceDir, &$invoice, &$oldInvoice) {
                $update = [
                    'status'       => 'done',
                    'completed_at' => now(),
                    'completed_by' => Auth::id(),
                ];

                if ($type === 'service') {
                    $update['cost'] = $data['cost'];
                }

                // Isian opsional hanya menimpa data pengaju kalau admin benar-benar mengisinya
                foreach ($optionalKeys as $k) {
                    if ($request->filled($k)) {
                        $update[$k] = $data[$k];
                    }
                }

                if (!empty($data['completion_notes'])) {
                    $update['notes'] = trim(($row->notes ? $row->notes . "\n" : '') . '[Admin] ' . $data['completion_notes']);
                }

                if ($request->hasFile('invoice_file')) {
                    $invoice = ImageHelper::compressOrStoreFile($request->file('invoice_file'), $invoiceDir);
                    $oldInvoice = $row->invoice_file;   // dihapus SETELAH transaksi berhasil
                    $update['invoice_file'] = $invoice;
                }

                $row->update($update);
            });
        } catch (\Throwable $e) {
            if ($invoice) {
                ImageHelper::deleteImage($invoice);
            }
            throw $e;
        }

        if (!$ok) {
            return back()->with('error', 'Pengajuan ini belum berstatus Dalam Proses atau sudah diselesaikan.');
        }

        if ($oldInvoice) {
            ImageHelper::deleteImage($oldInvoice);
        }

        DrmsServiceNotifier::queue($type, $modelClass::with('vehicle')->find($item->id), 'completed', Auth::user());

        return back()->with('success', 'Pekerjaan ditandai selesai.');
    }
}
