@extends('layouts.app-sidebar')

@section('content')
<div class="p-6 bg-slate-50 min-h-screen space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Serahkan Barang</h1>
            <p class="text-sm text-slate-500">Form penyerahan barang kepada penerima</p>
        </div>
        <a href="{{ route('founddesk.index') }}"
           class="px-4 py-2 rounded-lg border bg-white hover:bg-slate-100 text-sm">
            ← Kembali
        </a>
    </div>

    <form action="{{ route('founddesk.disposition.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- MAIN FORM --}}
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 space-y-6">

                <div class="bg-blue-50 rounded-lg p-4 flex items-center gap-4">
                    <div class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-hand-holding-heart text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs text-blue-600">No. Transaksi</p>
                        <p class="font-semibold text-lg text-blue-700">{{ $dispositionNo }}</p>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium">Pilih Barang <span class="text-red-500">*</span></label>
                    <select name="item_id" id="itemSelect"
                            class="w-full mt-1 px-4 py-2 rounded-lg border @error('item_id') border-red-500 @enderror">
                        <option value="">Pilih barang yang akan diserahkan</option>
                        @foreach($items as $itemOption)
                            <option value="{{ $itemOption->id }}"
                                    data-stock="{{ $itemOption->current_stock }}"
                                    data-unit="{{ $itemOption->unit }}"
                                    data-code="{{ $itemOption->item_code }}"
                                    {{ (old('item_id', $item->id ?? '') == $itemOption->id) ? 'selected' : '' }}>
                                [{{ $itemOption->item_code }}] {{ $itemOption->name }} ({{ $itemOption->current_stock }} {{ $itemOption->unit }})
                            </option>
                        @endforeach
                    </select>
                    @error('item_id')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="itemInfo" class="hidden bg-slate-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-slate-500">Kode Barang</p>
                            <p id="infoCode" class="font-medium">-</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Stok Tersedia</p>
                            <p id="infoStock" class="font-medium">-</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium">Jumlah <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity"
                               value="{{ old('quantity', 1) }}"
                               min="1"
                               id="quantity"
                               class="w-full mt-1 px-4 py-2 rounded-lg border @error('quantity') border-red-500 @enderror">
                        @error('quantity')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium">Tanggal Penyerahan <span class="text-red-500">*</span></label>
                        <input type="date" name="disposition_date"
                               value="{{ old('disposition_date', date('Y-m-d')) }}"
                               class="w-full mt-1 px-4 py-2 rounded-lg border">
                        @error('disposition_date')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium">Nama Penerima <span class="text-red-500">*</span></label>
                    <input type="text" name="recipient_name"
                           value="{{ old('recipient_name') }}"
                           class="w-full mt-1 px-4 py-2 rounded-lg border @error('recipient_name') border-red-500 @enderror"
                           placeholder="Nama lengkap penerima">
                    @error('recipient_name')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium">No. Identitas</label>
                        <input type="text" name="recipient_id"
                               value="{{ old('recipient_id') }}"
                               class="w-full mt-1 px-4 py-2 rounded-lg border"
                               placeholder="Contoh: 0203040001">
                    </div>

                    <div>
                        <label class="text-sm font-medium">No. Kontak</label>
                        <input type="text" name="recipient_contact"
                               value="{{ old('recipient_contact') }}"
                               class="w-full mt-1 px-4 py-2 rounded-lg border"
                               placeholder="Contoh: 08123456789">
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium">Catatan</label>
                    <textarea name="notes" rows="3"
                              class="w-full mt-1 px-4 py-2 rounded-lg border"
                              placeholder="Catatan tambahan jika diperlukan">{{ old('notes') }}</textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit"
                            class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        <i class="fas fa-hand-holding-heart mr-2"></i>Serahkan Barang
                    </button>
                    <button type="reset"
                            class="px-6 py-2 rounded-lg border hover:bg-slate-100">
                        Reset
                    </button>
                </div>
            </div>

            {{-- SIDEBAR FOTO --}}
            <div class="space-y-6">
                {{-- Foto Penyerahan dengan Kamera & Kompresi --}}
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <label class="text-sm font-medium block mb-3">Foto Penyerahan Barang</label>
                    
                    <div class="text-center">
                        <input type="file" name="handover_photo" id="handoverInput" class="hidden" accept="image/*">
                        
                        <div id="handoverBox"
                             class="border-2 border-dashed rounded-xl p-4 cursor-pointer hover:border-blue-500 transition">
                            <i class="fas fa-camera text-2xl text-slate-300 mb-2"></i>
                            <p class="text-sm text-slate-500">Klik untuk upload foto penyerahan</p>
                            <p class="text-xs text-slate-400 mt-1">Format: JPG, PNG (max 10MB akan dikompres)</p>
                        </div>
                        
                        {{-- Tombol Pilihan Gallery atau Kamera --}}
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <button type="button" onclick="openGallery('handover')" 
                                    class="px-3 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100">
                                <i class="fas fa-images mr-1"></i> Gallery
                            </button>
                            <button type="button" onclick="openCamera('handover')" 
                                    class="px-3 py-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100">
                                <i class="fas fa-camera mr-1"></i> Kamera
                            </button>
                        </div>
                        
                        {{-- Progress Bar --}}
                        <div id="handoverProgressContainer" class="hidden mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div id="handoverProgressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                            </div>
                            <p id="handoverProgressText" class="text-xs text-gray-500 mt-1">Memproses...</p>
                        </div>
                        
                        <div id="handoverPreview" class="hidden mt-4">
                            <img id="handoverImg" class="rounded-lg object-cover max-h-40 w-full">
                            <div class="flex items-center justify-between mt-2 text-xs">
                                <span id="handoverFileSize" class="text-gray-500"></span>
                                <button type="button" onclick="removeHandover()"
                                        class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times mr-1"></i>Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Informasi --}}
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center gap-3 text-sm text-slate-600">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        <p>Pastikan data penerima dan foto sudah sesuai sebelum menyimpan.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// ==================== KONFIGURASI ====================
const MAX_WIDTH = 1024;
const MAX_HEIGHT = 1024;
const QUALITY = 0.8;
const MAX_SIZE_MB = 2;

