<?php

namespace App\Notifications;

use App\Models\Drms\DriverRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke admin saat slot yang dilepas dibatalkan padahal sudah ada request lain
 * yang ditugaskan di dalam slot itu. Request tersebut TIDAK dipindah otomatis;
 * admin yang memutuskan (ganti driver/mobil atau jadwal).
 */
class SlotReleaseCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected DriverRequest $parent,
        protected array $affectedRequestNos
    ) {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Slot driver/mobil trip ' . ($this->parent->request_no ?? ('#' . $this->parent->id))
                . ' dibatalkan. Request berikut perlu dicek ulang: ' . implode(', ', $this->affectedRequestNos) . '.',
            'request_id' => $this->parent->id,
            'url' => route('drms.approval.admin.index', ['status' => 'disetujui']),
        ];
    }
}
