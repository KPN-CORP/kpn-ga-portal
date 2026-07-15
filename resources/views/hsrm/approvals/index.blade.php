@extends('layouts.hsrm-app')

@section('title', 'Pending Approvals')
@section('page-title', 'Approval Requests')

@section('content')
<div class="space-y-6">
    <!-- Certificates Pending -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold">📄 Certificates Pending</h3>
            <span class="text-sm text-gray-500">{{ $certificates->count() }} pending</span>
        </div>
        <div class="bg-white rounded-lg shadow-sm border overflow-x-auto">
            <!-- Table for desktop/tablet -->
            <div class="hidden md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="p-3 text-left">Employee</th>
                            <th class="p-3 text-left">Certificate Number</th>
                            <th class="p-3 text-left">Type</th>
                            <th class="p-3 text-left">Area</th>
                            <th class="p-3 text-left">Recommendation</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($certificates as $cert)
                        <tr class="border-t hover:bg-gray-50 transition">
                            <td class="p-3">{{ $cert->employee_name }}</td>
                            <td class="p-3 font-mono text-xs">{{ $cert->nik }}</td>
                            <td class="p-3">{{ $cert->certificateType->name ?? '-' }}</td>
                            <td class="p-3">{{ $cert->area->nama_area ?? '-' }}</td>
                            <td class="p-3">
                                @if($cert->rekomendasi === 'recommended')
                                    <span class="text-green-600">Recommended</span>
                                @elseif($cert->rekomendasi === 'not_recommended')
                                    <span class="text-red-600">Not Recommended</span>
                                @elseif($cert->rekomendasi === 'valid')
                                    <span class="text-blue-600">Valid</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <span class="status-badge status-pending">Pending</span>
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <a href="{{ route('hsrm.certificates.show', $cert) }}" 
                                       class="text-gray-600 hover:text-gray-800 text-sm" 
                                       title="View Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form action="{{ route('hsrm.certificates.approve', $cert) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">Verif</button>
                                    </form>
                                    <form action="{{ route('hsrm.certificates.reject', $cert) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">Revisi</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">No pending certificates.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Card view for mobile -->
            <div class="md:hidden space-y-3 p-3">
                @forelse($certificates as $cert)
                <div class="bg-white border rounded-lg p-4 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-semibold">{{ $cert->employee_name }}</div>
                            <div class="text-sm text-gray-500 font-mono">Certificate #: {{ $cert->nik }}</div>
                            <div class="text-sm text-gray-500">{{ $cert->certificateType->name ?? '-' }}</div>
                            <div class="text-sm text-gray-500">Area: {{ $cert->area->nama_area ?? '-' }}</div>
                            <div class="text-sm text-gray-500">
                                Recommendation: 
                                @if($cert->rekomendasi === 'recommended')
                                    <span class="text-green-600">Recommended</span>
                                @elseif($cert->rekomendasi === 'not_recommended')
                                    <span class="text-red-600">Not Recommended</span>
                                @elseif($cert->rekomendasi === 'valid')
                                    <span class="text-blue-600">Valid</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                        </div>
                        <span class="status-badge status-pending">Pending</span>
                    </div>
                    <div class="flex justify-end gap-2 mt-3 pt-2 border-t">
                        <a href="{{ route('hsrm.certificates.show', $cert) }}" 
                           class="text-gray-600 hover:text-gray-800 text-sm">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <form action="{{ route('hsrm.certificates.approve', $cert) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">Verif</button>
                        </form>
                        <form action="{{ route('hsrm.certificates.reject', $cert) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">Revisi</button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="text-center text-gray-500 p-4">No pending certificates.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Equipments Pending -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold">🔧 Equipments Pending</h3>
            <span class="text-sm text-gray-500">{{ $equipments->count() }} pending</span>
        </div>
        <div class="bg-white rounded-lg shadow-sm border overflow-x-auto">
            <!-- Table for desktop/tablet -->
            <div class="hidden md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="p-3 text-left">Name</th>
                            <th class="p-3 text-left">Type</th>
                            <th class="p-3 text-left">Area</th>
                            <th class="p-3 text-left">Recommendation</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($equipments as $eq)
                        <tr class="border-t hover:bg-gray-50 transition">
                            <td class="p-3">{{ $eq->name }}</td>
                            <td class="p-3">{{ $eq->equipmentType->name ?? '-' }}</td>
                            <td class="p-3">{{ $eq->area->nama_area ?? '-' }}</td>
                            <td class="p-3">
                                @if($eq->rekomendasi === 'recommended')
                                    <span class="text-green-600">Recommended</span>
                                @elseif($eq->rekomendasi === 'not_recommended')
                                    <span class="text-red-600">Not Recommended</span>
                                @elseif($eq->rekomendasi === 'valid')
                                    <span class="text-blue-600">Valid</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <span class="status-badge status-pending">Pending</span>
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <a href="{{ route('hsrm.equipments.show', $eq) }}" 
                                       class="text-gray-600 hover:text-gray-800 text-sm" 
                                       title="View Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form action="{{ route('hsrm.equipments.approve', $eq) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">Verif</button>
                                    </form>
                                    <form action="{{ route('hsrm.equipments.reject', $eq) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">Revisi</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-4 text-center text-gray-500">No pending equipments.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Card view for mobile -->
            <div class="md:hidden space-y-3 p-3">
                @forelse($equipments as $eq)
                <div class="bg-white border rounded-lg p-4 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-semibold">{{ $eq->name }}</div>
                            <div class="text-sm text-gray-500">{{ $eq->equipmentType->name ?? '-' }}</div>
                            <div class="text-sm text-gray-500">Area: {{ $eq->area->nama_area ?? '-' }}</div>
                            <div class="text-sm text-gray-500">
                                Recommendation: 
                                @if($eq->rekomendasi === 'recommended')
                                    <span class="text-green-600">Recommended</span>
                                @elseif($eq->rekomendasi === 'not_recommended')
                                    <span class="text-red-600">Not Recommended</span>
                                @elseif($eq->rekomendasi === 'valid')
                                    <span class="text-blue-600">Valid</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                        </div>
                        <span class="status-badge status-pending">Pending</span>
                    </div>
                    <div class="flex justify-end gap-2 mt-3 pt-2 border-t">
                        <a href="{{ route('hsrm.equipments.show', $eq) }}" 
                           class="text-gray-600 hover:text-gray-800 text-sm">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <form action="{{ route('hsrm.equipments.approve', $eq) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">Verif</button>
                        </form>
                        <form action="{{ route('hsrm.equipments.reject', $eq) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">Revisi</button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="text-center text-gray-500 p-4">No pending equipments.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection