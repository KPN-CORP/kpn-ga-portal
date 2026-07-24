@extends('layouts.hsrm-app')

@section('title', 'Equipment Detail')
@section('page-title', 'Equipment Detail')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow-sm border">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">{{ $equipment->name }}</h2>
        <span class="status-badge 
            @if($equipment->status_verif == 'pending') status-pending
            @elseif($equipment->status_verif == 'verified') status-verified
            @else status-revision @endif">
            {{ ucfirst($equipment->status_verif) }}
        </span>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="text-sm text-gray-500">Type</label>
            <p class="font-medium">{{ $equipment->equipmentType->name ?? '-' }}</p>
        </div>
        <div>
            <label class="text-sm text-gray-500">Capacity</label>
            <p class="font-medium">{{ $equipment->capacity }}</p>
        </div>
        <div>
            <label class="text-sm text-gray-500">Total Items</label>
            <p class="font-medium">{{ $equipment->total_items ?? 1 }}</p>
        </div>
        <div>
            <label class="text-sm text-gray-500">Location</label>
            <p class="font-medium">{{ $equipment->location ?? '-' }}</p>
        </div>
        <div>
            <label class="text-sm text-gray-500">Area</label>
            <p class="font-medium">{{ $equipment->area->nama_area ?? '-' }}</p>
        </div>
        <div>
            <label class="text-sm text-gray-500">Business Unit</label>
            <p class="font-medium">{{ $equipment->businessUnit->nama_bisnis_unit ?? '-' }}</p>
        </div>
        <div>
            <label class="text-sm text-gray-500">Expired Date</label>
            <p class="font-medium">{{ $equipment->expired_date->format('d M Y') }}</p>
        </div>
        <div>
            <label class="text-sm text-gray-500">Verification Status</label>
            <p class="font-medium">
                <span class="status-badge 
                    @if($equipment->status_verif == 'pending') status-pending
                    @elseif($equipment->status_verif == 'verified') status-verified
                    @else status-revision @endif">
                    {{ ucfirst($equipment->status_verif) }}
                </span>
            </p>
        </div>
        <div>
            <label class="text-sm text-gray-500">Ownership Status</label>
            <p class="font-medium">{{ $equipment->status_kepemilikan ? '✔ ' : '✘ ' }}</p>
        </div>
        <div>
            <label class="text-sm text-gray-500">Recommendation</label>
            <p class="font-medium">
                @if($equipment->rekomendasi === 'recommended')
                    <span class="text-green-600">Recommended</span>
                @elseif($equipment->rekomendasi === 'not_recommended')
                    <span class="text-red-600">Not Recommended</span>
                @elseif($equipment->rekomendasi === 'valid')
                    <span class="text-blue-600">Valid</span>
                @else
                    <span class="text-gray-400">-</span>
                @endif
            </p>
        </div>
        <div>
            <label class="text-sm text-gray-500">PIC</label>
            <p class="font-medium">{{ $equipment->pic->name ?? '-' }}</p>
        </div>
        <div class="col-span-2">
            <label class="text-sm text-gray-500">Notes</label>
            <p class="font-medium">{{ $equipment->notes ?? '-' }}</p>
        </div>

        {{-- MAIN PHOTO --}}
        <div class="col-span-2">
            <label class="text-sm text-gray-500">Photo</label>
            @php
                $mainPath = $equipment->photo_path;
                $mainExists = $mainPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($mainPath);
            @endphp
            @if($mainExists)
                <a href="{{ route('hsrm.file.download', ['type' => 'equipment', 'id' => $equipment->id]) }}" 
                   target="_blank" 
                   class="text-blue-600 hover:underline inline-flex items-center gap-1">
                    <i class="fas fa-eye"></i> View Photo ({{ basename($mainPath) }})
                </a>
            @else
                <span class="text-gray-400">-</span>
            @endif
        </div>

        {{-- OLD ATTACHMENTS --}}
        @if($equipment->old_attachments && count($equipment->old_attachments) > 0)
        <div class="col-span-2 mt-4">
            <label class="text-sm text-gray-500">Previous Versions (Archived)</label>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($equipment->old_attachments as $index => $old)
                    @php
                        $oldPath = $old['path'] ?? null;
                        $oldExists = $oldPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath);
                    @endphp
                    <li>
                        @if($oldExists)
                            <a href="{{ route('hsrm.file.download', ['type' => 'equipment', 'id' => $equipment->id, 'old_index' => $index]) }}" 
                               target="_blank" 
                               class="text-blue-600 hover:underline">
                                {{ $old['original_name'] ?? basename($oldPath) }}
                            </a>
                        @else
                            <span class="text-gray-400">{{ $old['original_name'] ?? 'File not found' }}</span>
                        @endif
                        <span class="text-xs text-gray-400">(archived: {{ $old['archived_at'] ?? '-' }})</span>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div>
            <label class="text-sm text-gray-500">Created By</label>
            <p class="font-medium">{{ $equipment->creator->name ?? '-' }}</p>
        </div>
        <div>
            <label class="text-sm text-gray-500">Approved By</label>
            <p class="font-medium">{{ $equipment->approver->name ?? '-' }}</p>
        </div>
    </div>

    <div class="mt-6 flex gap-3">
        <a href="{{ route('hsrm.equipments.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
        @if(auth()->user()->canEditInArea($equipment->area_id) || session('hsrm_role') === 'admin')
            <a href="{{ route('hsrm.equipments.edit', $equipment) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
        @endif
    </div>
</div>
@endsection