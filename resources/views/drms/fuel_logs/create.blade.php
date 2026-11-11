@extends('layouts.app_car_sidebar')

@section('content')
{{-- Tampilan khusus mobile --}}
<div class="block md:hidden">
    <div class="container mx-auto max-w-2xl">
        <h1 class="text-2xl font-bold mb-4">Pengisian BBM / Listrik</h1>

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
                           <p class="text-xs text-gray-500 mt-1">Isi jumlah liter bahan bakar yang dibeli (contoh: 50.5).</p>
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
                           <p class="text-xs text-gray-500 mt-1">Masukkan total harga dalam angka (contoh: 750000).</p>
                </div>

            </div>

            {{-- 3 FOTO (kamera hanya untuk mobile):
                 1) Foto km + indikator BBM/baterai SEBELUM pengisian  -> wajib untuk simpan draft
                 2) Foto km + indikator BBM/baterai SETELAH pengisian  -> wajib untuk kirim final
                 3) Struk / bukti pengisian                           -> wajib untuk kirim final --}}
            @php
                $photoFields = [
                    [
                        'key'   => 'photo_dashboard_before',
                        'label' => 'Foto Km dan Indikator BBM/Baterai Sebelum Pengisian',
                        'hint'  => 'Foto km (odometer) dan indikator BBM/baterai sebelum pengisian, langsung dari kamera.',
                        'empty' => 'Foto Km dan Indikator BBM/Baterai Sebelum Pengisian',
                        'phase' => 'before',
                    ],
                    [
                        'key'   => 'photo_dashboard_after',
                        'label' => 'Foto Km dan Indikator BBM/Baterai Setelah Pengisian',
                        'hint'  => 'Foto km (odometer) dan indikator BBM/baterai setelah pengisian, langsung dari kamera.',
                        'empty' => 'Foto Km dan Indikator BBM/Baterai Setelah Pengisian',
                        'phase' => 'after',
                    ],
                    [
                        'key'   => 'receipt_file',
                        'label' => 'Foto Struk Pengisian',
                        'hint'  => 'Tekan ikon kamera untuk mengambil foto struk pengisian.',
                        'empty' => 'Foto Struk Pengisian',
                        'phase' => 'after',
                    ],
                ];
            @endphp

            {{-- Catatan alur untuk yang mengisi --}}
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                <p class="font-semibold mb-1">📸 Alur pengisian</p>
                <p><strong>1. Sebelum mengisi:</strong> isi kendaraan, odometer, lalu foto km dan indikator BBM/baterai → tekan <strong>Simpan Draft</strong>.</p>
                <p><strong>2. Setelah mengisi:</strong> buka draft di daftar log → tekan <strong>Lengkapi</strong> (liter, harga, foto km dan indikator setelah pengisian, struk).</p>
                <p class="text-xs mt-2 text-amber-700">Kalau semua sudah siap sekarang, isi semuanya lalu tekan <strong>Simpan &amp; Kirim</strong>. Pastikan odometer dan indikator BBM/baterai terlihat jelas dan tidak buram.</p>
            </div>

            @foreach($photoFields as $pf)
            <div class="mb-4 photo-field" data-key="{{ $pf['key'] }}" data-empty="{{ $pf['empty'] }}">

                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ $pf['label'] }}
                    @if($pf['phase'] === 'before')
                        <span class="text-red-500">*</span>
                    @else
                        <span class="text-xs font-normal text-gray-400">(wajib saat Simpan &amp; Kirim)</span>
                    @endif
                </label>

                <div class="flex items-center gap-3">

                    {{-- Tombol Kamera (mobile) --}}
                    <label for="{{ $pf['key'] }}"
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

                    {{-- Input file dengan capture.
                         Foto dashboard: SELALU kamera langsung (capture="environment").
                         Foto struk: untuk kendaraan listrik atribut capture dilepas lewat JS
                         (boleh pilih dari galeri hp), selain itu tetap kamera langsung. --}}
                    <input type="file"
                           id="{{ $pf['key'] }}"
                           name="{{ $pf['key'] }}"
                           accept="image/*"
                           capture="environment"
                           class="hidden">

                    {{-- Status File --}}
                    <span id="{{ $pf['key'] }}_name"
                          class="text-sm text-gray-500">
                        {{ $pf['empty'] }}
                    </span>

                </div>

                {{-- Preview --}}
                <div id="{{ $pf['key'] }}_preview"
                     class="hidden mt-3">

                    <div class="relative inline-block">

                        <img id="{{ $pf['key'] }}_preview_img"
                             src=""
                             alt="Preview {{ $pf['empty'] }}"
                             class="w-40 h-40 object-cover rounded-lg border shadow">

                        <button type="button"
                                id="{{ $pf['key'] }}_remove"
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
                    {{ $pf['hint'] }}
                </p>

                <p id="{{ $pf['key'] }}_error" class="hidden text-xs text-red-600 font-medium mt-1">
                    {{ $pf['label'] }} wajib diisi.
                </p>

            </div>
            @endforeach

            {{-- Catatan --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">
                    Catatan
                </label>

                <textarea name="notes"
                          rows="2"
                          class="w-full border rounded px-3 py-2">{{ old('notes') }}</textarea>
            </div>

            {{-- Button: save_mode dikirim lewat input hidden (diisi saat tombol diklik) --}}
            <input type="hidden" name="save_mode" id="save_mode" value="submit">
            <div class="flex flex-wrap justify-end gap-2">

                <a href="{{ route('drms.fuel-logs.index') }}"
                   class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                    Batal
                </a>

                {{-- formnovalidate: draft hanya butuh kendaraan, odometer, foto sebelum (dicek manual di JS) --}}
                <button type="submit"
                        id="btn_draft"
                        formnovalidate
                        class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600">
                    Simpan Draft
                </button>

                <button type="submit"
                        id="btn_submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Simpan &amp; Kirim
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

    // Daftar foto (harus sama dengan key di $photoFields).
    // Foto sebelum wajib di kedua mode; sesudah + struk hanya wajib saat Simpan & Kirim.
    const PHOTO_KEYS = ['photo_dashboard_before', 'photo_dashboard_after', 'receipt_file'];
    const PHOTO_REQUIRED_ALWAYS = ['photo_dashboard_before'];

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

        // Mobil listrik: boleh ambil foto dari galeri hp (atribut capture dilepas).
        // Mobil lain: wajib ambil foto langsung dari kamera (capture="environment").
        function updateReceiptCaptureMode(fuelType) {
            const receiptInput = document.getElementById('receipt_file');
            if (!receiptInput) return;

            const isElectric = fuelType && fuelType.toLowerCase() === 'listrik';
            if (isElectric) {
                receiptInput.removeAttribute('capture');
            } else {
                receiptInput.setAttribute('capture', 'environment');
            }
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
                        updateReceiptCaptureMode(this.getAttribute('data-fuel'));
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

        // Mode simpan ditentukan tombol yang diklik (draft / submit)
        const saveModeInput = document.getElementById('save_mode');
        document.getElementById('btn_draft').addEventListener('click', function () { saveModeInput.value = 'draft'; });
        document.getElementById('btn_submit').addEventListener('click', function () { saveModeInput.value = 'submit'; });

        searchInput
            .closest('form')
            .addEventListener('submit', function (e) {
                let hasError = false;
                const isDraft = saveModeInput.value === 'draft';

                // Draft dikirim dengan formnovalidate, jadi odometer dicek manual di sini.
                if (isDraft) {
                    const odo = document.querySelector('input[name="odometer_start"]');
                    if (!odo.value) {
                        e.preventDefault();
                        odo.setCustomValidity('Odometer wajib diisi untuk menyimpan draft.');
                        odo.reportValidity();
                        odo.addEventListener('input', function () { odo.setCustomValidity(''); }, { once: true });
                        return;
                    }
                }

                if (!hiddenInput.value) {
                    e.preventDefault();
                    searchInput.setCustomValidity('Silakan pilih kendaraan dari daftar saran.');
                    searchInput.reportValidity();
                    hasError = true;
                } else {
                    searchInput.setCustomValidity('');
                }

                // Foto wajib divalidasi manual di sini karena atribut `required` pada
                // <input type="file"> yang disembunyikan (display:none) tidak bisa difokuskan
                // browser dan bisa membuat submit gagal tanpa pesan.
                // Draft: hanya foto sebelum. Kirim: semua foto.
                let firstMissing = null;
                PHOTO_KEYS.forEach(function (key) {
                    const input = document.getElementById(key);
                    const err = document.getElementById(key + '_error');
                    const mustHave = !isDraft || PHOTO_REQUIRED_ALWAYS.includes(key);
                    if (mustHave && (!input.files || input.files.length === 0)) {
                        err.classList.remove('hidden');
                        if (!firstMissing) firstMissing = err;
                    } else {
                        err.classList.add('hidden');
                    }
                });
                if (firstMissing) {
                    e.preventDefault();
                    if (!hasError) {
                        firstMissing.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    hasError = true;
                }
            });

        searchInput.addEventListener('input', function () {
            this.setCustomValidity('');
        });

        if (hiddenInput.value) {
            const selected = VEHICLES_DATA.find(v => String(v.id) === String(hiddenInput.value));
            updateFuelUnit(selected ? selected.fuel : null);
            updateReceiptCaptureMode(selected ? selected.fuel : null);
        } else {
            updateFuelUnit(null);
            updateReceiptCaptureMode(null);
        }

        /* =====================================================
         * KAMERA (3 foto: dashboard sebelum, dashboard sesudah, struk)
         * ===================================================== */
        const MAX_SIZE = 5 * 1024 * 1024; // samakan dengan max:5120 di server

        // Kompres foto di browser sebelum diupload, supaya tidak kena limit
        // "max:5120" (5MB) di server sebelum sempat dikompres ulang di backend.
        // Foto langsung dari kamera HP modern seringkali > 5MB.
        function compressImage(file, maxDim, quality) {
            return new Promise(function (resolve, reject) {
                const img = new Image();
                const reader = new FileReader();

                reader.onload = function (e) {
                    img.onload = function () {
                        let width = img.width;
                        let height = img.height;

                        if (width > height && width > maxDim) {
                            height = Math.round(height * (maxDim / width));
                            width = maxDim;
                        } else if (height > maxDim) {
                            width = Math.round(width * (maxDim / height));
                            height = maxDim;
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        canvas.getContext('2d').drawImage(img, 0, 0, width, height);

                        canvas.toBlob(function (blob) {
                            if (!blob) {
                                reject(new Error('Gagal kompres gambar'));
                                return;
                            }
                            const newName = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                            resolve(new File([blob], newName, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            }));
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = reject;
                    img.src = e.target.result;
                };
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        function setupPhotoField(key) {
            const wrap        = document.querySelector('.photo-field[data-key="' + key + '"]');
            const emptyText   = wrap.getAttribute('data-empty');
            const fileInput   = document.getElementById(key);
            const fileName    = document.getElementById(key + '_name');
            const preview     = document.getElementById(key + '_preview');
            const previewImg  = document.getElementById(key + '_preview_img');
            const removeBtn   = document.getElementById(key + '_remove');
            const errorEl     = document.getElementById(key + '_error');

            fileInput.addEventListener('change', async function () {
                if (this.files && this.files.length > 0) {
                    let file = this.files[0];

                    if (file.type.startsWith('image/') && file.size > MAX_SIZE) {
                        fileName.textContent = 'Mengompres foto...';
                        try {
                            const compressed = await compressImage(file, 1600, 0.7);
                            if (compressed.size < file.size) {
                                file = compressed;
                                const dt = new DataTransfer();
                                dt.items.add(file);
                                fileInput.files = dt.files;
                            }
                        } catch (err) {
                            console.error('Gagal kompres foto:', err);
                        }
                    }

                    fileName.textContent = file.name;
                    fileName.classList.remove('text-gray-500');
                    fileName.classList.add('text-green-600');
                    errorEl.classList.add('hidden');

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                        preview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            });

            removeBtn.addEventListener('click', function () {
                fileInput.value = '';
                previewImg.src = '';
                preview.classList.add('hidden');
                fileName.textContent = emptyText;
                fileName.classList.remove('text-green-600');
                fileName.classList.add('text-gray-500');
            });
        }

        PHOTO_KEYS.forEach(setupPhotoField);

    });
</script>

@endsection