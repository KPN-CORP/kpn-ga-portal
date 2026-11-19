{{-- partials/filter-chips.blade.php
     Chip filter aktif. Tiap chip punya tombol × untuk menghapus filter itu saja,
     dan otomatis kembali ke halaman 1 (param "page" dibuang).

     Pemakaian:
     @include('mailing.partials.filter-chips', [
         'route'   => 'mailing.index',
         'filters' => ['start_date' => 'Mulai', 'end_date' => 'Akhir', 'search' => 'Cari'],
     ])
--}}
@php
    $activeChips = collect($filters)->filter(fn ($label, $key) => request()->filled($key));
@endphp

@if($activeChips->isNotEmpty())
<div class="flex flex-wrap items-center gap-2">
    <span class="text-xs text-gray-500">
        <i class="fas fa-filter mr-1"></i>Filter aktif:
    </span>

    @foreach($activeChips as $key => $label)
    <a href="{{ route($route, request()->except([$key, 'page'])) }}"
       title="Hapus filter {{ $label }}"
       class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs rounded-full border border-blue-200 hover:bg-blue-100 transition">
        <span><span class="font-medium">{{ $label }}:</span> {{ request($key) }}</span>
        <i class="fas fa-times text-[10px]"></i>
    </a>
    @endforeach

    <a href="{{ route($route) }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold ml-1">
        Hapus semua
    </a>
</div>
@endif
