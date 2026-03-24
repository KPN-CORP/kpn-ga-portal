@extends('layouts.app_work_sidebar')

@section('title', 'Laporan Pekerjaan')
@section('breadcrumb', 'Laporan Bulanan')

@section('content')
<div class="container mx-auto">
    <div class="flex flex-wrap justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Laporan Pekerjaan</h1>
        <div class="space-x-2">
            <a href="{{ route('work-reports.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                <i class="fas fa-plus mr-1"></i> Tambah Laporan
            </a>
            @if(auth()->user()->isWorkAdmin())
            <a href="{{ route('work-reports.categories.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition">
                <i class="fas fa-tags mr-1"></i> Kelola Kategori
            </a>
            @endif
        </div>
    </div>

    <!-- Filter Bulan, Kategori & Lokasi -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form method="GET" action="{{ route('work-reports.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Bulan</label>
                <select name="month" class="mt-1 block w-48 rounded-md border-gray-300 shadow-sm">
                    @foreach($months as $key => $label)
                        <option value="{{ $key }}" {{ $month == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Kategori</label>
                <select name="category_id" class="mt-1 block w-48 rounded-md border-gray-300 shadow-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Lokasi</label>
                <input type="text" name="location" value="{{ $location ?? '' }}" class="mt-1 block w-48 rounded-md border-gray-300 shadow-sm" placeholder="Cari lokasi...">
            </div>

            <!-- Tombol & Info Jumlah -->
            <div class="flex-1 flex flex-wrap items-center justify-between gap-4">
                <div class="flex space-x-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Tampilkan</button>
                    <a href="{{ route('work-reports.export', request()->query()) }}" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
                        <i class="fas fa-file-excel mr-1"></i> Export Excel
                    </a>
                </div>
                <div class="text-gray-600">
                    Menampilkan <strong>{{ $reports->count() }}</strong> laporan
                    @if($categoryId || $location)
                        <span class="text-sm text-gray-500">(filter)</span>
                        <a href="{{ route('work-reports.index', ['month' => $month]) }}" class="ml-2 text-blue-600 hover:underline text-sm">Reset Filter</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    @if($reports->count())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($reports as $report)
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- Foto Before & After -->
                <div class="grid grid-cols-2 gap-2 p-3 bg-gray-50">
                    @if($report->photo_before)
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Sebelum</p>
                            <img src="{{ route('private.storage', $report->photo_before) }}" class="w-full h-32 object-cover rounded">
                        </div>
                    @else
                        <div class="bg-gray-100 h-32 flex items-center justify-center text-gray-400 rounded">
                            <p class="text-xs">Tidak ada foto</p>
                        </div>
                    @endif
                    @if($report->photo_after)
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Sesudah</p>
                            <img src="{{ route('private.storage', $report->photo_after) }}" class="w-full h-32 object-cover rounded">
                        </div>
                    @else
                        <div class="bg-gray-100 h-32 flex items-center justify-center text-gray-400 rounded">
                            <p class="text-xs">Tidak ada foto</p>
                        </div>
                    @endif
                </div>

                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                            {{ $report->category->name }}
                        </span>
                        @if($report->canBeModifiedBy(auth()->user()))
                            <div class="dropdown relative" x-data="{ open: false }">
                                <button @click="open = !open" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-32 bg-white rounded-md shadow-lg z-10">
                                    <a href="{{ route('work-reports.edit', $report) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit</a>
                                    <form method="POST" action="{{ route('work-reports.destroy', $report) }}" onsubmit="return confirm('Hapus laporan ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="mt-3 space-y-1 text-sm">
                        <p><i class="fas fa-layer-group w-5"></i> <strong>Lantai:</strong> {{ $report->floor }}</p>
                        <p><i class="fas fa-map-marker-alt w-5"></i> <strong>Lokasi:</strong> {{ $report->location }}</p>
                        <p><i class="fas fa-calendar-alt w-5"></i> <strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($report->report_date)->isoFormat('D MMMM Y') }}</p>
                        <p><i class="fas fa-clock w-5"></i> <strong>Jam:</strong> {{ \Carbon\Carbon::parse($report->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($report->end_time)->format('H:i') }}</p>
                        <p class="mt-2 pt-2 border-t"><i class="fas fa-tasks w-5"></i> <strong>Keterangan:</strong> {{ $report->description }}</p>
                    </div>
                    <div class="mt-4 text-xs text-gray-400">
                        Dibuat oleh: {{ $report->creator->name }}<br>
                        {{ $report->created_at->diffForHumans() }}
                        @if(!$report->isEditable())
                            <span class="text-red-500 ml-2">(tidak dapat diedit)</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @else
    <div class="bg-white rounded-lg p-8 text-center text-gray-500 shadow">
        <i class="fas fa-folder-open text-4xl mb-2"></i>
        <p>Tidak ada laporan untuk bulan ini.</p>
    </div>
    @endif
</div>
@endsection