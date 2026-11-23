@php
    // Class ditulis utuh (bukan bg-{{ $warna }}) supaya tidak ter-purge oleh Tailwind.
    $badge = [
        'pending'   => ['bg-yellow-100 text-yellow-800', 'Pending'],
        'disetujui' => ['bg-green-100 text-green-800',   'Disetujui'],
        'ditolak'   => ['bg-red-100 text-red-800',       'Ditolak'],
    ][$status] ?? ['bg-gray-100 text-gray-800', ucfirst($status)];
@endphp
<span class="{{ $badge[0] }} px-2 py-1 rounded-full text-xs">{{ $badge[1] }}</span>
