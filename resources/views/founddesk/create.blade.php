{{-- resources/views/founddesk/create.blade.php --}}
@extends('layouts.app-sidebar')

@section('content')
<div class="p-6 bg-slate-50 min-h-screen space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Tambah Barang Temuan</h1>
            <p class="text-sm text-slate-500">Lengkapi informasi barang yang ditemukan</p>
        </div>
        <a href="{{ route('founddesk.index') }}"
           class="px-4 py-2 rounded-lg border bg-white hover:bg-slate-100 text-sm">
            ← Kembali
        </a>
    </div>

    <form action="{{ route('founddesk.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- MAIN FORM --}}
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 space-y-6">

                <div class="bg-blue-50 rounded-lg p-4 flex items-center gap-4">
                    <div class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-box text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs text-blue-600">Kode Barang</p>
                        <p class="font-semibold text-lg text-blue-700">{{ $itemCode }}</p>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium">Nama Barang <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="w-full mt-1 px-4 py-2 rounded-lg border focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                        placeholder="Contoh: Dompet Hitam">
                    @error('name')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium">Kategori</label>
                        <select name="category_id"
                                class="w-full mt-1 px-4 py-2 rounded-lg border">
                            <option value="">Pilih kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id')==$cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium">Kondisi</label>
                        <select name="condition_id"
                                class="w-full mt-1 px-4 py-2 rounded-lg border">
                            <option value="">Pilih kondisi</option>
                            @foreach($conditions as $cond)
                                <option value="{{ $cond->id }}" @selected(old('condition_id')==$cond->id)>
                                    {{ $cond->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium">Jumlah</label>
                        <div class="flex gap-2 mt-1">
                            <input type="number" name="current_stock"
                                   value="{{ old('current_stock', 1) }}"
                                   min="1"
                                   class="w-full px-4 py-2 rounded-lg border">
                            <select name="unit"
                                    class="px-3 py-2 rounded-lg border bg-white">
                                <option value="pcs">pcs</option>
                                <option value="buah">buah</option>
                                <option value="unit">unit</option>
                                <option value="pasang">pasang</option>
                                <option value="lembar">lembar</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium">Tanggal Ditemukan</label>
                        <input type="date" name="found_date"
                               value="{{ old('found_date', date('Y-m-d')) }}"
                               class="w-full mt-1 px-4 py-2 rounded-lg border">
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium">Lokasi Penemuan</label>
                    <select name="location_id"
                            class="w-full mt-1 px-4 py-2 rounded-lg border">
                        <option value="">Pilih lokasi</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" @selected(old('location_id')==$loc->id)>
                                {{ $loc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium">Ditemukan Oleh</label>
                    <input type="text" name="found_by"
                           value="{{ old('found_by') }}"
                           class="w-full mt-1 px-4 py-2 rounded-lg border"
                           placeholder="Contoh: Petugas Kebersihan">
                </div>

                <div>
                    <label class="text-sm font-medium">Deskripsi</label>
                    <textarea name="description" rows="3"
                              class="w-full mt-1 px-4 py-2 rounded-lg border"
                              placeholder="Ciri-ciri barang, warna, merek, dll">{{ old('description') }}</textarea>
                </div>
            </div>

            {{-- SIDEBAR --}}
            <div class="space-y-6">

                {{-- Upload Foto dengan Kompresi --}}
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <label class="text-sm font-medium block mb-3">Foto Barang</label>
                    
                    <div class="text-center">
                        {{-- Input file dengan capture untuk HP --}}
                        <input type="file" name="photo" id="photoInput" class="hidden" accept="image/*">
                        
                        <div id="uploadBox"
                             class="border-2 border-dashed rounded-xl p-6 cursor-pointer hover:border-blue-500 transition">
                            <i class="fas fa-cloud-upload-alt text-3xl text-slate-300 mb-2"></i>
                            <p class="text-sm text-slate-500">Klik untuk upload foto</p>
                            <p class="text-xs text-slate-400 mt-1">Format: JPG, PNG (max 10MB akan dikompres)</p>
                        </div>
                        
                        {{-- Opsi Pilihan: Gallery atau Kamera --}}
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <button type="button" onclick="openGallery()" 
                                    class="px-3 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100">
                                <i class="fas fa-images mr-1"></i> Gallery
                            </button>
                            <button type="button" onclick="openCamera()" 
                                    class="px-3 py-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100">
                                <i class="fas fa-camera mr-1"></i> Kamera
                            </button>
                        </div>
                        
                        {{-- Progress bar --}}
                        <div id="progressContainer" class="hidden mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                            </div>
                            <p id="progressText" class="text-xs text-gray-500 mt-1">Memproses...</p>
                        </div>
                        
                        <div id="previewContainer" class="hidden mt-4">
                            <img id="preview" class="rounded-lg object-cover max-h-48 w-full">
                            <div class="flex items-center justify-between mt-2 text-xs">
                                <span id="fileSize" class="text-gray-500"></span>
                                <button type="button" onclick="removePhoto()"
                                        class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times mr-1"></i>Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="bg-white rounded-xl shadow-sm p-6 space-y-3">
                    <div class="flex items-center gap-3 pb-3 border-b">
                        <div class="w-8 h-8 flex items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Dibuat Oleh</p>
                            <p class="font-medium">{{ Auth::user()->name }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 flex items-center justify-center rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Status Awal</p>
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                Tersedia
                            </span>
                        </div>
                    </div>

                    <div class="pt-4 space-y-2">
                        <button type="submit"
                                class="w-full px-4 py-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-medium">
                            <i class="fas fa-save mr-2"></i>Simpan Barang
                        </button>
                        <button type="reset"
                                class="w-full px-4 py-2 rounded-lg border hover:bg-slate-100 text-sm">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const uploadBox = document.getElementById('uploadBox');
const photoInput = document.getElementById('photoInput');
const preview = document.getElementById('preview');
const previewContainer = document.getElementById('previewContainer');
const progressContainer = document.getElementById('progressContainer');
const progressBar = document.getElementById('progressBar');
const progressText = document.getElementById('progressText');
const fileSize = document.getElementById('fileSize');

/**
 * Fungsi kompres gambar menggunakan Canvas
 * @param {File} file - File asli dari input
 * @param {number} maxWidth - Lebar maksimal (default: 1200px)
 * @param {number} maxHeight - Tinggi maksimal (default: 1200px)
 * @param {number} quality - Kualitas JPEG (0 - 1, default: 0.8)
 * @param {number} maxSizeMB - Ukuran maksimal dalam MB (default: 2MB)
 */
function compressImage(file, maxWidth = 1200, maxHeight = 1200, quality = 0.8, maxSizeMB = 2) {
    return new Promise((resolve, reject) => {
        // Jika file sudah kecil, langsung resolve
        if (file.size <= maxSizeMB * 1024 * 1024) {
            resolve(file);
            return;
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        
        reader.onload = (e) => {
            const img = new Image();
            img.src = e.target.result;
            
            img.onload = () => {
                // Hitung dimensi baru dengan mempertahankan aspect ratio
                let width = img.width;
                let height = img.height;
                
                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round(height * (maxWidth / width));
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round(width * (maxHeight / height));
                        height = maxHeight;
                    }
                }
                
                // Buat canvas dengan dimensi baru
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                
                // Gambar ulang dengan kualitas lebih baik
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                // Fungsi untuk kompres bertahap sampai ukuran sesuai
                const compressWithQuality = (currentQuality) => {
                    canvas.toBlob((blob) => {
                        if (!blob) {
                            reject(new Error('Gagal mengkompres gambar'));
                            return;
                        }
                        
                        // Jika masih terlalu besar dan quality > 0.3, turunkan quality
                        if (blob.size > maxSizeMB * 1024 * 1024 && currentQuality > 0.3) {
                            compressWithQuality(currentQuality - 0.1);
                        } else {
                            // Buat file baru dari blob
                            const compressedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            
                            // Update progress
                            updateProgress(100, 'Selesai');
                            
                            resolve(compressedFile);
                        }
                    }, 'image/jpeg', currentQuality);
                };
                
                // Mulai kompres dengan quality awal
                compressWithQuality(quality);
            };
            
            img.onerror = (error) => reject(error);
        };
        
        reader.onerror = (error) => reject(error);
    });
}

// Fungsi untuk update progress
function updateProgress(percent, message) {
    progressBar.style.width = percent + '%';
    progressText.textContent = message;
}

// Format bytes ke MB/KB
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Fungsi untuk buka gallery
function openGallery() {
    photoInput.removeAttribute('capture');
    photoInput.click();
}

// Fungsi untuk buka kamera
function openCamera() {
    photoInput.setAttribute('capture', 'environment');
    photoInput.click();
}

// Klik pada box upload (default gallery)
uploadBox.addEventListener('click', () => {
    openGallery();
});

// Saat file dipilih
photoInput.addEventListener('change', async function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Tampilkan progress
    progressContainer.classList.remove('hidden');
    uploadBox.classList.add('hidden');
    document.querySelector('.grid.grid-cols-2.gap-2.text-xs').style.display = 'none';
    
    updateProgress(10, 'Membaca file...');
    
    try {
        // Info ukuran asli
        console.log('Ukuran asli:', formatBytes(file.size));
        
        // Kompres gambar
        updateProgress(30, 'Mengkompres gambar...');
        const compressedFile = await compressImage(file, 1024, 1024, 0.8, 2);
        
        // Info ukuran setelah kompres
        console.log('Ukuran setelah kompres:', formatBytes(compressedFile.size));
        
        // Ganti file di input dengan yang sudah dikompres
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(compressedFile);
        photoInput.files = dataTransfer.files;
        
        // Preview
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.classList.remove('hidden');
            
            // Tampilkan info ukuran
            fileSize.textContent = `Ukuran: ${formatBytes(compressedFile.size)}`;
            
            // Sembunyikan progress
            setTimeout(() => {
                progressContainer.classList.add('hidden');
            }, 500);
        };
        reader.readAsDataURL(compressedFile);
        
    } catch (error) {
        alert('Gagal memproses gambar: ' + error.message);
        resetUpload();
    }
});

function resetUpload() {
    photoInput.value = '';
    preview.src = '';
    previewContainer.classList.add('hidden');
    uploadBox.classList.remove('hidden');
    progressContainer.classList.add('hidden');
    document.querySelector('.grid.grid-cols-2.gap-2.text-xs').style.display = 'grid';
}

function removePhoto() {
    resetUpload();
}

// Drag & drop support
uploadBox.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadBox.classList.add('border-blue-500', 'bg-blue-50');
});

uploadBox.addEventListener('dragleave', () => {
    uploadBox.classList.remove('border-blue-500', 'bg-blue-50');
});

uploadBox.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadBox.classList.remove('border-blue-500', 'bg-blue-50');
    
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        photoInput.files = e.dataTransfer.files;
        photoInput.dispatchEvent(new Event('change'));
    }
});
</script>

<style>
/* Animasi loading */
.fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Hover effect */
.border-dashed:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
}
</style>
@endsection