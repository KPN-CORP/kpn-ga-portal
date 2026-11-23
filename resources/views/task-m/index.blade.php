@extends('layouts.app-sidebar')

@section('title', isset($user) ? 'Project - ' . $user->name : 'Project Monitor')

@section('content')
@php
    $userId  = isset($user) ? $user->id : Auth::id();
    $baseUrl = isset($user) ? route('task-m.user.projects', $user->id) : route('task-m.index');
@endphp

<div class="w-full px-3 sm:px-6 lg:px-8 py-4 sm:py-6">

    {{-- Judul --}}
    <div class="mb-4">
        <h1 class="text-xl sm:text-2xl font-semibold text-gray-800 break-words">
            @if(isset($user))
                📁 Project Milik: {{ $user->name }}
            @else
                📋 Daftar Project
            @endif
        </h1>
        <p class="text-sm text-gray-500">Kelola progress</p>
    </div>

    {{-- Stats Cards (seluruh kartu bisa diketuk) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-5">
        <a href="#daftar-project" class="block bg-white rounded-xl shadow-sm border-l-4 border-indigo-500 p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Project</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_projects'] ?? 0 }}</p>
                </div>
                <i class="fas fa-folder-open text-3xl text-indigo-300"></i>
            </div>
        </a>
        <a href="{{ route('task-m.units', ['status' => 'pending', 'user_id' => $userId, 'year' => $year]) }}"
           class="block bg-white rounded-xl shadow-sm border-l-4 border-yellow-500 p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Item Proses</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['total_pending'] ?? 0 }}</p>
                </div>
                <i class="fas fa-clock text-3xl text-yellow-300"></i>
            </div>
        </a>
        <a href="{{ route('task-m.units', ['status' => 'done', 'user_id' => $userId, 'year' => $year]) }}"
           class="block bg-white rounded-xl shadow-sm border-l-4 border-green-500 p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Item Selesai</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['total_done'] ?? 0 }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-300"></i>
            </div>
        </a>
    </div>

    {{-- Toolbar: filter tahun + aksi --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ $baseUrl }}" class="flex items-center gap-2">
            <select name="year" onchange="this.form.submit()"
                    class="flex-1 sm:flex-none rounded-xl border-gray-200 px-3 py-2.5 text-sm">
                <option value="">Semua Tahun</option>
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ ($year == $y) ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <noscript>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-sm">Filter</button>
            </noscript>
            @if($year)
                <a href="{{ $baseUrl }}" class="shrink-0 bg-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm hover:bg-gray-300">
                    Reset
                </a>
            @endif
        </form>

        <div class="flex items-center gap-2">
            <a href="{{ route('task-m.report.weekly', isset($user) ? ['user_id' => $user->id] : []) }}"
               class="flex-1 sm:flex-none text-center bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-xl text-sm shadow-sm transition">
                Laporan Mingguan
            </a>
            <button type="button" onclick="openCreateModal()"
                    class="flex-1 sm:flex-none bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm shadow-sm transition">
                <i class="fas fa-plus mr-1"></i> Project Baru
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Daftar project: satu tampilan untuk HP, tablet, desktop --}}
    @if($projects->count())
        <ul id="daftar-project" class="scroll-mt-4 bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-100 overflow-hidden">
            @foreach($projects as $project)
                @php
                    $pct       = $project->progressPercentage();
                    $doneCount = $project->doneUnitsCount();
                    $active    = $project->activeUnitsCount();
                    $cancelled = $project->cancelledUnitsCount();
                @endphp
                <li>
                    <a href="{{ route('task-m.show', $project->id) }}"
                       class="block p-4 hover:bg-gray-50 sm:flex sm:items-center sm:justify-between sm:gap-6">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-indigo-700 break-words">{{ $project->title }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">Tahun {{ $project->start_date->format('Y') }}</p>
                        </div>
                        <div class="mt-3 sm:mt-0 sm:w-64 shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-indigo-600 h-2.5 rounded-full" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-700 w-10 text-right">{{ $pct }}%</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $doneCount }} / {{ $active }} unit aktif selesai
                                @if($cancelled) &middot; {{ $cancelled }} dibatalkan @endif
                            </p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <div id="daftar-project" class="text-center py-16 bg-gray-50 rounded-2xl">
            <i class="fas fa-folder-open text-5xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">Belum ada project{{ $year ? ' di tahun ' . $year : '' }}</p>
            <button type="button" onclick="openCreateModal()" class="inline-block mt-3 bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm">
                Buat Project Baru
            </button>
        </div>
    @endif
</div>

{{-- Modal Buat Project --}}
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4" onclick="if(event.target === this) closeCreateModal()">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-screen overflow-y-auto p-5 sm:p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg sm:text-xl font-bold text-gray-800">Buat Project Baru</h3>
            <button type="button" onclick="closeCreateModal()" aria-label="Tutup"
                    class="w-10 h-10 -mr-2 flex items-center justify-center text-2xl text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form action="{{ route('task-m.store') }}" method="POST">
            @csrf
            @if(isset($user))
                <input type="hidden" name="user_id" value="{{ $user->id }}">
                <p class="mb-3 text-xs text-gray-500">Project dibuat untuk <b>{{ $user->name }}</b>.</p>
            @endif
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Project</label>
                <input type="text" name="title" required maxlength="255" value="{{ old('title') }}"
                       class="w-full rounded-xl border-gray-200 focus:border-indigo-400 focus:ring focus:ring-indigo-200">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="w-full rounded-xl border-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full rounded-xl border-gray-200">
                </div>
            </div>
            <p class="text-xs text-gray-400 mb-4">Kosongkan tanggal bila project tidak punya deadline.</p>
            <div class="flex gap-2 sm:justify-end">
                <button type="button" onclick="closeCreateModal()" class="flex-1 sm:flex-none px-4 py-2.5 border rounded-xl">Batal</button>
                <button type="submit" class="flex-1 sm:flex-none bg-indigo-600 text-white px-4 py-2.5 rounded-xl hover:bg-indigo-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        var m = document.getElementById('createModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        var t = m.querySelector('input[name="title"]');
        if (t) t.focus();
    }
    function closeCreateModal() {
        var m = document.getElementById('createModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeCreateModal();
    });
    @if($errors->any() && old('title') !== null)
        openCreateModal();
    @endif
</script>
@endsection
