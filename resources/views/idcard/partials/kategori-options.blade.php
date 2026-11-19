{{-- Opsi <select> kategori. Pemakaian: @include('idcard.partials.kategori-options', ['selected' => request('kategori'), 'allLabel' => 'Semua']) --}}
@if(isset($allLabel))
    <option value="all">{{ $allLabel }}</option>
@endif
@foreach(\App\Models\IDCard\RequestIdCard::KATEGORI_LABELS as $value => $label)
    <option value="{{ $value }}" {{ ($selected ?? null) === $value ? 'selected' : '' }}>{{ $label }}</option>
@endforeach
