@extends('layouts.hsrm-app')

@section('title', 'Equipments')
@section('page-title', 'Equipments Management')

@section('content')
<div class="flex flex-wrap justify-between items-center mb-4 gap-2">
    <div class="flex gap-2">
        @if(auth()->user()->hsrmAreas->isNotEmpty() || session('hsrm_role') === 'admin')
        <a href="{{ route('hsrm.equipments.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
            <i class="fas fa-plus mr-1"></i> Add Equipment
        </a>
        @endif
        <a href="{{ route('hsrm.equipments.export', request()->all()) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition">
            <i class="fas fa-file-excel mr-1"></i> Export Excel
        </a>
    </div>

    <form method="GET" action="{{ route('hsrm.equipments.index') }}" class="flex flex-wrap gap-2 items-center w-full md:w-auto">
        <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 flex-1 min-w-[120px]">
        <select name="status_verif" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">All Status</option>
            <option value="pending" {{ request('status_verif') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="verified" {{ request('status_verif') == 'verified' ? 'selected' : '' }}>Verified</option>
            <option value="revision" {{ request('status_verif') == 'revision' ? 'selected' : '' }}>Revision</option>
        </select>
        <select name="area_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">All Areas</option>
            @foreach($areas as $area)
                <option value="{{ $area->id_area_kerja }}" {{ request('area_id') == $area->id_area_kerja ? 'selected' : '' }}>
                    {{ $area->nama_area }}
                </option>
            @endforeach
        </select>

        {{-- 🔽 FILTER TYPE --}}
        <select name="equipment_type_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">All Types</option>
            @foreach($equipmentTypes as $type)
                <option value="{{ $type->id }}" {{ request('equipment_type_id') == $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>

        <input type="date" name="expired_from" value="{{ request('expired_from') }}" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Expired From">
        <input type="date" name="expired_to" value="{{ request('expired_to') }}" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Expired To">
        <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">Filter</button>
        <a href="{{ route('hsrm.equipments.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Clear</a>
    </form>
</div>

{{-- TABEL VIEW (Desktop & Tablet) --}}
<div class="hidden md:block bg-white rounded-xl soft-shadow border soft-border overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 text-left border-b soft-border">
                <th class="p-3 font-semibold text-gray-600">Area</th>
                <th class="p-3 font-semibold text-gray-600">Name</th>
                <th class="p-3 font-semibold text-gray-600">Type</th>
                <th class="p-3 font-semibold text-gray-600">Capacity</th>
                <th class="p-3 font-semibold text-gray-600">Total Items</th>
                <th class="p-3 font-semibold text-gray-600">Expired</th>
                <th class="p-3 font-semibold text-gray-600">Verification</th>
                <th class="p-3 font-semibold text-gray-600">Ownership</th>
                <th class="p-3 font-semibold text-gray-600">Recommendation</th>
                <th class="p-3 font-semibold text-gray-600 text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($equipments as $eq)
            <tr class="border-b soft-border hover:bg-gray-50 transition">
                <td class="p-3">{{ $eq->area->nama_area ?? '-' }}</td>
                <td class="p-3 font-medium">{{ $eq->name }}</td>
                <td class="p-3">{{ $eq->equipmentType->name ?? '-' }}</td>
                <td class="p-3">{{ $eq->capacity }}</td>
                <td class="p-3">{{ $eq->total_items ?? 1 }}</td>
                <td class="p-3">{{ $eq->expired_date->format('d M Y') }}</td>
                <td class="p-3">
                    <span class="status-badge 
                        @if($eq->status_verif == 'pending') status-pending
                        @elseif($eq->status_verif == 'verified') status-verified
                        @else status-revision @endif">
                        {{ ucfirst($eq->status_verif) }}
                    </span>
                </td>
                <td class="p-3 text-center">{{ $eq->status_kepemilikan ? '✔' : '✘' }}</td>
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
                <td class="p-3 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('hsrm.equipments.show', $eq) }}" class="text-gray-600 hover:text-gray-800" title="Detail">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if(auth()->user()->canEditInArea($eq->area_id) || session('hsrm_role') === 'admin')
                        <a href="{{ route('hsrm.equipments.edit', $eq) }}" class="text-blue-600 hover:text-blue-800" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="p-6 text-center text-gray-500">No equipments found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- CARD VIEW (Mobile) --}}
<div class="md:hidden space-y-4">
    @forelse($equipments as $eq)
    <div class="bg-white rounded-xl soft-shadow border soft-border p-4">
        <div class="flex justify-between items-start mb-2">
            <div>
                <h4 class="font-semibold text-gray-800 text-lg">{{ $eq->name }}</h4>
                <span class="text-sm text-gray-500">{{ $eq->equipmentType->name ?? '-' }}</span>
            </div>
            <span class="status-badge 
                @if($eq->status_verif == 'pending') status-pending
                @elseif($eq->status_verif == 'verified') status-verified
                @else status-revision @endif">
                {{ ucfirst($eq->status_verif) }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-1 text-sm mt-2">
            <div class="col-span-2">
                <span class="text-gray-500">Area:</span>
                <span class="font-medium">{{ $eq->area->nama_area ?? '-' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Capacity:</span>
                <span class="font-medium">{{ $eq->capacity }}</span>
            </div>
            <div>
                <span class="text-gray-500">Total Items:</span>
                <span class="font-medium">{{ $eq->total_items ?? 1 }}</span>
            </div>
            <div>
                <span class="text-gray-500">Expired:</span>
                <span class="font-medium">{{ $eq->expired_date->format('d M Y') }}</span>
            </div>
            <div>
                <span class="text-gray-500">Ownership:</span>
                <span class="font-medium">{{ $eq->status_kepemilikan ? '✔' : '✘' }}</span>
            </div>
            <div class="col-span-2">
                <span class="text-gray-500">Recommendation:</span>
                <span class="font-medium">
                    @if($eq->rekomendasi === 'recommended')
                        <span class="text-green-600">Recommended</span>
                    @elseif($eq->rekomendasi === 'not_recommended')
                        <span class="text-red-600">Not Recommended</span>
                    @elseif($eq->rekomendasi === 'valid')
                        <span class="text-blue-600">Valid</span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </span>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-3 pt-2 border-t soft-border">
            <a href="{{ route('hsrm.equipments.show', $eq) }}" class="text-gray-600 hover:text-gray-800 text-sm" title="Detail">
                <i class="fas fa-eye mr-1"></i> Detail
            </a>
            @if(auth()->user()->canEditInArea($eq->area_id) || session('hsrm_role') === 'admin')
            <a href="{{ route('hsrm.equipments.edit', $eq) }}" class="text-blue-600 hover:text-blue-800 text-sm" title="Edit">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            @endif
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl soft-shadow border soft-border p-6 text-center text-gray-500">
        No equipments found.
    </div>
    @endforelse
</div>

{{-- 🔽 PAGINATION --}}
<div class="mt-6">
    {{ $equipments->appends(request()->query())->links() }}
</div>
@endsection