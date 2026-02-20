<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800 flex items-center">
                <i class="fas fa-comments text-blue-500 mr-2"></i> Diskusi Tiket
            </h3>
            <span class="text-xs text-gray-500">{{ $tiket->komentar->count() }} pesan</span>
        </div>
    </div>
    
    <div class="p-4 bg-gray-50" style="height: 400px; overflow-y: auto;" id="chatContainer">
        @forelse($tiket->komentar as $komentar)
            @if($komentar->pesan_sistem)
                <!-- System Message -->
                <div class="text-center my-3">
                    <div class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                        <i class="fas fa-robot mr-1"></i>
                        @php
                            $systemUserName = $komentar->pengguna->user->name ?? $komentar->pengguna->nama ?? 'System';
                        @endphp
                        {{ $systemUserName }} • {{ $komentar->created_at->format('d/m H:i') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-600 bg-white p-2 rounded-lg border border-gray-200 max-w-md mx-auto">
                        {{ $komentar->komentar }}
                    </div>
                </div>
            @else
                <!-- User Message -->
                @php
                    $currentUserId = auth()->user()->pelanggan->id_pelanggan ?? null;
                    $penggunaName = $komentar->pengguna->user->name ?? $komentar->pengguna->nama ?? 'User';
                    $penggunaInitial = substr($penggunaName, 0, 1);
                    $isOwnMessage = $komentar->pengguna_id === $currentUserId;
                    
                    // Cari lampiran yang terkait dengan komentar ini
                    $komentarLampiran = $tiket->lampiran
                        ->where('pengguna_id', $komentar->pengguna_id)
                        ->where('created_at', '>=', $komentar->created_at->copy()->subMinute())
                        ->where('created_at', '<=', $komentar->created_at->copy()->addMinute())
                        ->first();
                @endphp
                
                <div class="flex items-start mb-3 {{ $isOwnMessage ? 'justify-end' : '' }}">
                    @if(!$isOwnMessage)
                        <div class="flex-shrink-0 mr-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center font-medium text-blue-700 text-sm">
                                {{ $penggunaInitial }}
                            </div>
                        </div>
                    @endif
                    
                    <div class="{{ $isOwnMessage ? 'max-w-[75%]' : 'max-w-[75%]' }}">
                        <div class="flex items-center mb-0.5 {{ $isOwnMessage ? 'justify-end' : '' }}">
                            <span class="text-xs text-gray-500">{{ $penggunaName }}</span>
                            <span class="text-xs text-gray-400 mx-1">•</span>
                            <span class="text-xs text-gray-400" title="{{ $komentar->created_at->format('d/m/Y H:i') }}">
                                {{ $komentar->created_at->diffForHumans() }}
                            </span>
                        </div>
                        
                        <div class="{{ $isOwnMessage ? 'bg-blue-100' : 'bg-white' }} p-3 rounded-xl {{ $isOwnMessage ? 'rounded-tr-none' : 'rounded-tl-none' }} border {{ $isOwnMessage ? 'border-blue-200' : 'border-gray-200' }}">
                            <p class="text-gray-800 text-sm whitespace-pre-line">{{ $komentar->komentar }}</p>
                            
                            @if($komentarLampiran)
                                <div class="mt-2 pt-2 border-t border-gray-200">
                                    @if(str_contains($komentarLampiran->tipe_file, 'image'))
                                        <div class="mt-1">
                                            <img src="{{ $userRole === 'staff' 
                                                ? route('help.proses.lampiran.preview', ['lampiran' => $komentarLampiran->id]) 
                                                : route('help.tiket.lampiran.preview', ['lampiran' => $komentarLampiran->id]) }}?thumb=true" 
                                                alt="{{ $komentarLampiran->nama_file }}"
                                                class="w-20 h-20 object-cover rounded-lg border border-gray-300 cursor-pointer hover:opacity-90 transition-opacity"
                                                onclick="viewImage('{{ $komentarLampiran->id }}')">
                                            <div class="mt-1 flex items-center">
                                                <span class="text-xs text-gray-600 truncate max-w-[150px]">{{ $komentarLampiran->nama_file }}</span>
                                                <a href="{{ $userRole === 'staff' 
                                                    ? route('help.proses.lampiran.download', ['lampiran' => $komentarLampiran->id]) 
                                                    : route('help.tiket.lampiran.download', ['lampiran' => $komentarLampiran->id]) }}" 
                                                    class="ml-1 text-xs text-blue-600 hover:text-blue-800"
                                                    title="Download"
                                                    onclick="event.stopPropagation();">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        @php
                                            $iconClass = '';
                                            if (str_contains($komentarLampiran->tipe_file, 'pdf')) {
                                                $iconClass = 'fas fa-file-pdf text-red-500';
                                            } elseif (str_contains($komentarLampiran->tipe_file, 'word')) {
                                                $iconClass = 'fas fa-file-word text-blue-500';
                                            } else {
                                                $iconClass = 'fas fa-file text-gray-500';
                                            }
                                        @endphp
                                        <div class="flex items-center">
                                            <i class="{{ $iconClass }} mr-2"></i>
                                            <a href="{{ $userRole === 'staff' 
                                                ? route('help.proses.lampiran.download', ['lampiran' => $komentarLampiran->id]) 
                                                : route('help.tiket.lampiran.download', ['lampiran' => $komentarLampiran->id]) }}" 
                                                class="text-xs text-gray-700 hover:text-blue-600 truncate max-w-[180px]">
                                                {{ $komentarLampiran->nama_file }}
                                            </a>
                                            <span class="ml-2 text-xs text-gray-500">{{ $komentarLampiran->formatted_size ?? '' }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    @if($isOwnMessage)
                        @php
                            $currentUser = auth()->user();
                            $currentUserName = $currentUser->name ?? 'You';
                            $currentUserInitial = substr($currentUserName, 0, 1);
                        @endphp
                        <div class="flex-shrink-0 ml-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center font-medium text-green-700 text-sm">
                                {{ $currentUserInitial }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        @empty
            <div class="flex flex-col items-center justify-center h-64 text-gray-400">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                    <i class="fas fa-comments text-xl"></i>
                </div>
                <p class="text-gray-500">Belum ada diskusi</p>
                <p class="text-xs text-gray-400 mt-1">Mulai percakapan dengan mengirim pesan</p>
            </div>
        @endforelse
    </div>
    
    @if($showInput)
        <div class="border-t border-gray-200 p-4">
            <form action="{{ $userRole === 'staff' 
                ? route('help.proses.add-komentar', $tiket) 
                : route('help.tiket.add-komentar', $tiket) }}" 
                method="POST" 
                enctype="multipart/form-data" 
                id="chatForm">
                @csrf
                
                <!-- ========== PREVIEW UPLOAD FILE (TAMBAHAN INI!) ========== -->
                <div id="chatPreviewContainer" class="hidden mb-4 bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-sm font-medium text-gray-700 flex items-center">
                            <i class="fas fa-images text-blue-500 mr-2"></i> 
                            File Siap Upload
                        </h4>
                        <div class="flex items-center gap-2">
                            <span id="chatFileBadge" class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full hidden">0 file</span>
                            <button type="button" onclick="clearAllFiles()" class="text-xs text-red-600 hover:text-red-800 hidden" id="clearAllBtn">
                                <i class="fas fa-trash mr-1"></i> Hapus Semua
                            </button>
                        </div>
                    </div>
                    
                    <!-- Daftar Preview File -->
                    <div id="chatFilePreview" class="space-y-2 max-h-80 overflow-y-auto pr-1">
                        <!-- Preview akan diisi JavaScript -->
                    </div>
                    
                    <div class="mt-3 text-xs text-gray-500 flex items-center bg-blue-50 p-2 rounded">
                        <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                        <span class="text-blue-700">Klik <strong>Kirim</strong> untuk mengupload file. Maksimal 5MB per file.</span>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <div class="flex-1">
                        <textarea name="komentar" 
                                  id="chatInput"
                                  rows="1"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm"
                                  placeholder="Ketik pesan..."
                                  required></textarea>
                    </div>
                    <div class="flex items-end gap-1">
                        <div class="relative">
                            <input type="file" 
                                   id="chat-lampiran" 
                                   name="lampiran[]" 
                                   multiple
                                   class="hidden"
                                   accept="image/*,.pdf,.doc,.docx">
                            <button type="button" 
                                    onclick="document.getElementById('chat-lampiran').click()"
                                    class="inline-flex items-center justify-center w-9 h-9 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg border border-gray-300 transition-colors"
                                    title="Lampirkan File">
                                <i class="fas fa-paperclip text-sm"></i>
                            </button>
                            <span id="file-count" class="absolute -top-1 -right-1 bg-blue-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center hidden"></span>
                        </div>
                        
                        <button type="submit"
                                class="inline-flex items-center justify-center w-9 h-9 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            <i class="fas fa-paper-plane text-sm"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @else
        <div class="p-6 text-center">
            <div class="inline-flex items-center justify-center w-10 h-10 bg-gray-100 rounded-full mb-2">
                <i class="fas fa-lock text-gray-400"></i>
            </div>
            <p class="text-sm text-gray-500">Diskusi telah ditutup</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
