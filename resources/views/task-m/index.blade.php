@extends('layouts.app-sidebar')

@section('title', isset($user) ? 'Project - ' . $user->name : 'Project Monitor')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    @php
        $userId = isset($user) ? $user->id : Auth::id();
        // Tentukan route untuk link Project
        $projectLink = isset($user) ? route('task-m.user.projects', $user->id) : route('task-m.index');
        if ($year) {
            $projectLink .= '?year=' . $year;
        }
    @endphp

    {{-- Stats Cards (semua angka bisa diklik) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border-l-4 border-indigo-500 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Project</p>
                    <a href="{{ $projectLink }}" class="text-2xl font-bold text-gray-800 hover:underline">
                        {{ $stats['total_projects'] ?? 0 }}
                    </a>
                </div>
                <i class="fas fa-folder-open text-3xl text-indigo-300"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-l-4 border-yellow-500 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Item Proses</p>
                    <a href="{{ route('task-m.units', ['status' => 'pending', 'user_id' => $userId, 'year' => $year]) }}" class="text-2xl font-bold text-yellow-600 hover:underline">
                        {{ $stats['total_pending'] ?? 0 }}
                    </a>
                </div>
                <i class="fas fa-clock text-3xl text-yellow-300"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-l-4 border-green-500 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Item Done</p>
                    <a href="{{ route('task-m.units', ['status' => 'done', 'user_id' => $userId, 'year' => $year]) }}" class="text-2xl font-bold text-green-600 hover:underline">
                        {{ $stats['total_done'] ?? 0 }}
                    </a>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-300"></i>
            </div>
        </div>
    </div>

    {{-- Filter Tahun --}}
    <div class="mb-4 flex flex-wrap justify-between items-center gap-3">
        <form method="GET" action="{{ isset($user) ? route('task-m.user.projects', $user->id) : route('task-m.index') }}" class="flex gap-2">
            <select name="year" class="rounded-xl border-gray-200 px-3 py-2 text-sm">
                <option value="">Semua Tahun</option>
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ ($year == $y) ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
            @if($year)
                <a href="{{ isset($user) ? route('task-m.user.projects', $user->id) : route('task-m.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm hover:bg-gray-400">
                    Reset
                </a>
            @endif
        </form>
        <button onclick="openCreateModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-full p-3 shadow-md transition">
            <i class="fas fa-plus text-lg"></i>
        </button>
    </div>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                @if(isset($user))
                    📁 Project Milik: {{ $user->name }}
                @else
                    📋 Daftar Project
                @endif
            </h1>
            <p class="text-sm text-gray-500">Kelola progress</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
    @endif

    @if($projects->count())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tahun</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($projects as $project)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('task-m.show', $project->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                    {{ $project->title }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $project->start_date->format('Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 max-w-[200px]">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-indigo-600 h-2.5 rounded-full" style="width: {{ $project->progressPercentage() }}%"></div>
                                        </div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700">{{ $project->progressPercentage() }}%</span>
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ $project->doneUnitsCount() }} / {{ $project->units->count() }} unit selesai
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-16 bg-gray-50 rounded-2xl">
            <i class="fas fa-folder-open text-5xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">Belum ada project</p>
            <button onclick="openCreateModal()" class="inline-block mt-3 bg-indigo-600 text-white px-4 py-2 rounded-full text-sm">
                Buat Project Baru
            </button>
        </div>
    @endif
</div>

{{-- Modal Create Project --}}
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="if(event.target === this) closeCreateModal()">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Buat Project Baru</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form action="{{ route('task-m.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Project</label>
                <input type="text" name="title" required class="w-full rounded-xl border-gray-200 focus:border-indigo-400 focus:ring focus:ring-indigo-200">
            </div>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" required class="w-full rounded-xl border-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="end_date" required class="w-full rounded-xl border-gray-200">
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 border rounded-xl">Batal</button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl hover:bg-indigo-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
        document.getElementById('createModal').classList.add('flex');
    }
    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
        document.getElementById('createModal').classList.remove('flex');
    }
</script>
@endsection