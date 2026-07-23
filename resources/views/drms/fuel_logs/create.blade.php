@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Tambah Log</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('drms.fuel-logs.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow">
        @csrf

        {{-- Kendaraan --}}
        <div class="mb-4 relative">
            <label class="block text-sm font-medium text-gray-700">Kendaraan <span class="text-red-500">*</span></label>
            @php
                $oldVehicle = old('vehicle_id') ? $vehicles->firstWhere('id', (int) old('vehicle_id')) : null;
                $oldVehicleLabel = $oldVehicle
                    ? $oldVehicle->plate_number . ' - ' . $oldVehicle->type . ' (' . ($oldVehicle->fuel_type ?: 'Bensin') . ')'
                    : '';
            @endphp
            <input type="text"
                   id="vehicle_search"
                   autocomplete="off"
                   placeholder="Ketik plat nomor / tipe kendaraan..."
                   class="w-full border rounded px-3 py-2"
                   value="{{ $oldVehicleLabel }}"
                   required>
            <input type="hidden" name="vehicle_id" id="vehicle_id" value="{{ old('vehicle_id') }}">

            {{-- Daftar saran, muncul otomatis saat mengetik --}}
            <div id="vehicle_suggestions"
                 class="hidden absolute z-20 mt-1 w-full bg-white border rounded shadow-lg max-h-60 overflow-y-auto">
            </div>
            <p class="text-xs text-gray-400 mt-1">Ketik untuk mencari kendaraan yang tersedia, lalu pilih dari saran yang muncul.</p>
        </div>

        <input type="hidden" name="driver_id" value="{{ $driver->id ?? '' }}">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Driver</label>
            <input type="text" value="{{ $driver->name ?? 'Tidak ada driver' }}" disabled class="w-full border rounded px-3 py-2 bg-gray-100">
            <p class="text-xs text-gray-400 mt-1">Driver diambil dari user yang login</p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Tanggal Pengisian <span class="text-red-500">*</span></label>
            <input type="date" name="filling_date" value="{{ old('filling_date', date('Y-m-d')) }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Odometer Saat Ini (km) <span class="text-red-500">*</span></label>
            <input type="number" name="odometer_start" value="{{ old('odometer_start') }}" class="w-full border rounded px-3 py-2" min="0" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700" id="fuel_unit_label">Liter <span class="text-red-500">*</span></label>
                <input type="number" name="fuel_liters" id="fuel_liters" value="{{ old('fuel_liters') }}" class="w-full border rounded px-3 py-2" min="0.01" step="0.01" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Harga Total <span class="text-red-500">*</span></label>
                <input type="number" name="fuel_total_price" value="{{ old('fuel_total_price') }}" class="w-full border rounded px-3 py-2" min="0" step="0.01" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Upload Struk (ambil dari kamera)</label>
            <input type="file" name="receipt_file" accept="image/*" capture="environment" class="w-full border rounded px-3 py-2 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            <p class="text-xs text-gray-400 mt-1">Untuk HP akan membuka kamera, untuk laptop bisa pilih file</p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catatan</label>
            <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2">{{ old('notes') }}</textarea>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.fuel-logs.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
        </div>
    </form>
</div>

{{-- Data kendaraan untuk pencarian & saran (autocomplete, TANPA SELECT/DROPDOWN) --}}
<script>
    const VEHICLES_DATA = [
        @foreach($vehicles as $v)
        {
            id: {{ $v->id }},
            plate: @json($v->plate_number),
            type: @json($v->type),
            fuel: @json($v->fuel_type ?: 'Bensin'),
            label: @json($v->plate_number . ' - ' . $v->type . ' (' . ($v->fuel_type ?: 'Bensin') . ')')
        },
        @endforeach
    ];

    document.addEventListener('DOMContentLoaded', function() {
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
                suggestionBox.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Kendaraan tidak ditemukan / tidak tersedia</div>';
                suggestionBox.classList.remove('hidden');
                return;
            }
            suggestionBox.innerHTML = list.map(v => `
                <div class="vehicle-option px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer border-b last:border-b-0"
                     data-id="${v.id}" data-fuel="${v.fuel}" data-label="${v.label.replace(/"/g, '&quot;')}">
                    ${v.label}
                </div>
            `).join('');
            suggestionBox.classList.remove('hidden');

            suggestionBox.querySelectorAll('.vehicle-option').forEach(function (el) {
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
            return VEHICLES_DATA.filter(v =>
                v.plate.toLowerCase().includes(q) || v.type.toLowerCase().includes(q)
            ).slice(0, 15);
        }

        // Saran muncul setiap kali user mengetik
        searchInput.addEventListener('input', function () {
            // Setiap kali teks berubah manual, anggap belum ada kendaraan valid terpilih
            hiddenInput.value = '';
            const results = search(this.value);
            if (this.value.trim().length === 0) {
                hideSuggestions();
                return;
            }
            renderSuggestions(results);
        });

        // Tampilkan kembali saran saat fokus (jika sedang mengetik sesuatu)
        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length > 0 && !hiddenInput.value) {
                renderSuggestions(search(this.value));
            }
        });

        // Sembunyikan saran saat klik di luar
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !suggestionBox.contains(e.target)) {
                hideSuggestions();
            }
        });

        // Validasi sebelum submit: pastikan kendaraan yang valid sudah dipilih dari saran
        searchInput.closest('form').addEventListener('submit', function (e) {
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

        // Set label satuan bahan bakar awal jika sudah ada kendaraan terpilih (redisplay setelah error validasi)
        if (hiddenInput.value) {
            const selected = VEHICLES_DATA.find(v => String(v.id) === String(hiddenInput.value));
            updateFuelUnit(selected ? selected.fuel : null);
        } else {
            updateFuelUnit(null);
        }
    });
</script>
@endsection