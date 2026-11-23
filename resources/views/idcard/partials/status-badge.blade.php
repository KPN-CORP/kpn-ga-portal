{{-- Badge status request. Pemakaian: @include('idcard.partials.status-badge', ['status' => $item->status])
     Bila $returnStatus diisi (requested/returned/rejected), badge pengembalian ikut tampil di bawahnya. --}}
@php
    $cls = [
        'pending'  => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
    ][$status] ?? 'bg-gray-100 text-gray-800';
    $lbl = \App\Models\IDCard\RequestIdCard::STATUS_LABELS[$status] ?? ucfirst($status);
@endphp
<span class="inline-block px-2 py-1 rounded-full text-xs font-semibold {{ $cls }}">{{ $lbl }}</span>
@if(!empty($returnStatus) && $returnStatus !== 'none')
    <div>@include('idcard.partials.return-badge', ['status' => $returnStatus])</div>
@endif
