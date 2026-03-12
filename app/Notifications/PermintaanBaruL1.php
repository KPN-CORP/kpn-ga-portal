<?php
namespace App\Notifications;

use App\Models\StockCtl\Permintaan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PermintaanBaruL1 extends Notification
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
            'message' => 'Permintaan ATK baru dari ' . $this->permintaan->pemohon->name . ' memerlukan approval Anda.',
            'url' => route('stock-ctl.approval.l1.index'),
        ];
    }
}