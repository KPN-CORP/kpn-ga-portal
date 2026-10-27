@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Kirim Dokumen</h2>
            <p class="text-xs text-gray-500">Buat pengiriman dokumen baru</p>
        </div>
        
        <div>
            <a href="{{ route('track-r.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700
                      rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>

    {{-- FORM --}}
    <div class="bg-white rounded-xl border p-6">
        <form method="POST" action="{{ route('track-r.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- NOMOR & JUDUL --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nomor Dokumen <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nomor_dokumen" required
                           value="{{ old('nomor_dokumen') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5
                                  text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                  @error('nomor_dokumen') border-red-300 @enderror"
                           placeholder="Contoh: DOC/2026/001">
                    @error('nomor_dokumen')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Nomor harus unik</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Judul Dokumen <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="judul" required
                           value="{{ old('judul') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5
                                  text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                  @error('judul') border-red-300 @enderror"
                           placeholder="Masukkan judul dokumen">
                    @error('judul')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- PENERIMA - AUTOSEARCH AFTER 3 CHARACTERS --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kirim ke <span class="text-red-500">*</span>
                </label>
                
                <div class="relative">
                    {{-- Search input --}}
                    <input type="text" 
                           id="penerima_search"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5
                                  text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                  @error('penerima_id') border-red-300 @enderror"
                           placeholder="Ketik minimal 3 huruf nama penerima..."
                           autocomplete="off">
                    
                    {{-- Hidden input for actual user ID --}}
                    <input type="hidden" name="penerima_id" id="penerima_id" value="{{ old('penerima_id') }}" required>
                    
                    {{-- Search results dropdown --}}
                    <div id="searchResults" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <div class="p-3 text-sm text-gray-500 text-center">
                            Ketik minimal 3 huruf untuk mencari user
                        </div>
                    </div>
                    
                    {{-- Selected user display --}}
                    <div id="selectedUserDisplay" class="mt-2 hidden">
                        <div class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-800" id="selectedUserName"></p>
                                <p class="text-xs text-gray-500" id="selectedUserEmail"></p>
                            </div>
                            <button type="button" 
                                    onclick="clearSelectedUser()"
                                    class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                @error('penerima_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Ketik minimal 3 huruf, sistem akan menyarankan user</p>
            </div>

            {{-- FOTO DOKUMEN --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Lampiran Dokumen
                    <span class="text-sm font-normal text-gray-500">(Opsional, max 5MB per file)</span>
                </label>
                
                {{-- File upload area --}}
                <div class="mt-2">
                    <div class="flex items-center justify-center w-full">
                        <label for="foto_dokumen_input" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                                <p class="mb-1 text-sm text-gray-500">
                                    <span class="font-semibold">Klik untuk upload</span> atau drag & drop
                                </p>
                                <p class="text-xs text-gray-500">
                                    JPG, PNG, PDF, DOC, DOCX (MAX 5MB per file)
                                </p>
                            </div>
                            <input id="foto_dokumen_input" type="file" name="foto_dokumen[]" multiple
                                   accept="image/*,.pdf,.doc,.docx"
                                   class="hidden"
                                   onchange="previewFiles()">
                        </label>
                    </div>
                    
                    {{-- File preview --}}
                    <div id="filePreview" class="mt-4 space-y-2 hidden">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-file text-gray-400 mr-3"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-700" id="fileCount">0 file dipilih</p>
                                    <p class="text-xs text-gray-500" id="fileSize">0 KB</p>
                                </div>
                            </div>
                            <button type="button" onclick="clearFiles()" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div id="fileList" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            {{-- File list will be populated by JavaScript --}}
                        </div>
                    </div>
                </div>
                
                @error('foto_dokumen.*')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- KETERANGAN --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Keterangan (Opsional)
                </label>
                <textarea name="keterangan" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5
                                 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                 @error('keterangan') border-red-300 @enderror"
                          placeholder="Tambahkan keterangan atau instruksi...">{{ old('keterangan') }}</textarea>
                @error('keterangan')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- INFO BOX --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400 mt-0.5"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Informasi Penting</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Dokumen bersifat 1 ke 1 (hanya antara pengirim dan penerima)</li>
                                <li>Lampiran tidak dapat dihapus setelah dikirim</li>
                                <li>Penerima dapat menerima, menolak, atau meneruskan dokumen</li>
                                <li>Semua aktivitas akan tercatat dalam riwayat</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BUTTONS --}}
            <div class="flex justify-end gap-3 pt-4 border-t">
                <a href="{{ route('track-r.index') }}"
                   class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg
                          text-sm font-semibold hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit"
                        class="px-5 py-2.5 bg-blue-600 text-white rounded-lg
                               text-sm font-semibold hover:bg-blue-700 transition
                               flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i> Kirim Dokumen
                </button>
            </div>
        </form>
    </div>

</div>

<script>
// All users data loaded once from PHP
const allUsers = [
    @foreach($users as $user)
        @if($user->id != auth()->id())
            {
                id: {{ $user->id }},
                name: "{{ $user->name }}",
                email: "{{ $user->email }}",
                searchText: "{{ strtolower($user->name) }} {{ strtolower($user->email) }}"
            },
        @endif
    @endforeach
];

let searchTimeout;

function searchUsers() {
    const searchInput = document.getElementById('penerima_search');
    const searchTerm = searchInput.value.trim().toLowerCase();
    const resultsDiv = document.getElementById('searchResults');
    
    // Clear previous timeout
    clearTimeout(searchTimeout);
    
    // Clear results if search term is empty
    if (searchTerm.length === 0) {
        resultsDiv.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">Ketik minimal 3 huruf untuk mencari user</div>';
        resultsDiv.classList.add('hidden');
        return;
    }
    
    // Only search after 3 characters
    if (searchTerm.length < 3) {
        resultsDiv.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">Ketik minimal 3 huruf...</div>';
        resultsDiv.classList.remove('hidden');
        return;
    }
    
    // Set timeout to delay search (debounce)
    searchTimeout = setTimeout(() => {
        // Filter users locally
        const filteredUsers = allUsers.filter(user => 
            user.searchText.includes(searchTerm)
        );
        
        displaySearchResults(filteredUsers);
    }, 300);
}

function displaySearchResults(users) {
    const resultsDiv = document.getElementById('searchResults');
    
    if (users.length === 0) {
        resultsDiv.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">Tidak ditemukan user dengan nama tersebut</div>';
        resultsDiv.classList.remove('hidden');
        return;
    }
    
    let html = '';
    users.slice(0, 10).forEach(user => {
        html += `
            <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                 onclick="selectUser(${user.id}, '${user.name.replace(/'/g, "\\'")}', '${user.email}')">
                <div class="font-medium text-gray-800">${user.name}</div>
                <div class="text-xs text-gray-500">${user.email}</div>
            </div>
        `;
    });
    
    if (users.length > 10) {
        html += `<div class="p-2 text-xs text-gray-500 text-center border-t border-gray-200">
                    ${users.length - 10} lebih... ketik lebih spesifik
                 </div>`;
    }
    
    resultsDiv.innerHTML = html;
    resultsDiv.classList.remove('hidden');
}

function selectUser(userId, userName, userEmail) {
    // Set hidden input value
    document.getElementById('penerima_id').value = userId;
    
    // Display selected user
    document.getElementById('selectedUserName').textContent = userName;
    document.getElementById('selectedUserEmail').textContent = userEmail;
    document.getElementById('selectedUserDisplay').classList.remove('hidden');
    
    // Hide search input and results
    document.getElementById('penerima_search').value = '';
    document.getElementById('searchResults').classList.add('hidden');
}

function clearSelectedUser() {
    // Clear all selections
    document.getElementById('penerima_id').value = '';
    document.getElementById('selectedUserDisplay').classList.add('hidden');
    document.getElementById('penerima_search').value = '';
    document.getElementById('searchResults').classList.add('hidden');
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('penerima_search');
    
    // Search on input
    searchInput.addEventListener('input', searchUsers);
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const resultsDiv = document.getElementById('searchResults');
        if (!searchInput.contains(event.target) && !resultsDiv.contains(event.target)) {
            resultsDiv.classList.add('hidden');
        }
    });
    
    // Pre-fill if there's old value
    @if(old('penerima_id'))
        const oldUserId = {{ old('penerima_id') }};
        const foundUser = allUsers.find(user => user.id == oldUserId);
        if (foundUser) {
            document.getElementById('penerima_id').value = foundUser.id;
            document.getElementById('selectedUserName').textContent = foundUser.name;
            document.getElementById('selectedUserEmail').textContent = foundUser.email;
            document.getElementById('selectedUserDisplay').classList.remove('hidden');
        }
    @endif
});

