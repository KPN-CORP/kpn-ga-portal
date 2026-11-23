<!-- Modern Lightbox untuk Preview Foto -->
<div id="imageViewer" class="fixed inset-0 bg-black bg-opacity-95 z-[9999] flex items-center justify-center p-4 hidden" 
     onclick="closeImageViewer()"
     style="backdrop-filter: blur(8px);">
    
    <div class="relative max-w-7xl max-h-full w-full h-full flex items-center justify-center">
        <!-- Tombol Close -->
        <button onclick="closeImageViewer()" 
                class="absolute top-4 right-4 md:top-6 md:right-6 z-50 w-12 h-12 rounded-full bg-black bg-opacity-50 hover:bg-opacity-70 text-white flex items-center justify-center transition-all duration-300 hover:scale-110 border border-white border-opacity-20">
            <i class="fas fa-times text-xl"></i>
        </button>
        
        <!-- Tombol Download -->
        <a id="downloadBtn" 
           href="#" 
           download
           class="absolute top-4 right-20 md:top-6 md:right-24 z-50 w-12 h-12 rounded-full bg-black bg-opacity-50 hover:bg-opacity-70 text-white flex items-center justify-center transition-all duration-300 hover:scale-110 border border-white border-opacity-20"
           onclick="event.stopPropagation();">
            <i class="fas fa-download text-xl"></i>
        </a>
        
        <!-- Informasi File -->
        <div id="imageInfo" 
             class="absolute bottom-4 left-4 right-4 md:bottom-6 md:left-auto md:right-auto md:bottom-6 z-50 bg-black bg-opacity-75 text-white px-6 py-3 rounded-full text-sm border border-white border-opacity-20 backdrop-blur-md max-w-2xl mx-auto">
            <span id="imageName" class="font-medium"></span>
            <span id="imageSize" class="ml-2 text-gray-300"></span>
        </div>
        
        <!-- Navigasi Kiri -->
        <button id="prevImageBtn" 
                onclick="prevImage(event)" 
                class="absolute left-2 md:left-6 z-50 w-12 h-12 rounded-full bg-black bg-opacity-50 hover:bg-opacity-70 text-white flex items-center justify-center transition-all duration-300 hover:scale-110 border border-white border-opacity-20 disabled:opacity-30 disabled:cursor-not-allowed"
                style="display: none;">
            <i class="fas fa-chevron-left text-xl"></i>
        </button>
        
        <!-- Navigasi Kanan -->
        <button id="nextImageBtn" 
                onclick="nextImage(event)" 
                class="absolute right-2 md:right-6 z-50 w-12 h-12 rounded-full bg-black bg-opacity-50 hover:bg-opacity-70 text-white flex items-center justify-center transition-all duration-300 hover:scale-110 border border-white border-opacity-20 disabled:opacity-30 disabled:cursor-not-allowed"
                style="display: none;">
            <i class="fas fa-chevron-right text-xl"></i>
        </button>
        
        <!-- Gambar Utama -->
        <div class="relative flex items-center justify-center w-full h-full" onclick="event.stopPropagation()">
            <img id="viewerImage" 
                 src="" 
                 alt="Preview" 
                 class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl transition-all duration-300 cursor-zoom-in"
                 onclick="toggleZoom(event)">
            
            <!-- Loading Spinner -->
            <div id="imageLoader" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-lg hidden">
                <div class="w-16 h-16 border-4 border-white border-t-transparent rounded-full animate-spin"></div>
            </div>
        </div>
        
        <!-- Counter Image -->
        <div id="imageCounter" 
             class="absolute top-4 left-4 md:top-6 md:left-6 z-50 bg-black bg-opacity-50 text-white px-4 py-2 rounded-full text-sm border border-white border-opacity-20"
             style="display: none;">
            <span id="currentImageIndex">1</span> / <span id="totalImages">1</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ==================== LIGHTBOX CONFIGURATION ====================
let currentImageId = null;
let currentImages = [];
let currentIndex = 0;
let isZoomed = false;

