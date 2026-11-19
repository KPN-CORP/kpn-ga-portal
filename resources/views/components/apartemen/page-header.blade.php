@props(['title', 'subtitle' => null])
{{-- Header halaman seragam: judul di kiri, tombol aksi (slot) di kanan. --}}
<div class="mb-4 md:mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div class="min-w-0">
        <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-xs md:text-sm text-gray-500 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @if(trim((string) $slot) !== '')
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>