@extends('layouts.app_work_sidebar')

@section('title', 'Kelola Kategori Pekerjaan')
@section('breadcrumb', 'Kategori')

@section('content')
<div class="bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">Daftar Kategori</h2>
        <a href="{{ route('work-reports.categories.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md">Tambah Kategori</a>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <table class="min-w-full divide-y divide-gray-200">
        <thead>
            32
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $cat)
            <tr>
                <td class="px-6 py-4">{{ $cat->name }}</td>
                <td class="px-6 py-4">{{ $cat->description ?? '-' }}</td>
                <td class="px-6 py-4 text-right space-x-2">
                    <a href="{{ route('work-reports.categories.edit', $cat) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                    <form method="POST" action="{{ route('work-reports.categories.destroy', $cat) }}" class="inline" onsubmit="return confirm('Hapus kategori ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection