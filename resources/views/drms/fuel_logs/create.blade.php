@extends('layouts.app_car_sidebar')

@section('content')
{{-- Tampilan khusus mobile --}}
<div class="block md:hidden">
    <div class="container mx-auto max-w-2xl">
        <h1 class="text-2xl font-bold mb-4">Tambah Log</h1>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('drms.fuel-logs.store') }}"
              method="POST"
              enctype="multipart/form-data"
              class="bg-white p-6 rounded shadow">

            @csrf

            {{-- Kendaraan --}}
            <div class="mb-4 relative">
                <label class="block text-sm font-medium text-gray-700">
                    Kendaraan <span class="text-red-500">*</span>
                </label>

                @php
                    $oldVehicle = old('vehicle_id')
                        ? $vehicles->firstWhere('id', (int) old('vehicle_id'))
                        : null;

                    $oldVehicleLabel = $oldVehicle
                        ? $oldVehicle->plate_number . ' - ' .
                          $oldVehicle->type . ' (' .
                          ($oldVehicle->fuel_type ?: 'Bensin') . ')'
                        : '';
                @endphp

                <input type="text"
                       id="vehicle_search"
                       autocomplete="off"
                       placeholder="Ketik plat nomor / tipe kendaraan..."
                       class="w-full border rounded px-3 py-2"
                       value="{{ $oldVehicleLabel }}"
                       required>

                <input type="hidden"
                       name="vehicle_id"
                       id="vehicle_id"
                       value="{{ old('vehicle_id') }}">

                <div id="vehicle_suggestions"
                     class="hidden absolute z-20 mt-1 w-full bg-white border rounded shadow-lg max-h-60 overflow-y-auto">
                </div>

                <p class="text-xs text-gray-400 mt-1">
                    Ketik untuk mencari kendaraan yang tersedia, lalu pilih dari saran yang muncul.
                </p>
            </div>

            {{-- Driver --}}
            <input type="hidden"
                   name="driver_id"
                   value="{{ $driver->id ?? '' }}">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">
                    Driver
                </label>

                <input type="text"
                       value="{{ $driver->name ?? 'Tidak ada driver' }}"
                       disabled
                       class="w-full border rounded px-3 py-2 bg-gray-100">

                <p class="text-xs text-gray-400 mt-1">
                    Driver diambil dari user yang login
                </p>
            </div>

            {{-- Tanggal --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">
                    Tanggal Pengisian <span class="text-red-500">*</span>
                </label>

                <input type="date"
                       name="filling_date"
                       value="{{ old('filling_date', date('Y-m-d')) }}"
                       class="w-full border rounded px-3 py-2"
                       required>
            </div>

            {{-- Odometer --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">
                    Odometer Saat Ini (km) <span class="text-red-500">*</span>
                </label>

                <input type="number"
                       name="odometer_start"
                       value="{{ old('odometer_start') }}"
                       class="w-full border rounded px-3 py-2"
                       min="0"
                       required>
            </div>

            {{-- Fuel --}}
            <div class="grid grid-cols-2 gap-4">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700"
                           id="fuel_unit_label">
                        Liter <span class="text-red-500">*</span>
                    </label>

                    <input type="number"
                           name="fuel_liters"
                           id="fuel_liters"
                           value="{{ old('fuel_liters') }}"
                           class="w-full border rounded px-3 py-2"
                           min="0.01"
                           step="0.01"
                           required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">
                        Harga Total <span class="text-red-500">*</span>
                    </label>

                    <input type="number"
                           name="fuel_total_price"
                           value="{{ old('fuel_total_price') }}"
                           class="w-full border rounded px-3 py-2"
                           min="0"
                           step="0.01"
                           required>
                </div>

            </div>

            {{-- Bukti Pengisian (kamera hanya untuk mobile) --}}
            <div class="mb-4">

                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Bukti Pengisian
                </label>

                <div class="flex items-center gap-3">

                    {{-- Tombol Kamera (mobile) --}}
                    <label for="receipt_file"
                           class="w-14 h-14 flex items-center justify-center
                                  bg-blue-600 text-white rounded-full
                                  cursor-pointer hover:bg-blue-700
                                  active:bg-blue-800
                                  transition shadow">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="w-7 h-7"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0118.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>

                    </label>

                    {{-- Input file dengan capture --}}
                    <input type="file"
                           id="receipt_file"
                           name="receipt_file"
                           accept="image/*"
                           capture="environment"
                           class="hidden">

                    {{-- Status File --}}
                    <span id="receipt_file_name"
                          class="text-sm text-gray-500">
                        Ambil foto bukti pengisian
                    </span>

                </div>

                {{-- Preview --}}
                <div id="receipt_preview"
                     class="hidden mt-3">

                    <div class="relative inline-block">

                        <img id="receipt_preview_img"
                             src=""
                             alt="Preview Bukti Pengisian"
                             class="w-40 h-40 object-cover rounded-lg border shadow">

                        <button type="button"
                                id="remove_receipt"
                                class="absolute -top-2 -right-2
                                       w-7 h-7
                                       bg-red-600 text-white
                                       rounded-full
                                       flex items-center justify-center
                                       hover:bg-red-700
                                       shadow">
                            ×
                        </button>

                    </div>

                </div>

                <p class="text-xs text-gray-400 mt-2">
                    Tekan ikon kamera untuk mengambil foto bukti pengisian.
                </p>

            </div>

            {{-- Catatan --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">
                    Catatan
                </label>

                <textarea name="notes"
                          rows="2"
                          class="w-full border rounded px-3 py-2">{{ old('notes') }}</textarea>
            </div>

            {{-- Button --}}
            <div class="flex justify-end space-x-2">

                <a href="{{ route('drms.fuel-logs.index') }}"
                   class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                    Batal
                </a>

                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Simpan
                </button>

            </div>

        </form>
    </div>
</div>

{{-- Tampilan untuk desktop (kosong / pesan alternatif) --}}
<div class="hidden md:block">
    {{-- Kosongkan atau beri pesan --}}
    <div class="container mx-auto max-w-2xl py-8 text-center text-gray-500">
        <p>Halaman ini hanya tersedia di perangkat mobile.</p>
    </div>
</div>

{{-- ========================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================================= --}}
<script>
    const VEHICLES_DATA = [
        @foreach($vehicles as $v)
        {
            id: {{ $v->id }},
            plate: @json($v->plate_number),
            type: @json($v->type),
            fuel: @json($v->fuel_type ?: 'Bensin'),
            label: @json(
                $v->plate_number .
                ' - ' .
                $v->type .
                ' (' .
                ($v->fuel_type ?: 'Bensin') .
                ')'
            )
        },
        @endforeach
    ];

    document.addEventListener('DOMContentLoaded', function () {

        /* =====================================================
         * VEHICLE
         * ===================================================== */
        const searchInput   = document.getElementById('vehicle_search');
        const hiddenInput   = document.getElementById('vehicle_id');
        const suggestionBox = document.getElementById('vehicle_suggestions');
        const fuelUnitLabel = document.getElementById('fuel_unit_label');

        function updateFuelUnit(fuelType) {
            let unit = 'Liter';
            if (fuelType && fuelType.toLowerCase() === 'listrik') {
                unit = 'kWh';
            }
            fuelUnitLabel.innerHTML = unit + ' <span class="text-red-500">*</span>';
        }

        function hideSuggestions() {
            suggestionBox.innerHTML = '';
            suggestionBox.classList.add('hidden');
        }

        function renderSuggestions(list) {
            if (!list.length) {
                suggestionBox.innerHTML =
                    '<div class="px-3 py-2 text-sm text-gray-500">' +
                    'Kendaraan tidak ditemukan / tidak tersedia' +
                    '</div>';
                suggestionBox.classList.remove('hidden');
                return;
            }

            suggestionBox.innerHTML = list.map(v => `
                <div
                    class="vehicle-option px-3 py-2 text-sm
                           hover:bg-blue-50 cursor-pointer
                           border-b last:border-b-0"
                    data-id="${v.id}"
                    data-fuel="${v.fuel}"
                    data-label="${v.label.replace(/"/g, '&quot;')}">
                    ${v.label}
                </div>
            `).join('');

            suggestionBox.classList.remove('hidden');

            suggestionBox
                .querySelectorAll('.vehicle-option')
                .forEach(function (el) {
                    el.addEventListener('click', function () {
                        hiddenInput.value = this.getAttribute('data-id');
                        searchInput.value = this.getAttribute('data-label');
                        updateFuelUnit(this.getAttribute('data-fuel'));
                        hideSuggestions();
                    });
                });
        }

        function search(term) {
            const q = term.trim().toLowerCase();
            if (!q) return [];
            return VEHICLES_DATA
                .filter(v =>
                    v.plate.toLowerCase().includes(q) ||
                    v.type.toLowerCase().includes(q)
                )
                .slice(0, 15);
        }

        searchInput.addEventListener('input', function () {
            hiddenInput.value = '';
            const results = search(this.value);
            if (this.value.trim().length === 0) {
                hideSuggestions();
                return;
            }
            renderSuggestions(results);
        });

        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length > 0 && !hiddenInput.value) {
                renderSuggestions(search(this.value));
            }
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !suggestionBox.contains(e.target)) {
                hideSuggestions();
            }
        });

        searchInput
            .closest('form')
            .addEventListener('submit', function (e) {
                if (!hiddenInput.value) {
                    e.preventDefault();
                    searchInput.setCustomValidity('Silakan pilih kendaraan dari daftar saran.');
                    searchInput.reportValidity();
                } else {
                    searchInput.setCustomValidity('');
                }
            });

        searchInput.addEventListener('input', function () {
            this.setCustomValidity('');
        });

        if (hiddenInput.value) {
            const selected = VEHICLES_DATA.find(v => String(v.id) === String(hiddenInput.value));
            updateFuelUnit(selected ? selected.fuel : null);
        } else {
            updateFuelUnit(null);
        }

        /* =====================================================
         * KAMERA / RECEIPT
         * ===================================================== */
        const receiptFile = document.getElementById('receipt_file');
        const receiptFileName = document.getElementById('receipt_file_name');
        const receiptPreview = document.getElementById('receipt_preview');
        const receiptPreviewImg = document.getElementById('receipt_preview_img');
        const removeReceipt = document.getElementById('remove_receipt');

        receiptFile.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                receiptFileName.textContent = file.name;
                receiptFileName.classList.remove('text-gray-500');
                receiptFileName.classList.add('text-green-600');

                const reader = new FileReader();
                reader.onload = function (e) {
                    receiptPreviewImg.src = e.target.result;
                    receiptPreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        removeReceipt.addEventListener('click', function () {
            receiptFile.value = '';
            receiptPreviewImg.src = '';
            receiptPreview.classList.add('hidden');
            receiptFileName.textContent = 'Ambil foto bukti pengisian';
            receiptFileName.classList.remove('text-green-600');
            receiptFileName.classList.add('text-gray-500');
        });

    });
</script>

@endsection