// ==================== FUNGSI KOMPRESI ====================
/**
 * Kompres gambar menggunakan Canvas
 */
function compressImage(file, maxWidth = MAX_WIDTH, maxHeight = MAX_HEIGHT, quality = QUALITY, maxSizeMB = MAX_SIZE_MB) {
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
                
                // Fungsi untuk kompres bertahap
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

// ==================== FUNGSI FORMAT BYTES ====================
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// ==================== FUNGSI UPDATE PROGRESS ====================
function updateProgress(containerId, barId, textId, percent, message) {
    const bar = document.getElementById(barId);
    const text = document.getElementById(textId);
    
    if (bar) bar.style.width = percent + '%';
    if (text) text.textContent = message;
}

// ==================== FUNGSI HANDOVER PHOTO ====================
const handoverInput = document.getElementById('handoverInput');
const handoverBox = document.getElementById('handoverBox');
const handoverPreview = document.getElementById('handoverPreview');
const handoverImg = document.getElementById('handoverImg');
const handoverFileSize = document.getElementById('handoverFileSize');
const handoverProgressContainer = document.getElementById('handoverProgressContainer');

// Fungsi untuk buka gallery
function openGallery(type) {
    if (type === 'handover') {
        handoverInput.removeAttribute('capture');
        handoverInput.click();
    }
}

// Fungsi untuk buka kamera
function openCamera(type) {
    if (type === 'handover') {
        handoverInput.setAttribute('capture', 'environment');
        handoverInput.click();
    }
}

// Klik pada box upload (default gallery)
handoverBox.addEventListener('click', () => {
    openGallery('handover');
});

// Saat file dipilih
handoverInput.addEventListener('change', async function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Tampilkan progress
    handoverProgressContainer.classList.remove('hidden');
    handoverBox.classList.add('hidden');
    document.querySelector('#handoverBox + .grid')?.classList.add('hidden');
    
    updateProgress('handoverProgressContainer', 'handoverProgressBar', 'handoverProgressText', 10, 'Membaca file...');
    
    try {
        // Info ukuran asli
        console.log('Ukuran asli:', formatBytes(file.size));
        
        // Kompres gambar
        updateProgress('handoverProgressContainer', 'handoverProgressBar', 'handoverProgressText', 30, 'Mengkompres gambar...');
        const compressedFile = await compressImage(file, MAX_WIDTH, MAX_HEIGHT, QUALITY, MAX_SIZE_MB);
        
        // Info ukuran setelah kompres
        console.log('Ukuran setelah kompres:', formatBytes(compressedFile.size));
        
        // Ganti file di input dengan yang sudah dikompres
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(compressedFile);
        handoverInput.files = dataTransfer.files;
        
        // Preview
        const reader = new FileReader();
        reader.onload = function(e) {
            handoverImg.src = e.target.result;
            handoverPreview.classList.remove('hidden');
            
            // Tampilkan info ukuran
            handoverFileSize.textContent = `Ukuran: ${formatBytes(compressedFile.size)}`;
            
            // Sembunyikan progress
            setTimeout(() => {
                handoverProgressContainer.classList.add('hidden');
            }, 500);
        };
        reader.readAsDataURL(compressedFile);
        
    } catch (error) {
        alert('Gagal memproses gambar: ' + error.message);
        resetHandover();
    }
});

function resetHandover() {
    handoverInput.value = '';
    handoverImg.src = '';
    handoverPreview.classList.add('hidden');
    handoverBox.classList.remove('hidden');
    handoverProgressContainer.classList.add('hidden');
    document.querySelector('#handoverBox + .grid')?.classList.remove('hidden');
}

function removeHandover() {
    resetHandover();
}

// Drag & drop support
handoverBox.addEventListener('dragover', (e) => {
    e.preventDefault();
    handoverBox.classList.add('border-blue-500', 'bg-blue-50');
});

handoverBox.addEventListener('dragleave', () => {
    handoverBox.classList.remove('border-blue-500', 'bg-blue-50');
});

handoverBox.addEventListener('drop', (e) => {
    e.preventDefault();
    handoverBox.classList.remove('border-blue-500', 'bg-blue-50');
    
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        handoverInput.files = e.dataTransfer.files;
        handoverInput.dispatchEvent(new Event('change'));
    }
});

// ==================== ITEM SELECTION ====================
const itemSelect = document.getElementById('itemSelect');
const itemInfo = document.getElementById('itemInfo');
const infoCode = document.getElementById('infoCode');
const infoStock = document.getElementById('infoStock');
const quantity = document.getElementById('quantity');

itemSelect.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    
    if (this.value) {
        const stock = selected.dataset.stock;
        const unit = selected.dataset.unit;
        const code = selected.dataset.code;
        
        infoCode.textContent = code;
        infoStock.textContent = stock + ' ' + unit;
        itemInfo.classList.remove('hidden');
        
        quantity.max = stock;
    } else {
        itemInfo.classList.add('hidden');
    }
});

// Trigger on page load if item is preselected
@if(isset($item) && $item)
    setTimeout(function() {
        itemSelect.dispatchEvent(new Event('change'));
    }, 100);
@endif

// Validate quantity before submit
document.querySelector('form').addEventListener('submit', function(e) {
    const selected = itemSelect.options[itemSelect.selectedIndex];
    if (itemSelect.value) {
        const maxStock = parseInt(selected.dataset.stock);
        const qty = parseInt(quantity.value);
        
        if (qty > maxStock) {
            e.preventDefault();
            alert('Jumlah melebihi stok tersedia! Stok maksimal: ' + maxStock);
        }
    }
    
    // Validasi foto penyerahan
    if (!handoverInput.files.length) {
        e.preventDefault();
        alert('Foto penyerahan wajib diupload!');
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

/* Progress bar transition */
#handoverProgressBar {
    transition: width 0.3s ease;
}
</style>
@endsection