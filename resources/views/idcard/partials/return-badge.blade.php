{{-- Badge status pengembalian kartu. Pemakaian: @include('idcard.partials.return-badge', ['status' => $item->return_status])
     Status 'none' tidak menampilkan apa-apa. --}}
@php
    $status = $status ?? 'none';
    $cls = [
        'requested' => 'bg-yellow-100 text-yellow-800',
        'returned'  => 'bg-blue-100 text-blue-800',
        'rejected'  => 'bg-red-100 text-red-800',
    ][$status] ?? null;
    $lbl = \App\Models\IDCard\RequestIdCard::RETURN_STATUS_LABELS[$status] ?? $status;
@endphp
@if($cls)
    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $cls }}">{{ $lbl }}</span>
@endif
