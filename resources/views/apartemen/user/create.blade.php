@extends('layouts.app-sidebar')

@section('content')
<div class="max-w-4xl mx-auto p-4 md:p-6">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Pengajuan Apartemen</h1>
        <p class="text-gray-500 text-sm mt-1">Isi form untuk mengajukan hunian</p>
    </div>

    @if(session('error'))
    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <ul class="list-disc pl-4">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('apartemen.user.store') }}" class="space-y-6">
        @csrf

        {{-- PILIH UNIT --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Pilih Unit</h2>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Unit Tersedia *</label>
                <select name="unit_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Unit --</option>
                    @foreach($availableUnits as $apartemen => $units)
                    <optgroup label="{{ $apartemen }}">
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                            Unit {{ $unit->nomor_unit }} (Kapasitas: {{ $unit->kapasitas }} orang)
                        </option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
                @error('unit_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai *</label>
                    <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Selesai *</label>
                    <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        {{-- DATA PENGHUNI --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Data Penghuni</h2>
                <span class="text-sm text-gray-500">Maksimal 5 orang</span>
            </div>

            <div id="penghuni-container">
                @php
                    $oldPenghuni = old('penghuni', [['nama' => '', 'id_karyawan' => '', 'no_hp' => '', 'unit_kerja' => '', 'gol' => '']]);
                @endphp

                @foreach($oldPenghuni as $index => $p)
                <div class="penghuni-item border border-gray-200 rounded-lg p-4 mb-4 {{ $index > 0 ? 'mt-4' : '' }}">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-medium text-gray-700">Penghuni {{ $index + 1 }}</h3>
                        @if($index > 0)
                        <button type="button" onclick="hapusPenghuni(this)" class="text-red-600 hover:text-red-800 text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
                            <input type="text" name="penghuni[{{ $index }}][nama]" value="{{ $p['nama'] }}" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ID Karyawan *</label>
                            <input type="text" name="penghuni[{{ $index }}][id_karyawan]" value="{{ $p['id_karyawan'] }}" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">No HP *</label>
                            <input type="text" name="penghuni[{{ $index }}][no_hp]" value="{{ $p['no_hp'] }}" required
                                   placeholder="08123456789"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Kerja</label>
                            <input type="text" name="penghuni[{{ $index }}][unit_kerja]" value="{{ $p['unit_kerja'] }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Golongan</label>
                            <select name="penghuni[{{ $index }}][gol]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">Pilih</option>
                                @foreach(['4','5','6','7','8','9','10'] as $gol)
                                <option value="{{ $gol }}" {{ $p['gol'] == $gol ? 'selected' : '' }}>{{ $gol }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <button type="button" onclick="tambahPenghuni()" 
                    class="mt-2 text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Penghuni
            </button>
        </div>

        {{-- ALASAN --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Pengajuan *</label>
            <textarea name="alasan" rows="3" required minlength="10"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                      placeholder="Contoh: Penempatan kerja...">{{ old('alasan') }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Minimal 10 karakter</p>
        </div>

        {{-- SUBMIT --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('apartemen.user.requests') }}" 
               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Batal
            </a>
            <button type="submit" id="submitBtn"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                Ajukan Permintaan
            </button>
        </div>
    </form>
</div>

<script>
let penghuniCount = {{ count(old('penghuni', [['nama' => '']])) }};

function tambahPenghuni() {
    if (penghuniCount >= 5) {
        alert('Maksimal 5 penghuni');
        return;
    }
    
    const container = document.getElementById('penghuni-container');
    const newIndex = penghuniCount;
    
    const html = `
        <div class="penghuni-item border border-gray-200 rounded-lg p-4 mt-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-medium text-gray-700">Penghuni ${newIndex + 1}</h3>
                <button type="button" onclick="hapusPenghuni(this)" class="text-red-600 hover:text-red-800 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="penghuni[${newIndex}][nama]" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID Karyawan *</label>
                    <input type="text" name="penghuni[${newIndex}][id_karyawan]" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">No HP *</label>
                    <input type="text" name="penghuni[${newIndex}][no_hp]" required
                           placeholder="08123456789"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit Kerja</label>
                    <input type="text" name="penghuni[${newIndex}][unit_kerja]"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Golongan</label>
                    <select name="penghuni[${newIndex}][gol]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Pilih</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    penghuniCount++;
}

function hapusPenghuni(button) {
    if (penghuniCount <= 1) {
        alert('Minimal 1 penghuni');
        return;
    }
    
    button.closest('.penghuni-item').remove();
    penghuniCount--;
    
    // Renumber
    document.querySelectorAll('.penghuni-item').forEach((item, index) => {
        item.querySelector('h3').textContent = `Penghuni ${index + 1}`;
        
        // Update name attributes
        item.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
            }
        });
    });
}

// Form submit loading
document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = 'Mengirim...';
});
</script>
@endsection