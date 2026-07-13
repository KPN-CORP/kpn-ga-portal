@extends('layouts.hsrm-app')

@section('title', 'Create Certificate')
@section('page-title', 'Create New Certificate')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow-sm border">
    <form action="{{ route('hsrm.certificates.store') }}" method="POST" enctype="multipart/form-data" id="certificate-form">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Business Unit (disabled / terkunci) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit <span class="text-red-500">*</span></label>
                <select id="business_unit_id_display" class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-gray-600" disabled>
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
                       class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('area_id') border-red-500 @enderror"
                       placeholder="Ketik nama area..."
                       autocomplete="off"
                       required>

                <input type="hidden" name="area_id" id="area_id" value="{{ old('area_id') }}">

                <datalist id="area-list">
                    @foreach($areas as $area)
                        <option value="{{ $area->nama_area }}" 
                                data-id="{{ $area->id_area_kerja }}" 
                                data-bisnis-unit="{{ $area->id_bisnis_unit }}">
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
                <select name="pic_user_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
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

            {{-- Employee Name --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Employee Name / Company <span class="text-red-500">*</span></label>
                <input type="text" name="employee_name" value="{{ old('employee_name') }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                @error('employee_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Certificate Number (NIK) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Certificate Number <span class="text-red-500">*</span></label>
                <input type="text" name="nik" value="{{ old('nik') }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                @error('nik') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Certificate Type with custom option --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Certificate Type <span class="text-red-500">*</span></label>
                <select name="certificate_type_id" id="certificate_type_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select Type</option>
                    @foreach($certificateTypes as $type)
                        <option value="{{ $type->id }}" {{ old('certificate_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                    <option value="other" {{ old('certificate_type_id') == 'other' ? 'selected' : '' }}>Other (custom)</option>
                </select>
                @error('certificate_type_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                {{-- Input custom type (muncul jika pilih "other") --}}
                <div id="custom_type_container" class="mt-2 {{ old('certificate_type_id') == 'other' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Certificate Type Name</label>
                    <input type="text" name="custom_certificate_type" id="custom_certificate_type" 
                           value="{{ old('custom_certificate_type') }}" 
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                           placeholder="Enter new certificate type name...">
                    <p class="text-xs text-gray-400 mt-1">This will be created automatically upon approval.</p>
                    @error('custom_certificate_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                {{-- Info kuota --}}
                <div id="quota-info" class="mt-1 text-sm hidden">
                    <span id="quota-text"></span>
                </div>
            </div>

            {{-- Issuing Authority --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Issuing Authority</label>
                <input type="text" name="instansi_pengurusan" value="{{ old('instansi_pengurusan') }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('instansi_pengurusan') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Expiry Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date <span class="text-red-500">*</span></label>
                <input type="date" name="expired_date" value="{{ old('expired_date') }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                @error('expired_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Ownership Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ownership</label>
                <div class="flex items-center">
                    <input type="hidden" name="status_kepemilikan" value="0">
                    <input type="checkbox"
                        name="status_kepemilikan"
                        value="1"
                        {{ old('status_kepemilikan') ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
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
                <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Attachment --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (PDF/JPG/PNG, max 5MB)</label>
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @error('attachment') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">Save Certificate</button>
            <a href="{{ route('hsrm.certificates.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium">Cancel</a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const areaInput = document.getElementById('area_name');
        const areaIdHidden = document.getElementById('area_id');
        const datalist = document.getElementById('area-list');
        const errorDiv = document.getElementById('area-error');
        const form = document.getElementById('certificate-form');
        const certTypeSelect = document.getElementById('certificate_type_id');
        const customContainer = document.getElementById('custom_type_container');
        const customInput = document.getElementById('custom_certificate_type');
        const quotaInfo = document.getElementById('quota-info');
        const quotaText = document.getElementById('quota-text');
        const businessUnitSelect = document.getElementById('business_unit_id');
        const businessUnitDisplay = document.getElementById('business_unit_id_display');

        // Data quota dari server
        const quotaData = @json($quotaData ?? []);

        // Fungsi untuk mengisi Business Unit
        function setBusinessUnit(buId) {
            if (buId) {
                businessUnitSelect.value = buId;
                // Set display select juga
                const displayOptions = businessUnitDisplay.options;
                for (let opt of displayOptions) {
                    if (opt.value == buId) {
                        businessUnitDisplay.value = buId;
                        break;
                    }
                }
            }
        }

        // Fungsi validasi area
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
                    setBusinessUnit(foundBu);
                }
                
                updateQuotaInfo();
                return true;
            } else {
                if (typedValue === '') {
                    areaIdHidden.value = '';
                    areaInput.classList.remove('border-red-500');
                    errorDiv.classList.add('hidden');
                    quotaInfo.classList.add('hidden');
                    // Kosongkan business unit
                    businessUnitSelect.value = '';
                    businessUnitDisplay.value = '';
                    return true;
                }
                areaIdHidden.value = '';
                areaInput.classList.add('border-red-500');
                errorDiv.classList.remove('hidden');
                quotaInfo.classList.add('hidden');
                return false;
            }
        }

        // Fungsi update quota info
        function updateQuotaInfo() {
            const areaId = areaIdHidden.value;
            const typeId = certTypeSelect.value;
            if (!areaId || !typeId || typeId === 'other') {
                quotaInfo.classList.add('hidden');
                return;
            }
            const key = areaId + '_' + typeId;
            const quota = quotaData[key] || 0;
            if (quota > 0) {
                quotaText.textContent = 'Kuota: ' + quota + ' (maksimal ' + quota + ' sertifikat aktif)';
                quotaInfo.classList.remove('hidden');
                quotaInfo.className = 'mt-1 text-sm text-blue-600';
            } else {
                quotaText.textContent = 'Tidak ada batasan kuota';
                quotaInfo.classList.remove('hidden');
                quotaInfo.className = 'mt-1 text-sm text-gray-500';
            }
        }

        // Event: area input
        areaInput.addEventListener('input', validateArea);
        areaInput.addEventListener('blur', validateArea);
        areaInput.addEventListener('change', validateArea);

        // Event: certificate type change
        certTypeSelect.addEventListener('change', function() {
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

        // Event: form submit
        form.addEventListener('submit', function(e) {
            if (!validateArea()) {
                e.preventDefault();
                areaInput.focus();
                alert('Silakan pilih area dari daftar yang tersedia.');
                return;
            }

            // Jika custom type dipilih, pastikan diisi
            if (certTypeSelect.value === 'other') {
                const customVal = customInput.value.trim();
                if (!customVal) {
                    e.preventDefault();
                    customInput.focus();
                    alert('Silakan isi nama tipe sertifikat baru.');
                    return;
                }
            }
        });

        // Inisialisasi: jika sudah ada value old, set business unit
        const initialAreaId = areaIdHidden.value;
        if (initialAreaId) {
            // Cari option di datalist yang sesuai
            const options = datalist.options;
            for (let opt of options) {
                if (opt.dataset.id == initialAreaId) {
                    const bu = opt.dataset.bisnisUnit;
                    if (bu) setBusinessUnit(bu);
                    break;
                }
            }
        }

        // Initial quota info
        setTimeout(validateArea, 100);
    });
</script>
@endsection