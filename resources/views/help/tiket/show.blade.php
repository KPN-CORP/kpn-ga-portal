@extends('layouts.app-sidebar')

@section('content')
<div class="p-4 md:p-6">
    <!-- Header Tiket -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-800">{{ $tiket->judul }}</h2>
                    
                    <!-- Status Badge -->
                    @php
                        $statusColors = [
                            'OPEN' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'ON_PROCESS' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'WAITING' => 'bg-orange-100 text-orange-800 border-orange-200',
                            'DONE' => 'bg-green-100 text-green-800 border-green-200',
                            'CLOSED' => 'bg-gray-100 text-gray-800 border-gray-200'
                        ];
                        $statusLabels = [
                            'OPEN'       => 'Menunggu Penanganan',
                            'ON_PROCESS' => 'Sedang Diproses',
                            'WAITING'    => 'Dalam Proses Pengadaan',
                            'DONE'       => 'Selesai',
                            'CLOSED'     => 'Ditutup',
                        ];
                        $priorityColors = [
                            'URGENT' => 'bg-red-100 text-red-800 border-red-200',
                            'HIGH' => 'bg-orange-100 text-orange-800 border-orange-200',
                            'MEDIUM' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'LOW' => 'bg-gray-100 text-gray-800 border-gray-200'
                        ];
                    @endphp
                    
                    <span class="px-2 py-1 text-xs font-medium rounded-full border {{ $statusColors[$tiket->status] }}">
                        {{ $statusLabels[$tiket->status] ?? $tiket->status }}
                    </span>
                    
                    <span class="px-2 py-1 text-xs font-medium rounded-full border {{ $priorityColors[$tiket->prioritas] }}">
                        {{ $tiket->prioritas }}
                    </span>
                </div>
                
                <div class="text-sm text-gray-600">
                    <span class="text-gray-500">#{{ $tiket->nomor_tiket }}</span>
                    <span class="mx-2">•</span>
                    <span class="text-gray-500">{{ $tiket->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
        
        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Pelapor -->
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Dilaporkan Oleh</h3>
                <div class="flex items-center">
                    @php
                        $pelaporName = $tiket->pelapor->user->name ?? $tiket->pelapor->nama ?? 'User';
                        $pelaporInitial = substr($pelaporName, 0, 1);
                    @endphp
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center font-medium text-blue-700 mr-2">
                        {{ $pelaporInitial }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 text-sm">{{ $pelaporName }}</p>
                        <p class="text-xs text-gray-500">{{ $tiket->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Kategori -->
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Kategori</h3>
                <div class="flex items-center">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center mr-2">
                        <i class="fas fa-tag text-blue-600 text-sm"></i>
                    </div>
                    <p class="font-medium text-gray-900 text-sm">{{ $tiket->kategori->nama ?? '-' }}</p>
                </div>
            </div>
            
            <!-- Penanggung Jawab -->
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Penanggung Jawab</h3>
                <div class="flex items-center">
                    @if($tiket->ditugaskanKe)
                        @php
                            $pjName = $tiket->ditugaskanKe->user->name ?? $tiket->ditugaskanKe->nama ?? 'Staff GA';
                            $pjInitial = substr($pjName, 0, 1);
                        @endphp
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center font-medium text-green-700 mr-2">
                            {{ $pjInitial }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 text-sm">{{ $pjName }}</p>
                            @if($tiket->diproses_pada)
                            <p class="text-xs text-gray-500">{{ $tiket->diproses_pada->format('d/m/Y') }}</p>
                            @endif
                        </div>
                    @else
                        <p class="text-gray-500 text-sm italic">Belum ditugaskan</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Konten Utama -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Deskripsi Masalah -->
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-file-alt text-blue-500 mr-2"></i> Deskripsi Masalah
                </h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-700 text-sm whitespace-pre-line">{{ $tiket->deskripsi }}</p>
                </div>
            </div>
            
            <!-- Diskusi Tiket -->
            @include('help.partials.diskusi', [
                'tiket' => $tiket,
                'userRole' => 'pelapor',
                'showInput' => $tiket->status !== 'CLOSED'
            ])
            
            <!-- Lampiran -->
            @include('help.partials.lampiran', [
                'lampiran' => $lampiran,
                'tiket' => $tiket,
                'userRole' => 'pelapor'
            ])
        </div>
        
        <!-- Kolom Kanan (1/3) -->
        <div class="space-y-6">
            <!-- Status Tiket -->
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i> Status Tiket
                </h3>
                <div class="space-y-2">
                    @if($tiket->status === 'OPEN')
                    <div class="flex items-start">
                        <i class="fas fa-clock text-yellow-500 mt-0.5 mr-2 text-sm"></i>
                        <div>
                            <p class="text-sm text-gray-700">Menunggu penugasan ke petugas GA.</p>
                        </div>
                    </div>
                    @elseif($tiket->status === 'ON_PROCESS')
                    <div class="flex items-start">
                        <i class="fas fa-cog text-blue-500 mt-0.5 mr-2 text-sm"></i>
                        <div>
                            <p class="text-sm text-gray-700">Sedang diproses oleh petugas GA.</p>
                            @if($tiket->diproses_pada)
                            <p class="text-xs text-gray-500 mt-0.5">Sejak: {{ $tiket->diproses_pada->format('d/m H:i') }}</p>
                            @endif
                        </div>
                    </div>
                    @elseif($tiket->status === 'WAITING')
                    <div class="flex items-start">
                        <i class="fas fa-hourglass-half text-orange-500 mt-0.5 mr-2 text-sm"></i>
                        <div>
                            <p class="text-sm text-gray-700">Menunggu</p>
                            <p class="text-sm font-medium text-orange-600 mt-1">Silakan berikan respons atau info tambahan.</p>
                        </div>
                    </div>
                    @elseif($tiket->status === 'DONE')
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-0.5 mr-2 text-sm"></i>
                        <div>
                            <p class="text-sm text-gray-700">Tiket telah diselesaikan. <br>Jika dalam 1 hari kerja tidak ada tanggapan atau komplain, tiket akan close otomatis (Sabtu–Minggu tidak dihitung).</p>
                            @if($tiket->diselesaikan_pada)
                            <p class="text-xs text-gray-500 mt-0.5">{{ $tiket->diselesaikan_pada->format('d/m H:i') }}</p>
                            @endif
                        </div>
                    </div>
                    @elseif($tiket->status === 'CLOSED')
                    <div class="flex items-start">
                        <i class="fas fa-lock text-gray-500 mt-0.5 mr-2 text-sm"></i>
                        <div>
                            <p class="text-sm text-gray-700">Tiket telah ditutup.</p>
                            @if($tiket->ditutup_pada)
                            <p class="text-xs text-gray-500 mt-0.5">{{ $tiket->ditutup_pada->format('d/m H:i') }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Timeline Status -->
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-history text-gray-500 mr-2"></i> Timeline Status
                </h3>
                <div class="space-y-3">
                    @forelse($tiket->logStatus as $log)
                    @php
                        $logUserName = $log->pengguna->user->name ?? $log->pengguna->nama ?? 'System';
                    @endphp
                    <div class="relative pl-5 pb-3 last:pb-0">
                        @if(!$loop->last)
                            <div class="absolute left-2 top-3 bottom-0 w-0.5 bg-gray-200"></div>
                        @endif
                        
                        <div class="absolute left-0 top-1 w-4 h-4 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-circle text-blue-600 text-xs"></i>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-start">
                                <p class="font-medium text-gray-900 text-sm">
                                    {{ $statusLabels[$log->status_baru] ?? $log->status_baru }}
                                </p>
                                <span class="text-xs text-gray-500" title="{{ $log->created_at->format('d/m/Y H:i') }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 mt-0.5">Oleh: {{ $logUserName }}</p>
                            @if($log->catatan)
                            <div class="mt-1 p-2 bg-gray-50 rounded text-xs text-gray-600">
                                {{ $log->catatan }}
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 text-center py-2">Belum ada riwayat status</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Viewer Modal -->
@include('help.partials.image-viewer')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ==================== AUTO SCROLL CHAT ====================
    const chatContainer = document.getElementById('chatContainer');
    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    
    // ==================== AUTO RESIZE TEXTAREA ====================
    const chatInput = document.getElementById('chatInput');
    if (chatInput) {
        chatInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 80) + 'px';
        });
        
        chatInput.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim() !== '') {
                    document.getElementById('chatForm').submit();
                }
            }
        });
    }
    
    // ==================== FILE UPLOAD INDICATOR ====================
    const chatFileInput = document.getElementById('chat-lampiran');
    const fileCountSpan = document.getElementById('file-count');
    if (chatFileInput) {
        chatFileInput.addEventListener('change', function() {
            const count = this.files.length;
            if (count > 0) {
                fileCountSpan.textContent = count;
                fileCountSpan.classList.remove('hidden');
            } else {
                fileCountSpan.classList.add('hidden');
            }
        });
    }
    
    // ==================== LIGHTBOX PREMIUM ====================
    let currentImageId = null;
    let currentImages = [];
    let currentIndex = 0;
    let isZoomed = false;

    // View Image Function
    window.viewImage = function(imageId) {
        console.log('Opening image:', imageId);
        
        // Kumpulkan semua ID gambar di halaman
        const images = [];
        document.querySelectorAll('[onclick*="viewImage"]').forEach(el => {
            const match = el.getAttribute('onclick').match(/viewImage\(['"](.+)['"]\)/);
            if (match && match[1]) {
                images.push(match[1]);
            }
        });
        
        // Hapus duplikat
        currentImages = [...new Set(images)];
        currentIndex = currentImages.indexOf(imageId.toString());
        
        if (currentIndex === -1) {
            currentImages = [imageId];
            currentIndex = 0;
        }
        
        currentImageId = imageId;
        loadImage(imageId);
        
        // Tampilkan lightbox
        const viewer = document.getElementById('imageViewer');
        viewer.classList.remove('hidden');
        viewer.style.opacity = '0';
        
        setTimeout(() => {
            viewer.style.opacity = '1';
        }, 10);
        
        document.body.classList.add('overflow-hidden');
        
        // Update navigasi
        updateNavigation();
    };

    // Load Image
    function loadImage(imageId) {
        const viewerImage = document.getElementById('viewerImage');
        const imageLoader = document.getElementById('imageLoader');
        const imageName = document.getElementById('imageName');
        const imageSize = document.getElementById('imageSize');
        const downloadBtn = document.getElementById('downloadBtn');
        
        // Show loader
        if (imageLoader) imageLoader.classList.remove('hidden');
        viewerImage.style.opacity = '0';
        
        // Reset zoom
        isZoomed = false;
        viewerImage.classList.remove('scale-150', 'cursor-zoom-out');
        viewerImage.classList.add('cursor-zoom-in');
        
        // Generate URL
        const timestamp = new Date().getTime();
        const previewUrl = "{{ route('help.tiket.lampiran.preview', ['lampiran' => ':id']) }}".replace(':id', imageId) + `?t=${timestamp}`;
        const downloadUrl = "{{ route('help.tiket.lampiran.download', ['lampiran' => ':id']) }}".replace(':id', imageId);
        
        // Set download link
        if (downloadBtn) downloadBtn.href = downloadUrl;
        
        // Load image
        viewerImage.onload = function() {
            if (imageLoader) imageLoader.classList.add('hidden');
            viewerImage.style.opacity = '1';
            
            // Get image info
            const imgElement = document.querySelector(`[onclick*="viewImage('${imageId}')"] img`);
            if (imgElement) {
                if (imageName) imageName.textContent = imgElement.alt || 'Foto ' + imageId;
                
                // Get file size
                const sizeElement = imgElement.closest('.flex-shrink-0')?.nextElementSibling?.querySelector('.text-xs.text-gray-500');
                if (sizeElement && imageSize) {
                    imageSize.textContent = '• ' + sizeElement.textContent.trim();
                }
            }
        };
        
        viewerImage.onerror = function() {
            if (imageLoader) imageLoader.classList.add('hidden');
            viewerImage.src = 'https://via.placeholder.com/800x600?text=Image+Not+Found';
            if (imageName) imageName.textContent = 'Image not found';
            if (imageSize) imageSize.textContent = '';
        };
        
        viewerImage.src = previewUrl;
    }

    // Navigasi Previous
    window.prevImage = function(event) {
        if (event) event.stopPropagation();
        if (currentImages.length > 1 && currentIndex > 0) {
            currentIndex--;
            currentImageId = currentImages[currentIndex];
            loadImage(currentImageId);
            updateNavigation();
        }
    };

    // Navigasi Next
    window.nextImage = function(event) {
        if (event) event.stopPropagation();
        if (currentImages.length > 1 && currentIndex < currentImages.length - 1) {
            currentIndex++;
            currentImageId = currentImages[currentIndex];
            loadImage(currentImageId);
            updateNavigation();
        }
    };

    // Update Navigation Buttons
    function updateNavigation() {
        const prevBtn = document.getElementById('prevImageBtn');
        const nextBtn = document.getElementById('nextImageBtn');
        const counter = document.getElementById('imageCounter');
        const currentEl = document.getElementById('currentImageIndex');
        const totalEl = document.getElementById('totalImages');
        
        if (currentImages.length > 1) {
            if (prevBtn) {
                prevBtn.style.display = 'flex';
                prevBtn.disabled = currentIndex === 0;
            }
            if (nextBtn) {
                nextBtn.style.display = 'flex';
                nextBtn.disabled = currentIndex === currentImages.length - 1;
            }
            if (counter) counter.style.display = 'block';
            if (currentEl) currentEl.textContent = currentIndex + 1;
            if (totalEl) totalEl.textContent = currentImages.length;
        } else {
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
            if (counter) counter.style.display = 'none';
        }
    }

    // Toggle Zoom
    window.toggleZoom = function(event) {
        event.stopPropagation();
        const img = document.getElementById('viewerImage');
        
        if (isZoomed) {
            img.classList.remove('scale-150');
            img.classList.add('cursor-zoom-in');
            img.classList.remove('cursor-zoom-out');
        } else {
            img.classList.add('scale-150');
            img.classList.remove('cursor-zoom-in');
            img.classList.add('cursor-zoom-out');
        }
        
        isZoomed = !isZoomed;
    };

    // Close Viewer
    window.closeImageViewer = function() {
        const viewer = document.getElementById('imageViewer');
        const viewerImage = document.getElementById('viewerImage');
        
        viewer.style.opacity = '0';
        viewerImage.style.opacity = '0';
        
        setTimeout(() => {
            viewer.classList.add('hidden');
            viewerImage.src = '';
            viewerImage.classList.remove('scale-150');
            isZoomed = false;
            document.body.classList.remove('overflow-hidden');
        }, 300);
    };

    // Keyboard Navigation
    document.addEventListener('keydown', function(e) {
        const viewer = document.getElementById('imageViewer');
        if (!viewer || viewer.classList.contains('hidden')) return;
        
        switch(e.key) {
            case 'Escape':
                closeImageViewer();
                break;
            case 'ArrowLeft':
                prevImage(e);
                break;
            case 'ArrowRight':
                nextImage(e);
                break;
            case '+':
            case '=':
                e.preventDefault();
                if (!isZoomed) toggleZoom(e);
                break;
            case '-':
                e.preventDefault();
                if (isZoomed) toggleZoom(e);
                break;
        }
    });

    // Touch Swipe Support untuk Mobile
    let touchStartX = 0;
    let touchEndX = 0;

    document.getElementById('viewerImage')?.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });

    document.getElementById('viewerImage')?.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });

    function handleSwipe() {
        const swipeThreshold = 50;
        if (touchEndX < touchStartX - swipeThreshold) {
            nextImage();
        }
        if (touchEndX > touchStartX + swipeThreshold) {
            prevImage();
        }
    }

    // Prevent background scroll saat lightbox open
    document.getElementById('viewerImage')?.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Notification handler
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            html: `{!! session('success') !!}`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#22c55e',
        });
    @endif
    
    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            html: `{!! session('error') !!}`,
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#ef4444',
        });
    @endif
    
    @if(session('warning'))
        Swal.fire({
            icon: 'warning',
            title: 'Peringatan',
            html: `{!! session('warning') !!}`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#f59e0b',
        });
    @endif
    
    @if(session('info'))
        Swal.fire({
            icon: 'info',
            title: 'Informasi',
            html: `{!! session('info') !!}`,
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6',
        });
    @endif
</script>
@endpush

@push('styles')
<style>
    /* ==================== CUSTOM SCROLLBAR ==================== */
    #chatContainer::-webkit-scrollbar {
        width: 4px;
    }
    #chatContainer::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 2px;
    }
    #chatContainer::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 2px;
    }
    #chatContainer::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    /* ==================== HORIZONTAL SCROLL GALLERY ==================== */
    .overflow-x-auto::-webkit-scrollbar {
        height: 6px;
    }
    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    /* ==================== LIGHTBOX PREMIUM ==================== */
    #imageViewer {
        transition: opacity 0.3s ease;
        cursor: pointer;
        backdrop-filter: blur(8px);
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    #viewerImage {
        transition: opacity 0.3s ease, transform 0.3s ease;
        cursor: default;
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
    }
    
    #viewerImage.scale-150 {
        transform: scale(1.5);
        cursor: zoom-out;
    }
    
    /* Loading Spinner */
    .animate-spin {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* Lightbox Buttons */
    #imageViewer button {
        backdrop-filter: blur(4px);
        transition: all 0.2s ease;
        border: 1px solid rgba(255,255,255,0.2);
    }
    
    #imageViewer button:hover:not(:disabled) {
        background-color: rgba(255,255,255,0.2);
        transform: scale(1.1);
    }
    
    #imageViewer button:active:not(:disabled) {
        transform: scale(0.95);
    }
    
    #imageViewer button:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    
    /* Image Info Panel */
    #imageInfo {
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.2);
        background: rgba(0,0,0,0.75);
    }
    
    /* ==================== PHOTO THUMBNAIL ==================== */
    .aspect-square {
        transition: all 0.2s ease;
        border: 2px solid transparent;
    }
    
    .aspect-square:hover {
        transform: translateY(-2px);
        border-color: #3b82f6;
        box-shadow: 0 8px 16px rgba(59,130,246,0.2);
    }
    
    .group:hover .group-hover\:scale-110 {
        transform: scale(1.1);
    }
    
    .group:hover .group-hover\:opacity-100 {
        opacity: 1;
    }
    
    .group:hover .group-hover\:bg-opacity-30 {
        background-color: rgba(0,0,0,0.3);
    }
    
    /* Badge Styling */
    .badge {
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255,255,255,0.2);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* ==================== RESPONSIVE ==================== */
    @media (max-width: 768px) {
        #viewerImage.scale-150 {
            transform: scale(1.2);
        }
        
        #imageViewer button {
            width: 40px;
            height: 40px;
        }
        
        .w-24.h-24 {
            width: 80px;
            height: 80px;
        }
    }
    
    /* ==================== SWEET ALERT ==================== */
    .swal2-popup {
        border-radius: 12px !important;
        padding: 1.5rem !important;
    }
    .swal2-title {
        font-size: 1.25rem !important;
        font-weight: 600 !important;
    }
    .swal2-html-container {
        font-size: 0.875rem !important;
    }
    .swal2-confirm {
        border-radius: 8px !important;
        padding: 0.5rem 1.5rem !important;
        font-weight: 500 !important;
    }
</style>
@endpush
@endsection