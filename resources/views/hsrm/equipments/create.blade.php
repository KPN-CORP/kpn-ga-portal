@extends('layouts.hsrm-app')

@section('title', 'Create Equipment')
@section('page-title', 'Add New Equipment')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl soft-shadow border soft-border">
    <form action="{{ route('hsrm.equipments.store') }}" method="POST" enctype="multipart/form-data" id="equipment-form">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Business Unit (disabled, auto-filled) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit <span class="text-red-500">*</span></label>
                <select name="business_unit_id_display" id="business_unit_id_display" class="w-full border rounded-lg px-3 py-2 bg-gray-100 soft-border" disabled>
                    <option value="">Select Business Unit</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ old('business_unit_id') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="business_unit_id" id="business_unit_id" value="{{ old('business_unit_id') }}">
                @error('business_unit_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Area dengan datalist --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Area <span class="text-red-500">*</span></label>
                <input type="text"
                       id="area_name"
                       name="area_name"
                       list="area-list"
                       value="{{ old('area_name') ?: ($areas->where('id_area_kerja', old('area_id'))->first()->nama_area ?? '') }}"
                       class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border @error('area_id') border-red-500 @enderror"
                       placeholder="Ketik nama area..."
                       autocomplete="off"
                       required>

                <input type="hidden" name="area_id" id="area_id" value="{{ old('area_id') }}">

                <datalist id="area-list">
                    @foreach($areas as $area)
                        <option value="{{ $area->nama_area }}" data-id="{{ $area->id_area_kerja }}" data-bisnis-unit="{{ $area->id_bisnis_unit }}">
                    @endforeach
                </datalist>

                <div id="area-error" class="text-red-500 text-sm mt-1 hidden">Area tidak valid. Pilih dari daftar yang tersedia.</div>
                @error('area_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                @error('area_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- PIC (Admin only) --}}
            @if($isAdmin)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PIC (Person In Charge)</label>
                <select name="pic_user_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border">
                    <option value="">Select PIC</option>
                    @foreach($pics as $pic)
                        <option value="{{ $pic->id }}" {{ old('pic_user_id') == $pic->id ? 'selected' : '' }}>
                            {{ $pic->name }}
                        </option>
                    @endforeach
                </select>
                @error('pic_user_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            @endif

            {{-- Equipment Name --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Equipment Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Equipment Type with custom option --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                <select name="equipment_type_id" id="equipment_type_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                    <option value="">Select Type</option>
                    @foreach($equipmentTypes as $type)
                        <option value="{{ $type->id }}" {{ old('equipment_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                    <option value="other" {{ old('equipment_type_id') == 'other' ? 'selected' : '' }}>Other (custom)</option>
                </select>
                @error('equipment_type_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                {{-- Input custom type --}}
                <div id="custom_type_container" class="mt-2 {{ old('equipment_type_id') == 'other' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Equipment Type Name</label>
                    <input type="text" name="custom_equipment_type" id="custom_equipment_type" 
                           value="{{ old('custom_equipment_type') }}" 
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border"
                           placeholder="Enter new equipment type name...">
                    <p class="text-xs text-gray-400 mt-1">This will be created automatically upon approval.</p>
                </div>

                {{-- Info kuota --}}
                <div id="quota-info" class="mt-1 text-sm hidden">
                    <span id="quota-text"></span>
                </div>
            </div>

            {{-- Total Items --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Items <span class="text-red-500">*</span></label>
                <input type="number" name="total_items" id="total_items" min="1" step="1"
                       value="{{ old('total_items', 1) }}"
                       class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                @error('total_items') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Capacity --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacity <span class="text-red-500">*</span></label>
                <input type="text" name="capacity" value="{{ old('capacity') }}" placeholder="e.g. 6kg, 5kg" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                @error('capacity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Location --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <input type="text" name="location" value="{{ old('location') }}" placeholder="Building/Floor/Point" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border">
                @error('location') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Expired Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expired Date <span class="text-red-500">*</span></label>
                <input type="date" name="expired_date" value="{{ old('expired_date') }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                @error('expired_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Ownership --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ownership</label>
                <div class="flex items-center">
                    <input type="hidden" name="status_kepemilikan" value="0">
                    <input type="checkbox" name="status_kepemilikan" value="1" {{ old('status_kepemilikan') ? 'checked' : '' }} class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label class="ml-2 text-sm text-gray-700">Checked (tick) / Unchecked (cross)</label>
                </div>
                @error('status_kepemilikan') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Recommendation --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recommendation</label>
                <select name="rekomendasi" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">- Select -</option>
                    <option value="recommended" {{ old('rekomendasi') == 'recommended' ? 'selected' : '' }}>Recommended</option>
                    <option value="not_recommended" {{ old('rekomendasi') == 'not_recommended' ? 'selected' : '' }}>Not Recommended</option>
                    <option value="valid" {{ old('rekomendasi') == 'valid' ? 'selected' : '' }}>Valid</option>
                </select>
                @error('rekomendasi') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Notes --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border">{{ old('notes') }}</textarea>
                @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Photo --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo (JPG/PNG, max 15MB)</label>
                <div class="flex flex-wrap gap-3">
                    <input type="file" name="photo" accept="image/*" capture="environment" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border">
                    <span class="text-xs text-gray-400">You can take a photo directly using your phone camera. Max file size: 15MB (will be compressed to ~1.5MB).</span>
                </div>
                @error('photo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition">
                <i class="fas fa-save mr-1"></i> Save Equipment
            </button>
            <a href="{{ route('hsrm.equipments.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const areaInput = document.getElementById('area_name');
        const areaIdHidden = document.getElementById('area_id');
        const datalist = document.getElementById('area-list');
        const errorDiv = document.getElementById('area-error');
        const form = document.getElementById('equipment-form');
        const eqTypeSelect = document.getElementById('equipment_type_id');
        const quotaInfo = document.getElementById('quota-info');
        const quotaText = document.getElementById('quota-text');
        const businessUnitDisplay = document.getElementById('business_unit_id_display');
        const businessUnitHidden = document.getElementById('business_unit_id');
        const customContainer = document.getElementById('custom_type_container');
        const customInput = document.getElementById('custom_equipment_type');

        // Data quota dari server
        const quotaData = @json($quotaData ?? []);

        function validateArea() {
            const typedValue = areaInput.value.trim();
            const options = datalist.options;
            let found = false;
            let foundId = null;
            let foundBu = null;

            for (let opt of options) {
                if (opt.value === typedValue) {
                    found = true;
                    foundId = opt.dataset.id;
                    foundBu = opt.dataset.bisnisUnit;
                    break;
                }
            }

            if (found && foundId) {
                areaIdHidden.value = foundId;
                areaInput.classList.remove('border-red-500');
                errorDiv.classList.add('hidden');
                
                // Auto-fill Business Unit
                if (foundBu) {
                    businessUnitDisplay.value = foundBu;
                    businessUnitHidden.value = foundBu;
                }
                
                updateQuotaInfo();
                return true;
            } else {
                if (typedValue === '') {
                    areaIdHidden.value = '';
                    areaInput.classList.remove('border-red-500');
                    errorDiv.classList.add('hidden');
                    quotaInfo.classList.add('hidden');
                    return true;
                }
                areaIdHidden.value = '';
                areaInput.classList.add('border-red-500');
                errorDiv.classList.remove('hidden');
                quotaInfo.classList.add('hidden');
                return false;
            }
        }

        function updateQuotaInfo() {
            const areaId = areaIdHidden.value;
            const typeId = eqTypeSelect.value;
            if (!areaId || !typeId || typeId === 'other') {
                quotaInfo.classList.add('hidden');
                return;
            }
            const key = areaId + '_' + typeId;
            const quota = quotaData[key] || 0;
            if (quota > 0) {
                quotaText.textContent = 'Kuota total item: ' + quota + ' (maksimal ' + quota + ' item aktif)';
                quotaInfo.classList.remove('hidden');
                quotaInfo.className = 'mt-1 text-sm text-blue-600';
            } else {
                quotaText.textContent = 'Tidak ada batasan kuota';
                quotaInfo.classList.remove('hidden');
                quotaInfo.className = 'mt-1 text-sm text-gray-500';
            }
        }

        // Toggle custom input
        eqTypeSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                customContainer.classList.remove('hidden');
                customInput.setAttribute('required', 'required');
                quotaInfo.classList.add('hidden');
            } else {
                customContainer.classList.add('hidden');
                customInput.removeAttribute('required');
                customInput.value = '';
                updateQuotaInfo();
            }
        });

        areaInput.addEventListener('input', validateArea);
        areaInput.addEventListener('blur', validateArea);
        areaInput.addEventListener('change', validateArea);

        form.addEventListener('submit', function(e) {
            if (!validateArea()) {
                e.preventDefault();
                areaInput.focus();
                alert('Silakan pilih area dari daftar yang tersedia.');
            }

            // Jika custom type, cek apakah sudah diisi
            if (eqTypeSelect.value === 'other' && !customInput.value.trim()) {
                e.preventDefault();
                customInput.focus();
                alert('Silakan isi nama tipe peralatan baru.');
            }
        });

        // Initial call
        setTimeout(validateArea, 100);
    });
</script>
@endsection