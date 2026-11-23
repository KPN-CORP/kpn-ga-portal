@props(['label', 'value', 'total' => null, 'color' => 'gray', 'href' => null, 'active' => false, 'hint' => null])
@php
    // Persentase dari $total (opsional). Tanpa $total, batang bawah penuh sebagai aksen warna.
    $pct = ($total !== null && $total > 0) ? min(100, (int) round($value / $total * 100)) : null;
    $tag = $href ? 'a' : 'div';
@endphp
{{-- Kartu statistik standar. Diberi $href → jadi tombol filter cepat; $active menandai filter yang sedang dipakai. Slot = ikon <i>. --}}
<{{ $tag }} @if($href) href="{{ $href }}" @endif @if($active) aria-current="true" @endif
    {{ $attributes->class(['ap-card', 'ap-kpi', 't-' . $color, 'is-active' => $active]) }}>
    <span class="ap-kpi-label">{{ $slot }}<span class="truncate">{{ $label }}</span></span>
    <div class="ap-kpi-val">{{ number_format($value) }}@if($pct !== null)<span class="ap-kpi-pct">{{ $pct }}%</span>@endif</div>
    @if($hint)<div class="ap-kpi-hint">{{ $hint }}</div>@endif
    <div class="ap-kpi-bar"><span style="--w: {{ $pct ?? 100 }}%"></span></div>
</{{ $tag }}>