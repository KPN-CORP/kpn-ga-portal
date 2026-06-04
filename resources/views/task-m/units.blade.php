@extends('layouts.app-sidebar')

@section('title', "Daftar Unit - $statusLabel")

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">
            📋 Daftar Unit <span class="text-indigo-600">{{ $statusLabel }}</span>
        </h1>
        <p class="text-sm text-gray-500">
            @if(isset($user) && $user->id !== auth()->id())
                Milik: {{ $user->name }}
            @else
                Milik Anda
            @endif
            @if($year)
                <span class="ml-2 px-2 py-1 bg-gray-100 rounded-full text-xs">Tahun: {{ $year }}</span>
            @endif
        </p>
    </div>

    @if($units->count())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dibuat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Selesai/Dibatalkan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($units as $unit)
                        @php
                            $createdDate = $unit->created_at ? $unit->created_at->format('d/m/Y H:i') : '-';
                            $finalDate = ($unit->status == 'done' || $unit->status == 'cancelled')
                                ? ($unit->updated_at ? $unit->updated_at->format('d/m/Y H:i') : '-')
                                : '-';
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('task-m.show', $unit->task_monitor_id) }}" class="text-indigo-600 hover:underline">
                                    {{ $unit->taskMonitor->title }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-800">{{ $unit->description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($unit->status == 'pending')
                                    <span class="inline-block text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-700">Proses</span>
                                @elseif($unit->status == 'done')
                                    <span class="inline-block text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">Selesai</span>
                                @else
                                    <span class="inline-block text-xs px-2 py-1 rounded-full bg-red-100 text-red-700">Dibatalkan</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $createdDate }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $finalDate }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('task-m.show', $unit->task_monitor_id) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                    Lihat Project <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-16 bg-gray-50 rounded-2xl">
            <i class="fas fa-inbox text-5xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">Tidak ada unit dengan status "{{ $statusLabel }}"</p>
        </div>
    @endif

    <div class="mt-6">
        <a href="javascript:history.back()" class="text-indigo-600 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>
</div>
@endsection