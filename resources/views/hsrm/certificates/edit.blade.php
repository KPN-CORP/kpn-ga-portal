@extends('layouts.hsrm-app')

@section('title', 'Edit Certificate')
@section('page-title', 'Edit Certificate')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow-sm border">
    <form action="{{ route('hsrm.certificates.update', $cert) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Business Unit (disabled / terkunci) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit <span class="text-red-500">*</span></label>
                <select id="business_unit_id_display" class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-gray-600" disabled>
                    <option value="">Select Business Unit</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ old('business_unit_id', $cert->business_unit_id) == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="business_unit_id" id="business_unit_id" value="{{ old('business_unit_id', $cert->business_unit_id) }}">
                @error('business_unit_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Area --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Area <span class="text-red-500">*</span></label>
                <select name="area_id" id="area_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id_area_kerja }}" 
                                data-bisnis-unit="{{ $area->id_bisnis_unit }}"
                                {{ old('area_id', $cert->area_id) == $area->id_area_kerja ? 'selected' : '' }}>
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

            {{-- Certificate Type with custom option --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Certificate Type <span class="text-red-500">*</span></label>
                <select name="certificate_type_id" id="certificate_type_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select Type</option>
                    @foreach($certificateTypes as $type)
                        <option value="{{ $type->id }}" 
                            {{ old('certificate_type_id', $cert->certificate_type_id) == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                    <option value="other" {{ old('certificate_type_id', $cert->certificate_type_id) == 'other' || $cert->custom_certificate_type ? 'selected' : '' }}>
                        Other (custom)
                    </option>
                </select>
                @error('certificate_type_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                {{-- Input custom type --}}
                <div id="custom_type_container" class="mt-2 {{ old('certificate_type_id', $cert->certificate_type_id) == 'other' || $cert->custom_certificate_type ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Certificate Type Name</label>
                    <input type="text" name="custom_certificate_type" id="custom_certificate_type" 
                           value="{{ old('custom_certificate_type', $cert->custom_certificate_type) }}" 
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Ownership</label>
                <div class="flex items-center">
                    <input type="hidden" name="status_kepemilikan" value="0">
                    <input type="checkbox"
                        name="status_kepemilikan"
                        value="1"
                        {{ old('status_kepemilikan', $cert->status_kepemilikan ?? 0) == 1 ? 'checked' : '' }}
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const areaSelect = document.getElementById('area_id');
        const businessUnitSelect = document.getElementById('business_unit_id');
        const businessUnitDisplay = document.getElementById('business_unit_id_display');
        const certTypeSelect = document.getElementById('certificate_type_id');
        const customContainer = document.getElementById('custom_type_container');
        const customInput = document.getElementById('custom_certificate_type');
        const quotaInfo = document.getElementById('quota-info');
        const quotaText = document.getElementById('quota-text');
        const form = document.querySelector('form');

        // Data quota dari server
        const quotaData = @json($quotaData ?? []);

        function setBusinessUnit(buId) {
            if (buId) {
                businessUnitSelect.value = buId;
                const displayOptions = businessUnitDisplay.options;
                for (let opt of displayOptions) {
                    if (opt.value == buId) {
                        businessUnitDisplay.value = buId;
                        break;
                    }
                }
            }
        }

        function autoFillBusinessUnit() {
            const selectedOption = areaSelect.options[areaSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.bisnisUnit) {
                const buId = selectedOption.dataset.bisnisUnit;
                setBusinessUnit(buId);
            }
        }

        function updateQuotaInfo() {
            const areaId = areaSelect.value;
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

        // Event: area change
        areaSelect.addEventListener('change', function() {
            autoFillBusinessUnit();
            updateQuotaInfo();
        });

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

        // Initial: set business unit berdasarkan area yang terpilih
        autoFillBusinessUnit();

        // Initial: update quota info
        setTimeout(updateQuotaInfo, 100);

        // Initial: jika ada custom_certificate_type, tampilkan container
        if (customInput.value.trim() !== '') {
            customContainer.classList.remove('hidden');
            customInput.setAttribute('required', 'required');
        }
    });
</script>
@endsection