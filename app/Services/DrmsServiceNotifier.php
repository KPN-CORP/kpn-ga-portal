<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Memasukkan email servis rutin / perbaikan ke tabel drms_service_email_outbox.
 * Pengiriman sebenarnya dilakukan cron_drms_service.php (terpisah dari cron_drms.php).
 *
 * Penerima per event:
 *  - submitted : Admin GA (tabel drms_admin_emails, sesuai BU kendaraan) + drms_service_notif_recipients(on_submitted)
 *  - approved / rejected / completed : pengaju + drms_service_notif_recipients(on_approved / on_rejected / on_completed)
 *
 * Kegagalan memasukkan ke outbox TIDAK pernah menggagalkan aksi utama (hanya dicatat di log).
 */
class DrmsServiceNotifier
{
    public const EVENTS = ['submitted', 'approved', 'rejected', 'completed'];

    private const TYPE_LABEL = ['service' => 'Servis Rutin', 'repair' => 'Perbaikan'];

    private const EVENT_LABEL = [
        'submitted' => 'Pengajuan Baru',
        'approved'  => 'Disetujui',
        'rejected'  => 'Ditolak',
        'completed' => 'Selesai',
    ];

    /**
     * @param string $type   'service' | 'repair'
     * @param object $item   ServiceSchedule | Repair (sudah menyimpan status terbaru)
     * @param string $event  submitted | approved | rejected | completed
     * @param User|null $actor user yang melakukan aksi (admin), kalau ada
     */
    public static function queue(string $type, $item, string $event, $actor = null): void
    {
        try {
            if (!in_array($event, self::EVENTS, true) || !isset(self::TYPE_LABEL[$type])) {
                return;
            }

            $item->loadMissing('vehicle');
            $vehicle = $item->vehicle;
            $buId = $vehicle->business_unit_id ?? null;

            $submitterId = $type === 'repair' ? $item->reported_by : $item->created_by;
            $submitter = $submitterId ? User::find($submitterId) : null;

            // email (lowercase) => nama
            $recipients = [];
            $add = function ($email, $name) use (&$recipients) {
                $email = strtolower(trim((string) $email));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($recipients[$email])) {
                    $recipients[$email] = $name;
                }
            };

            if ($event === 'submitted') {
                // Admin GA tetap dapat notif pengajuan (sumber sama dengan cron_drms.php)
                $admins = DB::table('drms_admin_emails')
                    ->where(function ($q) use ($buId) {
                        $q->whereNull('business_unit_id');
                        if ($buId) {
                            $q->orWhere('business_unit_id', $buId);
                        }
                    })
                    ->get(['email', 'name']);
                foreach ($admins as $a) {
                    $add($a->email, $a->name);
                }
            } elseif ($submitter) {
                $add($submitter->email, $submitter->name);
            }

            // Penerima tambahan yang diatur di tabel pengaturan
            $extra = DB::table('drms_service_notif_recipients')
                ->where('is_active', 1)
                ->where('on_' . $event, 1)            // $event sudah divalidasi whitelist di atas
                ->whereIn('item_type', ['all', $type])
                ->where(function ($q) use ($buId) {
                    $q->whereNull('business_unit_id');
                    if ($buId) {
                        $q->orWhere('business_unit_id', $buId);
                    }
                })
                ->get(['email', 'name']);
            foreach ($extra as $r) {
                $add($r->email, $r->name);
            }

            if (empty($recipients)) {
                Log::info("DrmsServiceNotifier: tidak ada penerima untuk {$type} #{$item->id} ({$event})");
                return;
            }

            $item->refresh();
            $ref = ($type === 'service' ? 'SRV' : 'RPR') . str_pad((string) $item->id, 5, '0', STR_PAD_LEFT);
            $plate = $vehicle->plate_number ?? '-';

            $base = [
                'item_type' => $type,
                'item_id'   => $item->id,
                'event'     => $event,
                'ref'       => $ref,
                'vehicle'   => trim(($vehicle->type ?? '') . ' - ' . $plate, ' -'),
                'submitter' => $submitter->name ?? '-',
                'actor'     => $actor->name ?? null,
                'notes'     => $item->notes,
                'reason'    => $event === 'rejected' ? $item->rejection_reason : null,
            ];

            if ($type === 'service') {
                $base['date'] = optional($item->service_date)->format('d-m-Y');
                $base['summary'] = collect($item->service_type ?? [])
                    ->map(fn ($t) => ucfirst(str_replace('_', ' ', $t)))
                    ->implode(', ');
                $base['workshop'] = $item->workshop_name;
                $base['cost'] = $event === 'completed' ? (float) $item->cost : null;
                $adminLink = route('drms.service-schedules.show', $item->id);
            } else {
                $base['date'] = optional($item->report_date)->format('d-m-Y');
                $base['summary'] = $item->complaint;
                $base['workshop'] = null;
                $base['cost'] = $event === 'completed' ? (float) ($item->labor_cost + $item->parts_cost) : null;
                $adminLink = route('drms.repairs.show', $item->id);
            }
            $driverLink = route('drms.driver.service.show', [$type, $item->id]);

            $subject = '[' . self::TYPE_LABEL[$type] . '] ' . self::EVENT_LABEL[$event] . " - {$ref} - {$plate}";
            $submitterEmail = $submitter ? strtolower(trim((string) $submitter->email)) : null;
            $now = now();

            foreach ($recipients as $email => $name) {
                $payload = $base + ['link' => ($email === $submitterEmail) ? $driverLink : $adminLink];

                DB::table('drms_service_email_outbox')->insertOrIgnore([
                    'item_type'  => $type,
                    'item_id'    => $item->id,
                    'event'      => $event,
                    'to_email'   => $email,
                    'to_name'    => $name,
                    'subject'    => mb_substr($subject, 0, 255),
                    'payload'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'status'     => 'pending',
                    'attempts'   => 0,
                    'created_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('DrmsServiceNotifier gagal: ' . $e->getMessage(), [
                'type'  => $type,
                'id'    => $item->id ?? null,
                'event' => $event,
            ]);
        }
    }
}
