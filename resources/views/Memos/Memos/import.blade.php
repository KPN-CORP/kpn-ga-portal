@extends('layouts.app_memos')
@section('title', 'Import Excel Memo')
@section('content')
<div class="w-full px-2 md:px-4 max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Import Excel Memo</h2>
        <a href="{{ route('memos.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke Daftar Memo</a>
    </div>

    @if(session('error'))
        <div class="mb-4 bg-red-50 text-red-700 text-sm px-4 py-2 rounded-lg">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 bg-red-50 text-red-700 text-sm px-4 py-2 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-600 mb-4">
            Upload file mailing (format sama seperti file yang dulu dipakai mail merge Word).
            Setiap baris data di tiap sheet akan otomatis dibuatkan <strong>1 memo berstatus Draf</strong> —
            silakan dicek/edit dulu di daftar memo sebelum di-submit satu per satu.
        </p>

        <form action="{{ route('memos.import.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">File Excel (.xlsx)</label>
                <input type="file" name="file" accept=".xlsx,.xls" required
                    class="w-full border rounded-lg p-2 text-sm">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm">
                <i class="fas fa-upload"></i> Import & Buat Memo Draf
            </button>
        </form>
    </div>

    <div class="bg-gray-50 rounded-xl p-4 mt-4 text-xs text-gray-500">
        <p class="font-semibold mb-1">Catatan:</p>
        <ul class="list-disc list-inside space-y-1">
            <li>Sheet "Info Bank" dilewati (itu tabel referensi, bukan data memo).</li>
            <li>Sheet yang belum punya surat master (mis. "Klinik Arandra", "Other") tetap dibuatkan memo, tapi ditandai perlu dicek manual karena rekening tujuan belum bisa dipastikan otomatis.</li>
            <li>Baris tanpa Nama Karyawan / Tagihan yang valid akan dilewati.</li>
        </ul>
    </div>
</div>
@endsection
