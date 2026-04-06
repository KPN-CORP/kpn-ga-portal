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
                <!-- System message -->
                <div class="text-center my-3">
                    <div class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                        <i class="fas fa-robot mr-1"></i>
                        {{ $komentar->pengguna->user->name ?? $komentar->pengguna->nama ?? 'System' }} • {{ $komentar->created_at->format('d/m H:i') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-600 bg-white p-2 rounded-lg border border-gray-200 max-w-md mx-auto">
                        {{ $komentar->komentar }}
                    </div>
                </div>
            @else
                @php
                    $currentUserId = auth()->user()->pelanggan->id_pelanggan ?? null;
                    $penggunaName = $komentar->pengguna->user->name ?? $komentar->pengguna->nama ?? 'User';
                    $penggunaInitial = substr($penggunaName, 0, 1);
                    $isOwnMessage = $komentar->pengguna_id === $currentUserId;

                    // Cek apakah komentar ini memiliki lampiran (relasi atau fallback waktu)
                    $hasLampiran = false;
                    $lampiranCount = 0;
                    
                    if (method_exists($komentar, 'lampiran') && $komentar->lampiran && $komentar->lampiran->count()) {
                        $hasLampiran = true;
                        $lampiranCount = $komentar->lampiran->count();
                    } else {
                        // Fallback: cari lampiran dalam rentang waktu 2 menit
                        $startTime = $komentar->created_at->copy()->subMinutes(2);
                        $endTime = $komentar->created_at->copy()->addMinutes(2);
                        $lampiranKomentar = $tiket->lampiran
                            ->where('pengguna_id', $komentar->pengguna_id)
                            ->whereBetween('created_at', [$startTime, $endTime]);
                        $lampiranCount = $lampiranKomentar->count();
                        $hasLampiran = $lampiranCount > 0;
                    }
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
                            <span class="text-xs text-gray-400">{{ $komentar->created_at->diffForHumans() }}</span>
                        </div>

                        <div class="{{ $isOwnMessage ? 'bg-blue-100' : 'bg-white' }} p-3 rounded-xl {{ $isOwnMessage ? 'rounded-tr-none' : 'rounded-tl-none' }} border {{ $isOwnMessage ? 'border-blue-200' : 'border-gray-200' }}">
                            <p class="text-gray-800 text-sm whitespace-pre-line">{{ $komentar->komentar }}</p>

                            {{-- Tampilkan teks bahwa ada lampiran, tanpa thumbnail --}}
                            @if($hasLampiran)
                                <div class="mt-2 pt-1 text-xs text-gray-500 flex items-center gap-1">
                                    <i class="fas fa-paperclip"></i>
                                    <span>{{ $lampiranCount }} lampiran (lihat di bagian <strong>Lampiran</strong>)</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($isOwnMessage)
                        @php
                            $currentUser = auth()->user();
                            $currentUserInitial = substr($currentUser->name ?? 'You', 0, 1);
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

                <!-- Preview upload file (tetap ada, agar user tahu file terpilih) -->
                <div id="chatPreviewContainer" class="hidden mb-4 bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-sm font-medium text-gray-700 flex items-center">
                            <i class="fas fa-images text-blue-500 mr-2"></i> File Siap Upload
                        </h4>
                        <div class="flex items-center gap-2">
                            <span id="chatFileBadge" class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full hidden">0 file</span>
                            <button type="button" onclick="clearAllFiles()" class="text-xs text-red-600 hover:text-red-800 hidden" id="clearAllBtn">
                                <i class="fas fa-trash mr-1"></i> Hapus Semua
                            </button>
                        </div>
                    </div>
                    <div id="chatFilePreview" class="space-y-2 max-h-80 overflow-y-auto pr-1"></div>
                    <div class="mt-3 text-xs text-gray-500 flex items-center bg-blue-50 p-2 rounded">
                        <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                        <span class="text-blue-700">File akan tersimpan di bagian <strong>Lampiran</strong> setelah dikirim.</span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <div class="flex-1">
                        <textarea name="komentar" id="chatInput" rows="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm"
                            placeholder="Ketik pesan..." required></textarea>
                    </div>
                    <div class="flex items-end gap-1">
                        <div class="relative">
                            <input type="file" id="chat-lampiran" name="lampiran[]" multiple class="hidden" accept="image/*,.pdf,.doc,.docx">
                            <button type="button" onclick="document.getElementById('chat-lampiran').click()"
                                class="inline-flex items-center justify-center w-9 h-9 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg border border-gray-300">
                                <i class="fas fa-paperclip text-sm"></i>
                            </button>
                            <span id="file-count" class="absolute -top-1 -right-1 bg-blue-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center hidden"></span>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center justify-center w-9 h-9 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
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
    // Auto scroll chat
    const chatContainer = document.getElementById('chatContainer');
    if(chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;

    // Preview upload file (minimal)
    const fileInput = document.getElementById('chat-lampiran');
    const fileCountSpan = document.getElementById('file-count');
    const previewContainer = document.getElementById('chatPreviewContainer');
    const filePreviewDiv = document.getElementById('chatFilePreview');
    const fileBadge = document.getElementById('chatFileBadge');
    const clearBtn = document.getElementById('clearAllBtn');

    if(fileInput) {
        fileInput.addEventListener('change', function() {
            const count = this.files.length;
            if(count > 0) {
                fileCountSpan.textContent = count;
                fileCountSpan.classList.remove('hidden');
                previewContainer.classList.remove('hidden');
                fileBadge.textContent = count + ' file' + (count > 1 ? 's' : '');
                fileBadge.classList.remove('hidden');
                clearBtn.classList.remove('hidden');
                
                // Preview sederhana (nama file)
                filePreviewDiv.innerHTML = '';
                Array.from(this.files).forEach((file, idx) => {
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between p-2 bg-gray-50 rounded';
                    div.innerHTML = `
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-alt text-gray-500"></i>
                            <span class="text-sm truncate max-w-[200px]">${file.name}</span>
                            <span class="text-xs text-gray-500">(${(file.size/1024).toFixed(0)} KB)</span>
                        </div>
                        <button type="button" onclick="removeFile(${idx})" class="text-red-500 text-xs">Hapus</button>
                    `;
                    filePreviewDiv.appendChild(div);
                });
            } else {
                fileCountSpan.classList.add('hidden');
                previewContainer.classList.add('hidden');
                fileBadge.classList.add('hidden');
                clearBtn.classList.add('hidden');
                filePreviewDiv.innerHTML = '';
            }
        });
    }

    window.removeFile = function(index) {
        const input = document.getElementById('chat-lampiran');
        const dt = new DataTransfer();
        const files = input.files;
        for(let i=0; i<files.length; i++) {
            if(i !== index) dt.items.add(files[i]);
        }
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
    };

    window.clearAllFiles = function() {
        document.getElementById('chat-lampiran').value = '';
        document.getElementById('chat-lampiran').dispatchEvent(new Event('change'));
    };

    // Kirim dengan Ctrl+Enter
    const chatInput = document.getElementById('chatInput');
    if(chatInput) {
        chatInput.addEventListener('keydown', function(e) {
            if(e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                if(this.value.trim() !== '') document.getElementById('chatForm').submit();
            }
        });
    }
</script>
@endpush

@push('styles')
<style>
    #chatPreviewContainer {
        animation: slideDown 0.3s ease;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    #chatFilePreview {
        max-height: 200px;
        overflow-y: auto;
    }
</style>
@endpush