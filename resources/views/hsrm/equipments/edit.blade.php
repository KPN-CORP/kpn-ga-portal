@extends('layouts.hsrm-app')

@section('title', 'Edit Equipment')
@section('page-title', 'Edit Equipment')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl soft-shadow border soft-border">
    <form action="{{ route('hsrm.equipments.update', $equipment) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit <span class="text-red-500">*</span></label>
                <select name="business_unit_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                    <option value="">Select Business Unit</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ old('business_unit_id', $equipment->business_unit_id) == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
                @error('business_unit_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Area <span class="text-red-500">*</span></label>
                <select name="area_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                    <option value="">Select Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id_area_kerja }}" {{ old('area_id', $equipment->area_id) == $area->id_area_kerja ? 'selected' : '' }}>
                            {{ $area->nama_area }}
                        </option>
                    @endforeach
                </select>
                @error('area_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            @if($isAdmin)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PIC</label>
                <select name="pic_user_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border">
                    <option value="">Select PIC</option>
                    @foreach($pics as $pic)
                        <option value="{{ $pic->id }}" {{ old('pic_user_id', $equipment->pic_user_id) == $pic->id ? 'selected' : '' }}>
                            {{ $pic->name }}
                        </option>
                    @endforeach
                </select>
                @error('pic_user_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Equipment Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $equipment->name) }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                <select name="equipment_type_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                    <option value="">Select Type</option>
                    @foreach($equipmentTypes as $type)
                        <option value="{{ $type->id }}" {{ old('equipment_type_id', $equipment->equipment_type_id) == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                @error('equipment_type_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacity <span class="text-red-500">*</span></label>
                <input type="text" name="capacity" value="{{ old('capacity', $equipment->capacity) }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                @error('capacity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <input type="text" name="location" value="{{ old('location', $equipment->location) }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border">
                @error('location') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expired Date <span class="text-red-500">*</span></label>
                <input type="date" name="expired_date" value="{{ old('expired_date', $equipment->expired_date->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                @error('expired_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center">
                <input type="hidden" name="status_kepemilikan" value="0">
                <input type="checkbox" name="status_kepemilikan" value="1" 
                    {{ old('status_kepemilikan', $equipment->status_kepemilikan) ? 'checked' : '' }} 
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label class="ml-2 text-sm font-medium text-gray-700">Checked (tick) / Unchecked (cross)</label>
                @error('status_kepemilikan') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recommendation</label>
                <select name="rekomendasi" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border">
                    <option value="">- Select -</option>
                    <option value="1" {{ old('rekomendasi', $equipment->rekomendasi) === '1' ? 'selected' : '' }}>Recommended</option>
                    <option value="0" {{ old('rekomendasi', $equipment->rekomendasi) === '0' ? 'selected' : '' }}>Not recommended</option>
                </select>
                @error('rekomendasi') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Verification Status</label>
                <span class="inline-block px-3 py-2 w-full border rounded-lg bg-gray-50 text-gray-600">
                    <span class="status-badge 
                        @if($equipment->status_verif == 'pending') status-pending
                        @elseif($equipment->status_verif == 'verified') status-verified
                        @else status-rejected @endif">
                        {{ ucfirst($equipment->status_verif) }}
                    </span>
                </span>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border">{{ old('notes', $equipment->notes) }}</textarea>
                @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- PHOTO INPUT WITH CAMERA CAPTURE --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo (JPG/PNG, max 15MB)</label>
                @php
                    $mainPath = $equipment->photo_path;
                    $mainExists = $mainPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($mainPath);
                @endphp
                @if($mainExists)
                    <div class="mb-2 p-2 bg-gray-50 border rounded flex items-center gap-2 soft-border">
                        <span class="text-sm text-gray-600">Current: 
                            <a href="{{ route('hsrm.file.download', ['type' => 'equipment', 'id' => $equipment->id]) }}" 
                            target="_blank" 
                            class="text-blue-600 hover:underline">
                                {{ basename($mainPath) }}
                            </a>
                        </span>
                    </div>
                @endif
                <div class="flex flex-wrap gap-3">
                    <input type="file" name="photo" accept="image/*" capture="environment" 
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border">
                    <span class="text-xs text-gray-400">You can take a photo directly using your phone camera. Max file size: 15MB (will be compressed to ~1.5MB).</span>
                </div>
                @error('photo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition">
                <i class="fas fa-save mr-1"></i> Update Equipment
            </button>
            <a href="{{ route('hsrm.equipments.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium transition">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection