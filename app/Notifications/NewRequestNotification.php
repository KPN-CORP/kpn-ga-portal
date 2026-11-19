<?php

namespace App\Notifications;

use App\Models\Drms\DriverRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewRequestNotification extends Notification
{
    use Queueable;

    protected $driverRequest;

    public function __construct(DriverRequest $driverRequest)
    {
        $this->driverRequest = $driverRequest;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Permintaan driver baru dari ' . $this->driverRequest->requester->name,
            'request_id' => $this->driverRequest->id,
            'url' => route('drms.approval.l1.index'),
        ];
    }
}