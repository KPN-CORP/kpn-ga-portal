{{-- Opsi <select> status. Pemakaian: @include('idcard.partials.status-options', ['selected' => request('status'), 'allLabel' => 'Semua']) --}}
@if(isset($allLabel))
    <option value="all">{{ $allLabel }}</option>
@endif
@foreach(\App\Models\IDCard\RequestIdCard::STATUS_LABELS as $value => $label)
    <option value="{{ $value }}" {{ ($selected ?? null) === $value ? 'selected' : '' }}>{{ $label }}</option>
@endforeach
