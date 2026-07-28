@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-2xl px-4 py-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">⬆️ Upload Voucher</h1>
        <a href="{{ route('drms.vouchers.index') }}" class="text-sm text-blue-600 hover:underline">← Kembali ke Daftar Voucher</a>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- TEMPLATE --}}
    <div class="bg-white p-6 rounded-lg shadow-sm border mb-6">
        <h2 class="font-semibold text-gray-700 mb-2">1. Unduh Template</h2>
        <p class="text-sm text-gray-500 mb-4">Pilih template sesuai format kode voucher yang Anda miliki, isi datanya, lalu upload kembali di bawah.</p>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('drms.vouchers.template', 'single') }}"
               class="flex-1 border rounded-lg px-4 py-3 hover:bg-gray-50 transition">
                <p class="font-medium text-gray-800">📄 Template 1 Voucher / Baris</p>
                <p class="text-xs text-gray-500 mt-1">Kolom: kode_voucher, nominal, tipe</p>
            </a>
            <a href="{{ route('drms.vouchers.template', 'double') }}"
               class="flex-1 border rounded-lg px-4 py-3 hover:bg-gray-50 transition">
                <p class="font-medium text-gray-800">📄 Template 2 Voucher / Baris</p>
                <p class="text-xs text-gray-500 mt-1">Kolom: kode_voucher_1, nominal_1, tipe_1, kode_voucher_2, nominal_2, tipe_2</p>
            </a>
        </div>
    </div>

    {{-- UPLOAD FORM --}}
    <div class="bg-white p-6 rounded-lg shadow-sm border">
        <h2 class="font-semibold text-gray-700 mb-4">2. Upload File</h2>
        <form action="{{ route('drms.vouchers.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Format Template</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="format" value="single" {{ old('format', 'single') == 'single' ? 'checked' : '' }} required>
                        1 Voucher / Baris
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="format" value="double" {{ old('format') == 'double' ? 'checked' : '' }}>
                        2 Voucher / Baris
                    </label>
                </div>
            </div>

            @if(($businessUnits ?? collect())->isNotEmpty())
            <div class="mb-4">
                <label for="business_unit_id" class="block text-sm font-medium text-gray-700 mb-1">Business Unit</label>
                <select name="business_unit_id" id="business_unit_id" required
                        class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Business Unit --</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ old('business_unit_id') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
            </div>
            @else
                <p class="text-xs text-gray-500 mb-4">Voucher akan otomatis tercatat untuk business unit Anda.</p>
            @endif

            <div class="mb-4">
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">File Excel</label>
                <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" required
                       class="w-full border rounded px-3 py-2 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-xs text-gray-400 mt-1">Gunakan file hasil dari template di atas (format .xlsx), maksimal 5MB. File .xls/.csv juga didukung.</p>
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('drms.vouchers.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Upload</button>
            </div>
        </form>
    </div>
</div>
@endsection
