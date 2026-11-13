@props(['label', 'value', 'total' => null, 'color' => 'gray', 'icon' => 'fas fa-circle', 'href' => null, 'active' => false])
@php
    // Persentase dari total (kartu "Total" tidak punya persen, batangnya penuh).
    $pct = ($total !== null && $total > 0) ? min(100, (int) round($value / $total * 100)) : null;
    $tag = $href ? 'a' : 'div';
@endphp
{{-- KPI yang bisa diklik sebagai filter cepat. Batang tipis di bawah = porsi terhadap total. --}}
<{{ $tag }} @if($href) href="{{ $href }}" @endif @if($active) aria-current="true" @endif
    {{ $attributes->class(['ap-card', 'ap-kpi', 't-' . $color, 'is-active' => $active]) }}>
    <span class="ap-kpi-label"><i class="{{ $icon }}"></i>{{ $label }}</span>
    <div class="ap-kpi-val">{{ number_format($value) }}@if($pct !== null)<span class="ap-kpi-pct">{{ $pct }}%</span>@endif</div>
    <div class="ap-kpi-bar"><span style="--w: {{ $pct ?? 100 }}%"></span></div>
</{{ $tag }}>
