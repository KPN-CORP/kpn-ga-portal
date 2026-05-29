@extends('layouts.app-sidebar')
@section('content')
<div class="flex">
    <main class="flex-1">
        <div class="bg-white shadow rounded w-full" style="margin: 0.1cm 0.1cm 0 0.1cm; padding: 0.5cm;">
            <h2 class="text-2xl font-semibold mb-4 text-left">Request Messenger</h2>
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('messenger.store') }}" method="POST" enctype="multipart/form-data" id="messengerForm">
                @csrf

                <div class="space-y-6">
                    <!-- Baris 1: Jenis Barang, Alamat Asal, Alamat Tujuan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <!-- Jenis Barang -->
                        <div>
                            <label class="block text-sm font-medium mb-1">Jenis Barang <span class="text-red-500">*</span></label>
                            <select name="jenis_barang" id="jenis_barang" 
                                    class="w-full border border-gray-300 p-2 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">Pilih Jenis Barang</option>
                                <option value="paket" {{ old('jenis_barang') == 'paket' ? 'selected' : '' }}>Paket</option>
                                <option value="dokumen" {{ old('jenis_barang') == 'dokumen' ? 'selected' : '' }}>Dokumen</option>
                            </select>
                            @error('jenis_barang')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Alamat Asal + Maps Link -->
                        <div>
                            <label class="block text-sm font-medium mb-1">Alamat Asal <span class="text-red-500">*</span></label>
                            <input type="text" name="alamat_asal" id="alamat_asal" 
                                   class="w-full border border-gray-300 p-2 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   value="{{ old('alamat_asal', 'Gama Tower, DKI Jakarta, Indonesia') }}" required>
                            <div class="mt-2 text-gray-500 text-xs flex items-center gap-1">
                                <span>🔗</span>
                                <input type="url" name="maps_asal" id="maps_asal" 
                                       class="flex-1 border rounded-lg px-3 py-2 text-sm bg-gray-50" 
                                       placeholder="Link Google Maps (otomatis)" readonly>
                            </div>
                            @error('maps_asal')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Alamat Tujuan + Maps Link -->
                        <div>
                            <label class="block text-sm font-medium mb-1">Alamat Tujuan <span class="text-red-500">*</span></label>
                            <input type="text" name="alamat_tujuan" id="alamat_tujuan" 
                                   class="w-full border border-gray-300 p-2 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   value="{{ old('alamat_tujuan') }}" placeholder="Masukkan alamat tujuan lengkap" required>
                            <div class="mt-2 text-gray-500 text-xs flex items-center gap-1">
                                <span>🔗</span>
                                <input type="url" name="maps_tujuan" id="maps_tujuan" 
                                       class="flex-1 border rounded-lg px-3 py-2 text-sm bg-gray-50" 
                                       placeholder="Link Google Maps (otomatis)" readonly>
                            </div>
                            @error('maps_tujuan')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Baris 2: Penerima dan No HP -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Nama Penerima <span class="text-red-500">*</span></label>
                            <input type="text" name="penerima" id="penerima" 
                                   class="w-full border border-gray-300 p-2 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   value="{{ old('penerima') }}" placeholder="Nama lengkap penerima" required>
                            @error('penerima')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">No. HP Penerima <span class="text-red-500">*</span></label>
                            <input type="tel" name="no_hp_penerima" id="no_hp_penerima" 
                                   class="w-full border border-gray-300 p-2 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   value="{{ old('no_hp_penerima') }}" placeholder="081234567890" pattern="[0-9]{10,13}" required>
                            @error('no_hp_penerima')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            <p class="text-xs text-gray-500 mt-1">Format: 10-13 digit angka</p>
                        </div>
                    </div>

                    <!-- Deskripsi Barang -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Deskripsi Barang <span class="text-red-500">*</span></label>
                        <textarea name="deskripsi" id="deskripsi" 
                                  class="w-full border border-gray-300 p-2 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                  rows="4" placeholder="Deskripsi lengkap barang yang akan dikirim" required>{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Foto/Dokumen Barang -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Foto/Dokumen Barang <span class="text-red-500">*</span></label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-500 transition-colors cursor-pointer">
                            <div class="space-y-2">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                                <p class="text-sm text-gray-600"><span class="font-semibold">Klik untuk upload</span> atau drag & drop</p>
                                <p class="text-xs text-gray-500">Format: JPG, PNG, PDF, DOC, DOCX (Maksimal: 20MB)</p>
                            </div>
                            <input type="file" name="foto_barang" id="foto_barang" class="hidden" accept="image/*,.pdf,.doc,.docx" required>
                        </div>
                        @error('foto_barang')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        <div id="previewContainer" class="mt-4 hidden">
                            <p class="text-sm font-medium text-gray-700 mb-2">Preview:</p>
                            <div id="filePreview" class="inline-block p-3 border border-gray-300 rounded-lg bg-gray-50"></div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-8 pt-4 border-t">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm font-medium transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i>Kirim Request
                    </button>
                    <a href="{{ route('messenger.index') }}" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded text-sm font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========== AUTO-FILL MAPS LINK ==========
    function autoFillMapsLink(addressId, mapsId) {
        const addressInput = document.getElementById(addressId);
        const mapsInput = document.getElementById(mapsId);
        if (!addressInput || !mapsInput) return;
        addressInput.addEventListener('blur', function() {
            const address = this.value.trim();
            if (address !== '') {
                mapsInput.value = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
            }
        });
    }
    autoFillMapsLink('alamat_asal', 'maps_asal');
    autoFillMapsLink('alamat_tujuan', 'maps_tujuan');

    // ========== EXISTING CODE (Preview file, dll.) ==========
    const form = document.getElementById('messengerForm');
    const fileInput = document.getElementById('foto_barang');
    const previewContainer = document.getElementById('previewContainer');
    const filePreview = document.getElementById('filePreview');
    const uploadArea = fileInput.closest('.border-dashed');
    const phoneInput = document.getElementById('no_hp_penerima');

    uploadArea.addEventListener('click', () => fileInput.click());

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); });
    });
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => uploadArea.classList.add('border-blue-500', 'bg-blue-50'));
    });
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('border-blue-500', 'bg-blue-50'));
    });
    uploadArea.addEventListener('drop', e => {
        const dt = e.dataTransfer;
        fileInput.files = dt.files;
        handleFileUpload(dt.files[0]);
    });
    fileInput.addEventListener('change', e => {
        if (this.files.length) handleFileUpload(this.files[0]);
    });

    function handleFileUpload(file) {
        const maxSize = 20 * 1024 * 1024;
        if (file.size > maxSize) {
            alert(`File terlalu besar! Maksimal 20MB.`);
            fileInput.value = '';
            previewContainer.classList.add('hidden');
            return;
        }
        const validTypes = ['image/jpeg','image/jpg','image/png','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!validTypes.includes(file.type)) {
            alert('Format file tidak valid.');
            fileInput.value = '';
            previewContainer.classList.add('hidden');
            return;
        }
        let previewContent = '';
        if (file.type.startsWith('image/')) {
            previewContent = `<div class="flex items-center space-x-4"><img src="${URL.createObjectURL(file)}" class="h-32 w-32 object-cover rounded"><div><p class="font-medium text-sm">${file.name}</p><p class="text-xs text-gray-500">${(file.size/1024).toFixed(2)} KB</p></div></div>`;
        } else if (file.type === 'application/pdf') {
            previewContent = `<div class="flex items-center space-x-4"><div class="bg-red-100 p-3 rounded"><i class="fas fa-file-pdf text-3xl text-red-500"></i></div><div><p class="font-medium text-sm">${file.name}</p><p class="text-xs text-gray-500">PDF Document • ${(file.size/1024).toFixed(2)} KB</p></div></div>`;
        } else {
            previewContent = `<div class="flex items-center space-x-4"><div class="bg-blue-100 p-3 rounded"><i class="fas fa-file-word text-3xl text-blue-500"></i></div><div><p class="font-medium text-sm">${file.name}</p><p class="text-xs text-gray-500">Word Document • ${(file.size/1024).toFixed(2)} KB</p></div></div>`;
        }
        filePreview.innerHTML = previewContent;
        previewContainer.classList.remove('hidden');
    }

    form.addEventListener('submit', function(e) {
        let isValid = true;
        let errorMessages = [];
        const phonePattern = /^[0-9]{10,13}$/;
        if (!phonePattern.test(phoneInput.value.trim())) {
            errorMessages.push('Nomor HP Penerima harus 10-13 digit angka');
            phoneInput.classList.add('border-red-500');
            isValid = false;
        } else phoneInput.classList.remove('border-red-500');

        if (!fileInput.files.length) {
            errorMessages.push('Foto/Dokumen Barang wajib diupload');
            uploadArea.classList.add('border-red-500');
            isValid = false;
        } else uploadArea.classList.remove('border-red-500');

        if (!isValid) {
            e.preventDefault();
            if (errorMessages.length) alert('Terjadi kesalahan:\n\n' + errorMessages.join('\n'));
            return false;
        }
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
        submitBtn.disabled = true;
        setTimeout(() => { submitBtn.innerHTML = originalText; submitBtn.disabled = false; }, 5000);
    });

    form.querySelectorAll('input, textarea, select').forEach(input => {
        input.addEventListener('input', function() { this.classList.remove('border-red-500'); });
    });
});
</script>
@endsection