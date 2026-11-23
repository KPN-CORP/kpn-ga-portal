@php
  $badge = match($status) {
    'Terkirim' => ['bg-green-100','text-green-700'],
    'Batal' => ['bg-gray-200','text-gray-600'],
    'Dokumen Belum Tersedia' => ['bg-amber-100','text-amber-700'],
    'Proses Pengiriman' => ['bg-blue-100','text-blue-700'],
    default => ['bg-gray-100','text-gray-600'],
  };
@endphp
<span class="text-xs px-2 py-1 rounded-full {{ $badge[0] }} {{ $badge[1] }} whitespace-nowrap">{{ $status }}</span>
@if (!empty($kurir ?? null))
  <p class="text-[10px] text-gray-400 mt-0.5 whitespace-nowrap">Kurir: {{ $kurir }}</p>
@endif