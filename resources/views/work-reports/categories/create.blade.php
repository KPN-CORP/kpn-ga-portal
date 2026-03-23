@extends('layouts.app_work_sidebar')

@section('title', 'Tambah Kategori')
@section('breadcrumb', 'Tambah Kategori')

@section('content')
<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow">
    <h2 class="text-xl font-semibold mb-4">Tambah Kategori Baru</h2>
    <form method="POST" action="{{ route('work-reports.categories.store') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Nama Kategori</label>
            <input type="text" name="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Deskripsi (opsional)</label>
            <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
        </div>
        <div class="flex justify-end">
            <a href="{{ route('work-reports.categories.index') }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md mr-2">Batal</a>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Simpan</button>
        </div>
    </form>
</div>
@endsection