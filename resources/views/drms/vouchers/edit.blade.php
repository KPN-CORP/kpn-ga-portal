@extends('layouts.app_car_sidebar')

@section('title', 'Edit Voucher')
@section('breadcrumb', 'Edit Voucher')

@section('content')
<div class="container mx-auto max-w-lg">
    <h1 class="text-2xl font-bold mb-4">Edit Voucher</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('drms.vouchers.update', $voucher->id) }}" method="POST" class="bg-white p-6 rounded shadow">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Kode Voucher</label>
            <input type="text" name="code" id="code" value="{{ old('code', $voucher->code) }}" required
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="nominal" class="block text-sm font-medium text-gray-700 mb-1">Nominal (Rp)</label>
            <input type="number" name="nominal" id="nominal" value="{{ old('nominal', $voucher->nominal) }}" required min="0"
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipe Voucher</label>
            <select name="type" id="type" required
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="grab" {{ old('type', $voucher->type) == 'grab' ? 'selected' : '' }}>Grab</option>
                <option value="gojek" {{ old('type', $voucher->type) == 'gojek' ? 'selected' : '' }}>Gojek</option>
                <option value="taxi" {{ old('type', $voucher->type) == 'taxi' ? 'selected' : '' }}>Taxi</option>
            </select>
        </div>

        <div class="mb-4">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" required
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="available" {{ old('status', $voucher->status) == 'available' ? 'selected' : '' }}>Available</option>
                <option value="used" {{ old('status', $voucher->status) == 'used' ? 'selected' : '' }}>Used</option>
            </select>
        </div>

        {{-- Optional: Tampilkan business unit sebagai informasi readonly jika diperlukan --}}
        @if(isset($voucher->business_unit_id))
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit ID</label>
                <input type="text" value="{{ $voucher->business_unit_id }}" disabled
                       class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600">
                <p class="text-xs text-gray-500 mt-1">Tidak dapat diubah</p>
            </div>
        @endif

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.vouchers.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
        </div>
    </form>
</div>
@endsection