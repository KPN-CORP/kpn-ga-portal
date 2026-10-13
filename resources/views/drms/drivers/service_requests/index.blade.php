@extends('layouts.app_car_drive_sidebar')

@section('content')
@php
    $flowLabels = ['open' => 'Menunggu Approval', 'progress' => 'Dalam Proses', 'done' => 'Selesai', 'rejected' => 'Ditolak'];
    $flowColors = ['open' => 'bg-yellow-100 text-yellow-800', 'progress' => 'bg-blue-100 text-blue-800', 'done' => 'bg-green-100 text-green-800', 'rejected' => 'bg-red-100 text-red-800'];
@endphp
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <h1 class="text-2xl font-bold">Pengajuan Servis & Perbaikan</h1>
        <a href="{{ route('drms.driver.service.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">+ Ajukan Baru</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
    @endif

    {{-- SERVIS RUTIN --}}
    <h2 class="text-lg font-semibold mb-2">Servis Rutin</h2>
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendaraan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($services as $s)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ $s->vehicle->plate_number ?? '-' }}</span>
                                <span class="text-xs text-gray-400 block">{{ $s->vehicle->type ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $s->service_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ collect($s->service_type ?? [])->map(fn($t) => ucfirst(str_replace('_', ' ', $t)))->implode(', ') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $flowColors[$s->status] ?? 'bg-gray-100 text-gray-800' }}">{{ $flowLabels[$s->status] ?? $s->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('drms.driver.service.show', ['service', $s->id]) }}" class="text-blue-600 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada pengajuan servis rutin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($services->hasPages())
            <div class="px-4 py-3 border-t">{{ $services->links() }}</div>
        @endif
    </div>

    {{-- PERBAIKAN --}}
    <h2 class="text-lg font-semibold mb-2">Perbaikan / Kerusakan</h2>
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendaraan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keluhan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($repairs as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ $r->vehicle->plate_number ?? '-' }}</span>
                                <span class="text-xs text-gray-400 block">{{ $r->vehicle->type ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $r->report_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($r->complaint, 60) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $flowColors[$r->status] ?? 'bg-gray-100 text-gray-800' }}">{{ $flowLabels[$r->status] ?? $r->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('drms.driver.service.show', ['repair', $r->id]) }}" class="text-blue-600 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada pengajuan perbaikan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($repairs->hasPages())
            <div class="px-4 py-3 border-t">{{ $repairs->links() }}</div>
        @endif
    </div>
</div>
@endsection
