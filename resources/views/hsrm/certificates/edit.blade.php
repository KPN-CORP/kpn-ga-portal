@extends('layouts.hsrm-app')

@section('title', 'Edit Certificate')
@section('page-title', 'Edit Certificate')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow-sm border">
    <form action="{{ route('hsrm.certificates.update', $cert) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Business Unit --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit <span class="text-red-500">*</span></label>
                <select name="business_unit_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select Business Unit</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ old('business_unit_id', $cert->business_unit_id) == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
                @error('business_unit_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Area --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Area <span class="text-red-500">*</span></label>
                <select name="area_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id_area_kerja }}" {{ old('area_id', $cert->area_id) == $area->id_area_kerja ? 'selected' : '' }}>
                            {{ $area->nama_area }}
                        </option>
                    @endforeach
                </select>
                @error('area_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- PIC (only for admin) --}}
            @if(session('hsrm_role') === 'admin')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PIC (Person In Charge)</label>
                <select name="pic_user_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Select PIC</option>
                    @foreach($pics as $pic)
                        <option value="{{ $pic->id }}" {{ old('pic_user_id', $cert->pic_user_id) == $pic->id ? 'selected' : '' }}>
                            {{ $pic->name }}
                        </option>
                    @endforeach
                </select>
                @error('pic_user_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            @endif

            {{-- Employee Name --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Employee Name / Company <span class="text-red-500">*</span></label>
                <input type="text" name="employee_name" value="{{ old('employee_name', $cert->employee_name) }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                @error('employee_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- NIK --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">NIK <span class="text-red-500">*</span></label>
                <input type="text" name="nik" value="{{ old('nik', $cert->nik) }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                @error('nik') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Certificate Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Certificate Type <span class="text-red-500">*</span></label>
                <select name="certificate_type_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select Type</option>
                    @foreach($certificateTypes as $type)
                        <option value="{{ $type->id }}" {{ old('certificate_type_id', $cert->certificate_type_id) == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                @error('certificate_type_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Issuing Authority --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Issuing Authority</label>
                <input type="text" name="instansi_pengurusan" value="{{ old('instansi_pengurusan', $cert->instansi_pengurusan) }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('instansi_pengurusan') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Expired Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expired Date <span class="text-red-500">*</span></label>
                <input type="date" name="expired_date" value="{{ old('expired_date', $cert->expired_date->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                @error('expired_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Ownership Status --}}
            <div>
                <!-- Judul -->
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Ownership
                </label>

                <!-- Checkbox -->
                <div class="flex items-center">
                    <input type="hidden" name="status_kepemilikan" value="0">

                    <input type="checkbox"
                        name="status_kepemilikan"
                        value="1"
                        {{ old('status_kepemilikan', $cert->status_kepemilikan ?? 0) == 1 ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">

                    <label class="ml-2 text-sm text-gray-700">
                        Checked (tick) / Unchecked (cross)
                    </label>
                </div>

                @error('status_kepemilikan')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            {{-- Recommendation --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recommendation</label>
                <select name="rekomendasi" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">- Select -</option>
                    <option value="recommended" {{ old('rekomendasi', $cert->rekomendasi) == 'recommended' ? 'selected' : '' }}>Recommended</option>
                    <option value="not_recommended" {{ old('rekomendasi', $cert->rekomendasi) == 'not_recommended' ? 'selected' : '' }}>Not Recommended</option>
                    <option value="valid" {{ old('rekomendasi', $cert->rekomendasi) == 'valid' ? 'selected' : '' }}>Valid</option>
                </select>
                @error('rekomendasi') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Verification Status (read-only) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Verification Status</label>
                <span class="inline-block px-3 py-2 w-full border rounded-lg bg-gray-50 text-gray-600">
                    <span class="status-badge 
                        @if($cert->status_verif == 'pending') status-pending
                        @elseif($cert->status_verif == 'verified') status-verified
                        @else status-rejected @endif">
                        {{ ucfirst($cert->status_verif) }}
                    </span>
                </span>
            </div>

            {{-- Notes --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">{{ old('notes', $cert->notes) }}</textarea>
                @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Attachment --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (PDF/JPG/PNG, max 5MB)</label>
                @php
                    $mainPath = $cert->attachment_path;
                    $mainExists = $mainPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($mainPath);
                @endphp
                @if($mainExists)
                    <div class="mb-2 p-2 bg-gray-50 border rounded flex items-center gap-2">
                        <span class="text-sm text-gray-600">Current: 
                            <a href="{{ route('hsrm.file.download', ['type' => 'certificate', 'id' => $cert->id]) }}" 
                               target="_blank" 
                               class="text-blue-600 hover:underline">
                                {{ basename($mainPath) }}
                            </a>
                        </span>
                    </div>
                @endif
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('attachment') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                Update Certificate
            </button>
            <a href="{{ route('hsrm.certificates.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection