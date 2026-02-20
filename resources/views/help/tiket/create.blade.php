{{-- resources/views/help/tiket/create.blade.php --}}
@extends('layouts.app-sidebar')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Buat Tiket Baru</h2>
        <p class="text-gray-600">Isi form di bawah ini untuk membuat tiket pengaduan baru</p>
    </div>
    
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <form action="{{ route('help.tiket.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="space-y-6">
                <!-- Judul -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Judul Pengaduan *
                    </label>
                    <input type="text" 
                           name="judul" 
                           value="{{ old('judul') }}"
                           required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Lampu Area Divisi ERP (Lantai 46)">
                    @error('judul')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Deskripsi -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Deskripsi Masalah *
                    </label>
                    <textarea name="deskripsi" 
                              rows="4"
                              required
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Lampu tidak menyala">{{ old('deskripsi') }}</textarea>
                    @error('deskripsi')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Kategori, Bisnis Unit & Prioritas -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Kategori -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Kategori *
                        </label>
                        <select name="kategori_id" 
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih Kategori</option>
                            @foreach($kategori as $kat)
                            <option value="{{ $kat->id }}" {{ old('kategori_id') == $kat->id ? 'selected' : '' }}>
                                {{ $kat->nama }}
                            </option>
                            @endforeach
                        </select>
                        @error('kategori_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Bisnis Unit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Bisnis Unit
                        </label>
                        <select name="bisnis_unit_id" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih Bisnis Unit</option>
                            @foreach($bisnisUnits as $unit)
                            <option value="{{ $unit->id_bisnis_unit }}" {{ old('bisnis_unit_id') == $unit->id_bisnis_unit ? 'selected' : '' }}>
                                {{ $unit->nama_bisnis_unit }}
                            </option>
                            @endforeach
                        </select>
                        @error('bisnis_unit_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Prioritas -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Prioritas *
                        </label>
                        <select name="prioritas" 
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih Prioritas</option>
                            <option value="LOW" {{ old('prioritas') == 'LOW' ? 'selected' : '' }}>LOW</option>
                            <option value="MEDIUM" {{ old('prioritas') == 'MEDIUM' ? 'selected' : '' }}>MEDIUM</option>
                            <option value="HIGH" {{ old('prioritas') == 'HIGH' ? 'selected' : '' }}>HIGH</option>
                            <option value="URGENT" {{ old('prioritas') == 'URGENT' ? 'selected' : '' }}>URGENT</option>
                        </select>
                        @error('prioritas')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Lampiran -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Lampiran (Opsional)
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                        <input type="file" 
                               id="lampiran" 
                               name="lampiran[]" 
                               multiple
                               class="hidden"
                               accept="image/*,.pdf,.doc,.docx">
                        <div id="file-upload-area" class="cursor-pointer py-4">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-600">Klik untuk memilih file</p>
                            <p class="text-xs text-gray-500 mt-1">Maks: 5MB per file. Format: JPG, PNG, PDF, DOC</p>
                        </div>
                        <div id="file-list" class="mt-4 space-y-2"></div>
                    </div>
                    @error('lampiran.*')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Actions -->
                <div class="pt-6 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-end gap-3">
                        <a href="{{ route('help.tiket.index') }}" 
                           class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">
                            Batal
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                            <i class="fas fa-paper-plane mr-2"></i> Kirim Tiket
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('file-upload-area').addEventListener('click', () => {
    document.getElementById('lampiran').click();
});

document.getElementById('lampiran').addEventListener('change', function(e) {
    const fileList = document.getElementById('file-list');
    fileList.innerHTML = '';
    
    Array.from(this.files).forEach(file => {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between bg-gray-50 p-3 rounded-lg';
        div.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-file text-gray-400 mr-3"></i>
                <div>
                    <p class="text-sm text-gray-700">${file.name}</p>
                    <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                </div>
            </div>
            <button type="button" class="text-red-500 hover:text-red-700" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        fileList.appendChild(div);
    });
});
</script>
@endpush
@endsection