// ==================== PREVIEW FILE UPLOAD ====================
document.addEventListener('DOMContentLoaded', function() {
    const chatFileInput = document.getElementById('chat-lampiran');
    const fileCountSpan = document.getElementById('file-count');
    const chatPreviewContainer = document.getElementById('chatPreviewContainer');
    const chatFilePreview = document.getElementById('chatFilePreview');
    const chatFileBadge = document.getElementById('chatFileBadge');
    const clearAllBtn = document.getElementById('clearAllBtn');

    if (!chatFileInput) return;

    // Event Listener untuk perubahan file
    chatFileInput.addEventListener('change', function() {
        const count = this.files.length;
        
        // Update badge di tombol upload
        if (fileCountSpan) {
            if (count > 0) {
                fileCountSpan.textContent = count;
                fileCountSpan.classList.remove('hidden');
            } else {
                fileCountSpan.classList.add('hidden');
            }
        }
        
        // Tampilkan/sembunyikan preview container
        if (chatPreviewContainer) {
            if (count > 0) {
                chatPreviewContainer.classList.remove('hidden');
                if (chatFileBadge) {
                    chatFileBadge.textContent = count + ' file' + (count > 1 ? 's' : '');
                    chatFileBadge.classList.remove('hidden');
                }
                if (clearAllBtn) clearAllBtn.classList.remove('hidden');
                previewChatFiles(this.files);
            } else {
                chatPreviewContainer.classList.add('hidden');
                if (chatFileBadge) chatFileBadge.classList.add('hidden');
                if (clearAllBtn) clearAllBtn.classList.add('hidden');
                if (chatFilePreview) chatFilePreview.innerHTML = '';
            }
        }
    });

    // Fungsi preview file
    function previewChatFiles(files) {
        if (!chatFilePreview) return;
        
        chatFilePreview.innerHTML = '';
        
        // Konversi FileList ke Array
        const fileArray = Array.from(files);
        
        fileArray.forEach((file, index) => {
            // Validasi ukuran file (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({
                    icon: 'warning',
                    title: 'File Terlalu Besar',
                    text: `${file.name} melebihi 5MB. File tidak akan diupload.`,
                    timer: 2000,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false
                });
                return;
            }
            
            const isImage = file.type.startsWith('image/');
            const fileSize = (file.size / 1024).toFixed(0);
            const fileExt = file.name.split('.').pop().toUpperCase() || 'FILE';
            
            const previewItem = document.createElement('div');
            previewItem.className = 'flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors group';
            previewItem.setAttribute('data-file-index', index);
            
            if (isImage) {
                // Preview UNTUK GAMBAR
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewItem.innerHTML = `
                        <div class="flex-shrink-0 mr-3 relative">
                            <img src="${e.target.result}" class="w-14 h-14 object-cover rounded-lg border-2 border-white shadow-sm">
                            <span class="absolute -top-2 -right-2 bg-blue-600 text-white text-xs px-1.5 py-0.5 rounded-full">IMG</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                                <span class="text-xs text-green-600 bg-green-100 px-1.5 py-0.5 rounded-full">Baru</span>
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs">
                                <span class="text-gray-500 flex items-center">
                                    <i class="fas fa-database mr-1 text-gray-400"></i> ${fileSize} KB
                                </span>
                                <span class="text-gray-500 flex items-center">
                                    <i class="fas fa-file-image mr-1 text-blue-400"></i> ${fileExt}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center ml-2 space-x-1">
                            <button type="button" onclick="removeChatFile(${index})" class="p-1.5 text-red-600 hover:bg-red-100 rounded-lg transition-colors" title="Hapus file">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
                    chatFilePreview.appendChild(previewItem);
                };
                reader.readAsDataURL(file);
            } else {
                // Preview UNTUK DOKUMEN
                previewItem.innerHTML = `
                    <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-file-alt text-gray-600 text-2xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                            <span class="text-xs text-green-600 bg-green-100 px-1.5 py-0.5 rounded-full">Baru</span>
                        </div>
                        <div class="flex items-center gap-3 mt-1 text-xs">
                            <span class="text-gray-500 flex items-center">
                                <i class="fas fa-database mr-1 text-gray-400"></i> ${fileSize} KB
                            </span>
                            <span class="text-gray-500 flex items-center">
                                <i class="fas fa-file mr-1 text-orange-400"></i> ${fileExt}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center ml-2 space-x-1">
                        <button type="button" onclick="removeChatFile(${index})" class="p-1.5 text-red-600 hover:bg-red-100 rounded-lg transition-colors" title="Hapus file">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                `;
                chatFilePreview.appendChild(previewItem);
            }
        });
    }

    // Fungsi hapus file individual
    window.removeChatFile = function(index) {
        const input = document.getElementById('chat-lampiran');
        const dt = new DataTransfer();
        const files = input.files;
        
        for (let i = 0; i < files.length; i++) {
            if (i !== index) {
                dt.items.add(files[i]);
            }
        }
        
        input.files = dt.files;
        
        // Trigger change event
        const event = new Event('change', { bubbles: true });
        input.dispatchEvent(event);
        
        // Notifikasi
        Swal.fire({
            icon: 'success',
            title: 'File Dihapus',
            text: 'File telah dihapus dari daftar upload',
            timer: 1500,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
    };

    // Fungsi hapus semua file
    window.clearAllFiles = function() {
        Swal.fire({
            title: 'Hapus Semua File?',
            text: 'Semua file yang dipilih akan dihapus dari daftar upload.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus Semua',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ef4444',
        }).then((result) => {
            if (result.isConfirmed) {
                const input = document.getElementById('chat-lampiran');
                input.value = '';
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Semua File Dihapus',
                    text: 'Daftar upload telah dikosongkan',
                    timer: 1500,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false
                });
            }
        });
    };
});
</script>
@endpush

@push('styles')
<style>
/* ==================== PREVIEW UPLOAD ==================== */
#chatPreviewContainer {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#chatFilePreview {
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 #f1f1f1;
    max-height: 320px;
    overflow-y: auto;
}

#chatFilePreview::-webkit-scrollbar {
    width: 4px;
}

#chatFilePreview::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

#chatFilePreview::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

#chatFilePreview::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Toast Notification */
.swal2-toast {
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

.swal2-toast .swal2-title {
    font-size: 0.875rem !important;
}
</style>
@endpush