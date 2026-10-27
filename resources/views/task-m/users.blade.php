@extends('layouts.app-sidebar')

@section('title', 'Daftar User - Task Monitor')

@section('content')
<div class="w-full px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
    <div class="mb-4">
        <h1 class="text-xl sm:text-2xl font-semibold text-gray-800">👥 Daftar User</h1>
        <p class="text-sm text-gray-500">Pilih user untuk melihat project-task monitor nya</p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Judul kolom (hanya layar lebar) --}}
        <div class="hidden lg:flex items-center gap-4 px-4 py-2 bg-gray-50 border-b border-gray-100 text-xs font-medium text-gray-500 uppercase">
            <div class="flex-1">Nama User</div>
            <div class="w-20">Project</div>
            <div class="w-24">Item Proses</div>
            <div class="w-24">Item Selesai</div>
            <div class="w-28">Selesai minggu ini</div>
            <div class="w-36">Proses tertua</div>
            <div class="w-44 text-right">Aksi</div>
        </div>

        <ul class="divide-y divide-gray-100">
            @foreach($users as $user)
                @php
                    $st        = $stats[$user->id];
                    $weekDone  = (int) ($doneThisWeek[$user->id] ?? 0);
                    $oldestRaw = $oldestPending[$user->id] ?? null;
                    $oldest    = $oldestRaw ? \Carbon\Carbon::parse($oldestRaw) : null;
                    $oldestAge = $oldest ? (int) abs($oldest->copy()->startOfDay()->diffInDays(now()->startOfDay())) : null;
                    $ageClass  = $oldestAge === null ? 'text-gray-400' : ($oldestAge > 21 ? 'text-red-600' : ($oldestAge > 7 ? 'text-amber-600' : 'text-gray-700'));
                @endphp
                <li class="p-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-4 hover:bg-gray-50">
                    <div class="flex-1 min-w-0 font-medium text-gray-800 break-words">{{ $user->name }}</div>

                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 lg:flex lg:items-center lg:gap-4">
                        <div class="lg:w-20">
                            <span class="lg:hidden block text-xs text-gray-500">Project</span>
                            <a href="{{ route('task-m.user.projects', $user->id) }}" class="inline-block py-1 text-indigo-600 hover:underline">{{ $st['total_projects'] }}</a>
                        </div>
                        <div class="lg:w-24">
                            <span class="lg:hidden block text-xs text-gray-500">Item Proses</span>
                            <a href="{{ route('task-m.units', ['status' => 'pending', 'user_id' => $user->id]) }}" class="inline-block py-1 text-yellow-600 hover:underline">{{ $st['total_pending'] }}</a>
                        </div>
                        <div class="lg:w-24">
                            <span class="lg:hidden block text-xs text-gray-500">Item Selesai</span>
                            <a href="{{ route('task-m.units', ['status' => 'done', 'user_id' => $user->id]) }}" class="inline-block py-1 text-green-600 hover:underline">{{ $st['total_done'] }}</a>
                        </div>
                        <div class="lg:w-28">
                            <span class="lg:hidden block text-xs text-gray-500">Selesai minggu ini</span>
                            <span class="inline-block py-1 text-gray-700">{{ $weekDone }}</span>
                        </div>
                        <div class="lg:w-36">
                            <span class="lg:hidden block text-xs text-gray-500">Proses tertua</span>
                            @if($oldest)
                                <span class="inline-block py-1 text-sm {{ $ageClass }}">{{ $oldest->translatedFormat('d M Y') }} ({{ $oldestAge }} hari)</span>
                            @else
                                <span class="inline-block py-1 text-gray-400">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-4 lg:w-44 lg:justify-end shrink-0">
                        <a href="{{ route('task-m.user.projects', $user->id) }}" class="inline-flex items-center h-10 text-indigo-600 hover:text-indigo-900 text-sm">
                            Lihat Project <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                        <a href="{{ route('task-m.report.weekly', ['user_id' => $user->id]) }}" class="inline-flex items-center h-10 text-green-700 hover:text-green-900 text-sm">
                            Laporan
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