// View Image Function
window.viewImage = function(imageId) {
    console.log('Opening image:', imageId);
    
    // Cari semua gambar di halaman
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
    imageLoader.classList.remove('hidden');
    viewerImage.style.opacity = '0';
    
    // Reset zoom
    isZoomed = false;
    viewerImage.classList.remove('scale-150', 'cursor-zoom-out');
    viewerImage.classList.add('cursor-zoom-in');
    
    // Generate URL
    const timestamp = new Date().getTime();
    const previewUrl = `{{ route('help.proses.lampiran.preview', ['lampiran' => ':id']) }}`.replace(':id', imageId) + `?t=${timestamp}`;
    const downloadUrl = `{{ route('help.proses.lampiran.download', ['lampiran' => ':id']) }}`.replace(':id', imageId);
    
    // Set download link
    downloadBtn.href = downloadUrl;
    
    // Load image
    viewerImage.onload = function() {
        imageLoader.classList.add('hidden');
        viewerImage.style.opacity = '1';
        
        // Get image info from alt or create generic
        const imgElement = document.querySelector(`[onclick*="viewImage('${imageId}')"] img`);
        if (imgElement) {
            imageName.textContent = imgElement.alt || 'Image ' + imageId;
        } else {
            imageName.textContent = 'Foto ' + imageId;
        }
        
        // Get file size from nearby element
        const sizeElement = document.querySelector(`[onclick*="viewImage('${imageId}')"] .text-xs.text-gray-500`);
        if (sizeElement) {
            imageSize.textContent = '• ' + sizeElement.textContent.trim();
        } else {
            imageSize.textContent = '';
        }
    };
    
    viewerImage.onerror = function() {
        imageLoader.classList.add('hidden');
        viewerImage.src = 'https://via.placeholder.com/800x600?text=Image+Not+Found';
        imageName.textContent = 'Image not found';
        imageSize.textContent = '';
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
        prevBtn.style.display = 'flex';
        nextBtn.style.display = 'flex';
        counter.style.display = 'block';
        
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex === currentImages.length - 1;
        
        currentEl.textContent = currentIndex + 1;
        totalEl.textContent = currentImages.length;
    } else {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
        counter.style.display = 'none';
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
    if (viewer.classList.contains('hidden')) return;
    
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
            const img = document.getElementById('viewerImage');
            if (!isZoomed) toggleZoom(e);
            break;
        case '-':
            e.preventDefault();
            const imgZoom = document.getElementById('viewerImage');
            if (isZoomed) toggleZoom(e);
            break;
    }
});

// Touch Swipe Support
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

// Prevent background scroll when lightbox open
document.addEventListener('DOMContentLoaded', function() {
    const viewer = document.getElementById('imageViewer');
    if (viewer) {
        viewer.addEventListener('wheel', function(e) {
            e.preventDefault();
        }, { passive: false });
    }
});
</script>
@endpush

@push('styles')
<style>
    /* Lightbox Animations */
    #imageViewer {
        transition: opacity 0.3s ease;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    #viewerImage {
        transition: opacity 0.3s ease, transform 0.3s ease;
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
    
    /* Button Hover Effects */
    #imageViewer button {
        backdrop-filter: blur(4px);
        transition: all 0.2s ease;
    }
    
    #imageViewer button:hover {
        background-color: rgba(255,255,255,0.2);
        transform: scale(1.1);
    }
    
    #imageViewer button:active {
        transform: scale(0.95);
    }
    
    /* Custom Scrollbar for Chat */
    #chatContainer::-webkit-scrollbar {
        width: 6px;
    }
    
    #chatContainer::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    #chatContainer::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    #chatContainer::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    /* Horizontal Scroll for Photo Gallery */
    .overflow-x-auto {
        scrollbar-width: thin;
        scrollbar-color: #c1c1c1 #f1f1f1;
    }
    
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
    
    /* Photo Thumbnail Hover Effect */
    .aspect-square {
        transition: all 0.2s ease;
    }
    
    .aspect-square:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    /* Badge Styling */
    .badge {
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255,255,255,0.2);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        #viewerImage.scale-150 {
            transform: scale(1.2);
        }
        
        #imageViewer button {
            width: 40px;
            height: 40px;
        }
    }
</style>
@endpush