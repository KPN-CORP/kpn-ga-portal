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
        <p class="text-sm text-gray-500 mb-3">Isi datanya sesuai kolom, lalu upload kembali di bawah.</p>

        <a href="{{ route('drms.vouchers.template', 'default') }}"
           class="block border rounded-lg px-4 py-3 hover:bg-gray-50 transition mb-3">
            <p class="font-medium text-gray-800">📄 Download Template Voucher</p>
            <p class="text-xs text-gray-500 mt-1">Kolom: kode_voucher, nominal, Status, tipe, expired_at, Business Unit, Dibebankan ke BU</p>
        </a>

        <ul class="text-xs text-gray-500 space-y-1.5 list-disc pl-4">
            <li><span class="font-mono">kode_voucher</span> — isi 1 kode (contoh: <span class="font-mono">wrtgf</span>), atau 2 kode digabung dengan " &amp; " langsung di sel yang sama (contoh: <span class="font-mono">kdfjd &amp; jhdfu</span>) untuk mencatatnya sebagai 1 voucher gabungan. Kalau digabung, kolom <span class="font-mono">nominal</span> di baris itu dianggap TOTAL gabungan (bukan per kode).</li>
            <li><span class="font-mono">Status</span> — opsional, <span class="font-mono">available</span> atau <span class="font-mono">used</span>. Kosongkan untuk otomatis <span class="font-mono">available</span>.</li>
            <li><span class="font-mono">expired_at</span> — opsional (format tanggal: YYYY-MM-DD). Baris yang dikosongkan akan memakai <span class="font-medium">Tanggal Expired Default</span> di form upload di bawah (kalau diisi).</li>
            @if($isSuperAdmin ?? false)
                <li><span class="font-mono">Business Unit</span> — opsional, isi nama Business Unit persis (mis. "KPN Corporation") untuk menentukan BU voucher itu per baris. Kosongkan untuk memakai BU default yang dipilih di form upload di bawah.</li>
            @else
                <li><span class="font-mono">Business Unit</span> — kolom ini diabaikan untuk akun Anda; semua voucher otomatis tercatat milik Business Unit Anda sendiri.</li>
            @endif
            @if($isSpecialBu ?? false)
                <li><span class="font-mono">Dibebankan ke BU</span> — opsional, isi nama Business Unit tujuan pembebanan biaya per baris (khusus akun KPN Corporation).</li>
            @else
                <li><span class="font-mono">Dibebankan ke BU</span> — kolom ini tidak berlaku untuk akun Anda, boleh dikosongkan.</li>
            @endif
        </ul>
    </div>

    {{-- UPLOAD FORM --}}
    <div class="bg-white p-6 rounded-lg shadow-sm border">
        <h2 class="font-semibold text-gray-700 mb-4">2. Upload File</h2>
        <form action="{{ route('drms.vouchers.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="expired_at" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Expired Default (opsional)</label>
                <input type="date" name="expired_at" id="expired_at" value="{{ old('expired_at') }}"
                       class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Dipakai untuk semua baris pada file yang tidak mengisi kolom <span class="font-mono">expired_at</span> sendiri. Kosongkan jika voucher tidak memiliki masa berlaku.</p>
            </div>

            @if(($businessUnits ?? collect())->isNotEmpty())
            <div class="mb-4">
                <label for="business_unit_id" class="block text-sm font-medium text-gray-700 mb-1">Business Unit Default</label>
                <select name="business_unit_id" id="business_unit_id" required
                        class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Business Unit --</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ old('business_unit_id', $ownBusinessUnitId ?? '') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Dipakai untuk baris yang kolom "Business Unit"-nya dikosongkan di file.</p>
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