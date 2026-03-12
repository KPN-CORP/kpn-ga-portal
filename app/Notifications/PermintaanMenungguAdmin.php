<?php
namespace App\Notifications;

use App\Models\StockCtl\Permintaan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PermintaanMenungguAdmin extends Notification
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
            'message' => 'Permintaan ATK dari ' . $this->permintaan->pemohon->name . ' telah disetujui atasan dan menunggu approval admin.',
            'url' => route('stock-ctl.approval.admin.index'),
        ];
    }
}