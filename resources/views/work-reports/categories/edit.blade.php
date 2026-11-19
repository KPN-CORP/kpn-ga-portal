@extends('layouts.app_work_sidebar')

@section('title', 'Edit Kategori')
@section('breadcrumb', 'Edit Kategori')

@section('content')
<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow">
    <h2 class="text-xl font-semibold mb-4">Edit Kategori</h2>
    <form method="POST" action="{{ route('work-reports.categories.update', $workReportCategory) }}">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Nama Kategori</label>
            <input type="text" name="name" value="{{ old('name', $workReportCategory->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Deskripsi (opsional)</label>
            <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('description', $workReportCategory->description) }}</textarea>
        </div>
        <div class="flex justify-end">
            <a href="{{ route('work-reports.categories.index') }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md mr-2">Batal</a>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Update</button>
        </div>
    </form>
</div>
@endsection