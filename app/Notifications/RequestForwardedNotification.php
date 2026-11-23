<?php

namespace App\Notifications;

use App\Models\Drms\DriverRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestForwardedNotification extends Notification
{
    use Queueable;

    protected DriverRequest $driverRequest;
    protected User $forwardedBy;
    protected ?string $note;

    /**
     * Create a new notification instance.
     *
     * @param DriverRequest $driverRequest
     * @param User $forwardedBy
     * @param string|null $note
     */
    public function __construct(DriverRequest $driverRequest, User $forwardedBy, ?string $note = null)
    {
        $this->driverRequest = $driverRequest;
        $this->forwardedBy = $forwardedBy;
        $this->note = $note;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = url("/drms/approval/admin/{$this->driverRequest->id}/edit");

        $mail = (new MailMessage)
            ->subject("Permintaan Driver Dialihkan - {$this->driverRequest->request_no}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Permintaan driver dengan nomor **{$this->driverRequest->request_no}** telah dialihkan oleh **{$this->forwardedBy->name}** ke Business Unit Anda.")
            ->line("Rincian permintaan:")
            ->line("- Pemohon: {$this->driverRequest->requester->name}")
            ->line("- Tanggal Penggunaan: " . optional($this->driverRequest->usage_date)->format('d/m/Y'))
            ->line("- Tujuan: {$this->driverRequest->destination}");

        if ($this->note) {
            $mail->line("**Catatan dari pengirim:** {$this->note}");
        }

        $mail->action('Proses Permintaan', $url)
            ->line("Harap segera memproses permintaan ini di menu Approval Admin.");

        return $mail;
    }

    /**
     * Get the array representation of the notification for database.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'request_id' => $this->driverRequest->id,
            'request_no' => $this->driverRequest->request_no,
            'forwarded_by' => $this->forwardedBy->name,
            'forwarded_by_id' => $this->forwardedBy->id,
            'note' => $this->note,
            'message' => "Permintaan {$this->driverRequest->request_no} dialihkan ke BU Anda oleh {$this->forwardedBy->name}.",
        ];
    }
}