// EXISTING FILE UPLOAD FUNCTIONS (NO CHANGES)
function previewFiles() {
    const input = document.getElementById('foto_dokumen_input');
    const preview = document.getElementById('filePreview');
    const fileList = document.getElementById('fileList');
    const fileCount = document.getElementById('fileCount');
    const fileSize = document.getElementById('fileSize');
    
    const files = input.files;
    
    if (files.length > 0) {
        fileList.innerHTML = '';
        let totalSize = 0;
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            totalSize += file.size;
            
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between p-2 bg-white border rounded';
            fileItem.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-file text-gray-400 mr-2"></i>
                    <div>
                        <p class="text-xs font-medium text-gray-700 truncate" style="max-width: 150px;">${file.name}</p>
                        <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
                    </div>
                </div>
            `;
            fileList.appendChild(fileItem);
        }
        
        fileCount.textContent = `${files.length} file dipilih`;
        fileSize.textContent = formatFileSize(totalSize);
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
}

function clearFiles() {
    const input = document.getElementById('foto_dokumen_input');
    const preview = document.getElementById('filePreview');
    input.value = '';
    preview.classList.add('hidden');
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// Drag and drop functionality
const dropArea = document.querySelector('label[for="foto_dokumen_input"]');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropArea.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, unhighlight, false);
});

function highlight() {
    dropArea.classList.add('border-blue-400', 'bg-blue-50');
}

function unhighlight() {
    dropArea.classList.remove('border-blue-400', 'bg-blue-50');
}

dropArea.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    const input = document.getElementById('foto_dokumen_input');
    
    const dataTransfer = new DataTransfer();
    if (input.files) {
        for (let i = 0; i < input.files.length; i++) {
            dataTransfer.items.add(input.files[i]);
        }
    }
    
    for (let i = 0; i < files.length; i++) {
        dataTransfer.items.add(files[i]);
    }
    
    input.files = dataTransfer.files;
    const event = new Event('change', { bubbles: true });
    input.dispatchEvent(event);
}
</script>
@endsection