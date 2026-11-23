{{-- Periode masa berlaku + badge. Pemakaian: @include('idcard.partials.periode', ['item' => $item]) (model RequestIdCard) --}}
@php $p = $item->periode_info; @endphp
@if($p)
    <div class="flex flex-col">
        <span class="text-xs text-gray-500">{{ $p['range'] }}</span>
        <span class="mt-1 self-start px-2 py-0.5 rounded-full text-xs font-semibold {{ $p['class'] }}">{{ $p['label'] }}</span>
    </div>
@elseif(in_array($item->kategori, \App\Models\IDCard\RequestIdCard::KATEGORI_TETAP))
    <span class="text-xs text-gray-400">Tetap</span>
@else
    <span class="text-xs text-gray-400">-</span>
@endif
