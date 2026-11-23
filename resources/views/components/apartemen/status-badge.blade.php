@props(['type'])
@php
    $map = [
        'belum_aktif' => ['Belum Aktif',     'bg-yellow-50 text-yellow-800 ring-yellow-200'],
        'active'      => ['Aktif',           'bg-green-50 text-green-800 ring-green-200'],
        'expired'     => ['Belum Check-out', 'bg-red-50 text-red-800 ring-red-200'],
        'CHECKIN'     => ['Check In',        'bg-blue-50 text-blue-800 ring-blue-200'],
        'SELESAI'     => ['Check Out',       'bg-green-50 text-green-800 ring-green-200'],
        'DIPINDAH'    => ['Dipindah',        'bg-indigo-50 text-indigo-800 ring-indigo-200'],
        'DIBATALKAN'  => ['Dibatalkan',      'bg-red-50 text-red-800 ring-red-200'],
    ];
    [$label, $cls] = $map[$type] ?? [$type ?: '-', 'bg-gray-50 text-gray-700 ring-gray-200'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap ring-1 ring-inset $cls"]) }}><span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>{{ $label }}</span>