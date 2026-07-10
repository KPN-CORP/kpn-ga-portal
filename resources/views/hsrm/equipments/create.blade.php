@extends('layouts.hsrm-app')

@section('title', 'Create Equipment')
@section('page-title', 'Add New Equipment')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl soft-shadow border soft-border">
    <form action="{{ route('hsrm.equipments.store') }}" method="POST" enctype="multipart/form-data" id="equipment-form">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Business Unit --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit <span class="text-red-500">*</span></label>
                <select name="business_unit_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                    <option value="">Select Business Unit</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ old('business_unit_id') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
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
                        <option value="{{ $area->nama_area }}" data-id="{{ $area->id_area_kerja }}">
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

            {{-- Equipment Type with quota info --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                <select name="equipment_type_id" id="equipment_type_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 soft-border" required>
                    <option value="">Select Type</option>
                    @foreach($equipmentTypes as $type)
                        <option value="{{ $type->id }}" {{ old('equipment_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                @error('equipment_type_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                {{-- Info kuota --}}
                <div id="quota-info" class="mt-1 text-sm hidden">
                    <span id="quota-text"></span>
                </div>
            </div>

            {{-- Total Items (HIDDEN, always 1) --}}
            <input type="hidden" name="total_items" id="total_items" value="1">

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
                    <option value="1" {{ old('rekomendasi') == '1' ? 'selected' : '' }}>Recommended</option>
                    <option value="0" {{ old('rekomendasi') == '0' ? 'selected' : '' }}>Not Recommended</option>
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

        // Data quota dari server (dikirim dari controller)
        const quotaData = @json($quotaData ?? []);

        function validateArea() {
            const typedValue = areaInput.value.trim();
            const options = datalist.options;
            let found = false;
            let foundId = null;
            for (let opt of options) {
                if (opt.value === typedValue) {
                    found = true;
                    foundId = opt.dataset.id;
                    break;
                }
            }
            if (found && foundId) {
                areaIdHidden.value = foundId;
                areaInput.classList.remove('border-red-500');
                errorDiv.classList.add('hidden');
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
            if (!areaId || !typeId) {
                quotaInfo.classList.add('hidden');
                return;
            }
            const key = areaId + '_' + typeId;
            const quota = quotaData[key] || 0;
            if (quota > 0) {
                // Hitung active items saat ini (dari server, kirim via AJAX atau data awal)
                // Karena kita tidak punya data active di sini, kita bisa tampilkan quota saja.
                quotaText.textContent = 'Kuota total item: ' + quota + ' (maksimal ' + quota + ' item aktif)';
                quotaInfo.classList.remove('hidden');
                quotaInfo.className = 'mt-1 text-sm text-blue-600';
            } else {
                quotaText.textContent = 'Tidak ada batasan kuota';
                quotaInfo.classList.remove('hidden');
                quotaInfo.className = 'mt-1 text-sm text-gray-500';
            }
        }

        areaInput.addEventListener('input', validateArea);
        areaInput.addEventListener('blur', validateArea);
        eqTypeSelect.addEventListener('change', updateQuotaInfo);

        form.addEventListener('submit', function(e) {
            if (!validateArea()) {
                e.preventDefault();
                areaInput.focus();
                alert('Silakan pilih area dari daftar yang tersedia.');
            }

            // Validasi kuota sisi client (opsional, server tetap final)
            const areaId = areaIdHidden.value;
            const typeId = eqTypeSelect.value;
            const key = areaId + '_' + typeId;
            const quota = quotaData[key] || 0;
            if (quota > 0) {
                // Kita tidak tahu active items dari client, jadi kita skip client-side check,
                // cukup tampilkan peringatan jika sudah mendekati quota.
                // Server akan menolak jika melebihi.
                // Bisa tambahkan AJAX call untuk cek realtime, tapi untuk sederhana kita skip.
            }
        });

        // Initial call
        setTimeout(validateArea, 100);
    });
</script>
@endsection