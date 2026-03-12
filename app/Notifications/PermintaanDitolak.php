<?php
namespace App\Notifications;

use App\Models\StockCtl\Permintaan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PermintaanDitolak extends Notification
{
    use Queueable;

    protected $permintaan;

    public function __construct(Permintaan $permintaan)
    {
        $this->permintaan = $permintaan;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'id_permintaan' => $this->permintaan->id_permintaan,
            'message' => 'Permintaan ATK Anda ditolak. Alasan: ' . $this->permintaan->rejection_reason,
            'url' => route('stock-ctl.permintaan.show', $this->permintaan->id_permintaan),
        ];
    }
}