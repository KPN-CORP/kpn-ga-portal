@extends('layouts.hsrm-app')

@section('title', 'Create Certificate')
@section('page-title', 'Create New Certificate')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow-sm border">
    <form action="{{ route('hsrm.certificates.store') }}" method="POST" enctype="multipart/form-data" id="certificate-form">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Business Unit --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit <span class="text-red-500">*</span></label>
                <select name="business_unit_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select Business Unit</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ old('business_unit_id') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
                @error('business_unit_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Area dengan datalist + validasi ketat --}}
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

            {{-- Certificate Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Certificate Type <span class="text-red-500">*</span></label>
                <select name="certificate_type_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select Type</option>
                    @foreach($certificateTypes as $type)
                        <option value="{{ $type->id }}" {{ old('certificate_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                @error('certificate_type_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
                    <option value="1" {{ old('rekomendasi') == '1' ? 'selected' : '' }}>Recommended</option>
                    <option value="0" {{ old('rekomendasi') == '0' ? 'selected' : '' }}>Not recommended</option>
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

        // Fungsi untuk memeriksa validitas area
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
                return true;
            } else {
                // Jika input kosong, kita anggap valid (karena tidak wajib diisi? tapi di sini required)
                if (typedValue === '') {
                    areaIdHidden.value = '';
                    areaInput.classList.remove('border-red-500');
                    errorDiv.classList.add('hidden');
                    return true; // kosong dianggap valid (akan dicek required)
                }
                // Jika tidak kosong dan tidak ditemukan, invalid
                areaIdHidden.value = '';
                areaInput.classList.add('border-red-500');
                errorDiv.classList.remove('hidden');
                return false;
            }
        }

        // Validasi setiap kali input berubah
        areaInput.addEventListener('input', validateArea);

        // Saat form disubmit, validasi ulang dan cegah jika tidak valid
        form.addEventListener('submit', function(e) {
            if (!validateArea()) {
                e.preventDefault();
                areaInput.focus();
                alert('Silakan pilih area dari daftar yang tersedia.');
            }
        });

        // Jika pengguna memilih dari datalist dengan mouse, event input sudah cukup.
        // Tapi untuk keamanan, kita panggil validateArea juga saat blur
        areaInput.addEventListener('blur', validateArea);
    });
</script>
@endsection