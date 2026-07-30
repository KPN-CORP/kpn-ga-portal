@php
  $badge = match($status) {
    'Terkirim' => ['bg-green-100','text-green-700'],
    'Batal' => ['bg-gray-200','text-gray-600'],
    'Dokumen Belum Tersedia' => ['bg-amber-100','text-amber-700'],
    'Proses Pengiriman' => ['bg-orange-100','text-orange-700'],
    default => ['bg-gray-100','text-gray-600'],
  };
@endphp
<span class="text-xs px-2 py-1 rounded-full {{ $badge[0] }} {{ $badge[1] }} whitespace-nowrap">{{ $status }}</span>
