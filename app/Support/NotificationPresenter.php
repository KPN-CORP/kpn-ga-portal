<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;

/**
 * Menyeragamkan tampilan notifikasi dari berbagai modul (Stock Control, DRMS, dll)
 * supaya bisa dirender dengan satu komponen bell, mirip notification tray di Android
 * (setiap "aplikasi"/modul punya ikon & warna sendiri).
 *
 * Tinggal daftarkan class notifikasi baru di $map kalau ada modul baru yang
 * ingin ditampilkan di bell notifikasi dashboard.
 */
class NotificationPresenter
{
    /**
     * Mapping FQCN notifikasi => [label modul, ikon Font Awesome, warna aksen].
     */
    protected static array $map = [
        \App\Notifications\PermintaanBaru::class => [
            'label' => 'Stock Control', 'icon' => 'fa-boxes-stacked', 'color' => '#e67e22',
        ],
        \App\Notifications\PermintaanBaruL1::class => [
            'label' => 'Stock Control', 'icon' => 'fa-boxes-stacked', 'color' => '#e67e22',
        ],
        \App\Notifications\PermintaanMenungguAdmin::class => [
            'label' => 'Stock Control', 'icon' => 'fa-boxes-stacked', 'color' => '#e67e22',
        ],
        \App\Notifications\PermintaanDisetujui::class => [
            'label' => 'Stock Control', 'icon' => 'fa-circle-check', 'color' => '#27ae60',
        ],
        \App\Notifications\PermintaanDitolak::class => [
            'label' => 'Stock Control', 'icon' => 'fa-circle-xmark', 'color' => '#e74c3c',
        ],
        \App\Notifications\NewRequestNotification::class => [
            'label' => 'DRMS', 'icon' => 'fa-car', 'color' => '#3498db',
        ],
        \App\Notifications\RequestApprovedL1Notification::class => [
            'label' => 'DRMS', 'icon' => 'fa-car', 'color' => '#3498db',
        ],
        \App\Notifications\RequestApprovedAdminNotification::class => [
            'label' => 'DRMS', 'icon' => 'fa-circle-check', 'color' => '#27ae60',
        ],
        \App\Notifications\RequestForwardedNotification::class => [
            'label' => 'DRMS', 'icon' => 'fa-share-from-square', 'color' => '#3498db',
        ],
    ];

    protected static array $default = [
        'label' => 'GA Portal', 'icon' => 'fa-bell', 'color' => '#2c3e50',
    ];

    /**
     * Ubah satu DatabaseNotification jadi array siap-tampil.
     */
    public static function present(DatabaseNotification $notification): array
    {
        $meta = static::$map[$notification->type] ?? static::$default;
        $data = $notification->data ?? [];

        return [
            'id'         => $notification->id,
            'label'      => $meta['label'],
            'icon'       => $meta['icon'],
            'color'      => $meta['color'],
            'message'    => $data['message'] ?? 'Anda memiliki notifikasi baru.',
            'url'        => $data['url'] ?? route('notifications.index'),
            'is_read'    => ! is_null($notification->read_at),
            'created_at' => $notification->created_at,
            'time_ago'   => $notification->created_at?->diffForHumans(),
        ];
    }

    public static function presentMany(iterable $notifications): array
    {
        $result = [];
        foreach ($notifications as $notification) {
            $result[] = static::present($notification);
        }
        return $result;
    }
}
