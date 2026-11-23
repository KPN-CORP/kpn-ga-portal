<?php

namespace App\Notifications;

use App\Models\Drms\DriverRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestApprovedAdminNotification extends Notification
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
        $type = $this->driverRequest->transport_type;
        $message = "Permintaan driver Anda telah diproses. ";
        if ($type === 'company_driver') {
            $message .= "Driver: {$this->driverRequest->driver->name}, Kendaraan: {$this->driverRequest->vehicle->plate_number}.";
        } elseif ($type === 'voucher') {
            $message .= "Voucher {$this->driverRequest->voucher->type} senilai Rp " . number_format($this->driverRequest->voucher->nominal,0,',','.');
        } else {
            $message .= "Mobil rental.";
        }

        return [
            'message' => $message,
            'request_id' => $this->driverRequest->id,
            'url' => route('drms.requests.show', $this->driverRequest->id),
        ];
    }
}