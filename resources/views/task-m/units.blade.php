@extends('layouts.app-sidebar')

@section('title', "Daftar Unit - $statusLabel")

@section('content')
@php
    $tabs    = ['pending' => 'Proses', 'done' => 'Selesai', 'cancelled' => 'Dibatalkan'];
    $isOther = isset($user) && (int) $user->id !== (int) auth()->id();
    $backUrl = $isOther ? route('task-m.user.projects', $user->id) : route('task-m.index');
    if ($year) {
        $backUrl .= '?year=' . $year;
    }
@endphp

<div class="w-full px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
    <div class="mb-4">
        <h1 class="text-xl sm:text-2xl font-semibold text-gray-800">
            📋 Daftar Unit <span class="text-indigo-600">{{ $statusLabel }}</span>
        </h1>
        <p class="text-sm text-gray-500">
            @if($isOther)
                Milik: {{ $user->name }}
            @else
                Milik Anda
            @endif
            @if($year)
                <span class="ml-2 px-2 py-1 bg-gray-100 rounded-full text-xs">Tahun: {{ $year }}</span>
            @endif
            &middot; {{ $units->total() }} unit
        </p>
    </div>

    {{-- Pindah status tanpa kembali ke halaman sebelumnya --}}
    <nav class="flex gap-2 overflow-x-auto pb-2 mb-4 -mx-3 px-3 sm:mx-0 sm:px-0">
        @foreach($tabs as $key => $label)
            <a href="{{ route('task-m.units', ['status' => $key, 'user_id' => $userId, 'year' => $year]) }}"
               class="shrink-0 px-4 py-2 rounded-full text-sm border {{ $status === $key ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if($units->count())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            {{-- Judul kolom (hanya layar lebar) --}}
            <div class="hidden lg:flex items-center gap-4 px-4 py-2 bg-gray-50 border-b border-gray-100 text-xs font-medium text-gray-500 uppercase">
                <div class="w-1/4">Project</div>
                <div class="flex-1">Deskripsi Unit</div>
                <div class="w-28">Status</div>
                <div class="w-40">Waktu</div>
                <div class="w-28 text-right">Aksi</div>
            </div>

            <ul class="divide-y divide-gray-100">
                @foreach($units as $unit)
                    @php
                        $createdAt = $unit->created_at ? $unit->created_at->format('d/m/Y H:i') : '-';
                        $finalAt   = $unit->final_at ? $unit->final_at->format('d/m/Y H:i') : null;
                    @endphp
                    <li class="p-4 flex flex-col gap-2 lg:flex-row lg:items-start lg:gap-4 hover:bg-gray-50">
                        <div class="lg:w-1/4 min-w-0">
                            <a href="{{ route('task-m.show', $unit->task_monitor_id) }}" class="text-indigo-600 hover:underline break-words text-sm font-medium">
                                {{ $unit->taskMonitor->title ?? '-' }}
                            </a>
                        </div>
                        <div class="flex-1 min-w-0 text-gray-800 break-words">{!! nl2br(e($unit->description)) !!}</div>
                        <div class="lg:w-28 shrink-0">
                            <span class="inline-block text-xs px-2 py-1 rounded-full {{ $unit->status_class }}">{{ $unit->status_label }}</span>
                        </div>
                        <div class="lg:w-40 shrink-0 text-xs text-gray-500 leading-5">
                            <div>Dibuat: {{ $createdAt }}</div>
                            @if($finalAt)
                                <div>{{ $unit->status === 'done' ? 'Selesai' : 'Batal' }}: {{ $finalAt }}</div>
                            @endif
                        </div>
                        <div class="lg:w-28 shrink-0 lg:text-right">
                            <a href="{{ route('task-m.show', $unit->task_monitor_id) }}" class="inline-flex items-center h-10 text-indigo-600 hover:text-indigo-900 text-sm">
                                Lihat Project <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-4">
            {{ $units->links() }}
        </div>
    @else
        <div class="text-center py-16 bg-gray-50 rounded-2xl">
            <i class="fas fa-inbox text-5xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">Tidak ada unit dengan status "{{ $statusLabel }}"</p>
        </div>
    @endif

    <div class="mt-6">
        <a href="{{ $backUrl }}" class="inline-flex items-center h-10 text-indigo-600 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>
</div>
@endsection
