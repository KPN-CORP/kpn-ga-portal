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
                    
                    <span class="px-3 py-1 text-sm font-medium rounded-full border {{ $statusColors[$tiket->status] }}">
                        {{ $statusLabels[$tiket->status] ?? $tiket->status }}
                    </span>
                    
                    <span class="px-3 py-1 text-sm font-medium rounded-full border {{ $priorityColors[$tiket->prioritas] }}">
                        {{ $tiket->prioritas }}
                    </span>
                </div>
                
                <p class="text-gray-600 text-sm">{{ $tiket->nomor_tiket }}</p>
                <p class="text-sm text-gray-500">Dibuat: {{ $tiket->created_at->format('d/m/Y H:i') }}</p>
            </div>
            
            <div class="flex items-center gap-3">

                <a href="{{ route('help.tiket.pdf', $tiket) }}" 
                target="_blank"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <i class="fas fa-file-pdf mr-2"></i> Download PDF
                </a>
                <a href="{{ route('help.proses.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg border border-gray-300 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>
        </div>
        
        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            <!-- Pelapor -->
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Dilaporkan Oleh</h3>
                <div class="flex items-center">
                    @php
                        $pelaporName = $tiket->pelapor->user->name ?? $tiket->pelapor->nama ?? 'User';
                        $pelaporInitial = substr($pelaporName, 0, 1);
                        $pelaporEmail = $tiket->pelapor->user->email ?? '';
                    @endphp
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center font-medium text-blue-700 mr-3">
                        {{ $pelaporInitial }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $pelaporName }}</p>
                        <p class="text-sm text-gray-500">{{ $pelaporEmail }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Kategori & Bisnis Unit -->
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Kategori</h3>
                <div class="flex items-center mb-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center mr-3">
                        <i class="fas fa-tag text-blue-600"></i>
                    </div>
                    <p class="font-medium text-gray-900">{{ $tiket->kategori->nama ?? '-' }}</p>
                </div>
                @if($tiket->bisnisUnit)
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center mr-3">
                        <i class="fas fa-building text-gray-600"></i>
                    </div>
                    <p class="text-sm text-gray-700">{{ $tiket->bisnisUnit->nama_bisnis_unit }}</p>
                </div>
                @endif
            </div>
            
            <!-- Penanggung Jawab -->
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Penanggung Jawab</h3>
                <div class="flex items-center">
                    @if($tiket->ditugaskanKe)
                        @php
                            $pjName = $tiket->ditugaskanKe->user->name ?? $tiket->ditugaskanKe->nama ?? 'Staff GA';
                            $pjInitial = substr($pjName, 0, 1);
                            $pjEmail = $tiket->ditugaskanKe->user->email ?? '';
                        @endphp
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center font-medium text-green-700 mr-3">
                            {{ $pjInitial }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $pjName }}</p>
                            <p class="text-sm text-gray-500">{{ $pjEmail }}</p>
                            @if($tiket->diproses_pada)
                            <p class="text-xs text-gray-500 mt-1">Sejak: {{ $tiket->diproses_pada->format('d/m H:i') }}</p>
                            @endif
                        </div>
                    @else
                        <p class="text-gray-500 italic">Belum ditugaskan</p>
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
            <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-file-alt text-blue-500 mr-2"></i> Deskripsi Masalah
                </h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-700 whitespace-pre-line">{{ $tiket->deskripsi }}</p>
                </div>
            </div>
            
            <!-- Diskusi Tiket -->
            @include('help.partials.diskusi', [
                'tiket' => $tiket,
                'userRole' => 'staff',
                'showInput' => $tiket->status !== 'CLOSED'
            ])
            
            <!-- Lampiran -->
            @include('help.partials.lampiran', [
                'lampiran' => $lampiran,
                'tiket' => $tiket,
                'userRole' => 'staff'
            ])
        </div>
        
        <!-- Kolom Kanan (1/3) -->
        <div class="space-y-6">
            @php
                // Dapatkan data user dan pelanggan
                $currentUser = auth()->user();
                $currentPelanggan = null;
                $currentPelangganId = null;
                
                if ($currentUser) {
                    $currentPelanggan = $currentUser->pelanggan;
                    $currentPelangganId = $currentPelanggan ? $currentPelanggan->id_pelanggan : null;
                }
                
                // CEK APAKAH USER ADALAH PENANGGUNG JAWAB
                $isAssigned = false;
                if ($tiket->ditugaskan_ke && $currentPelangganId) {
                    $isAssigned = ($tiket->ditugaskan_ke == $currentPelangganId);
                }
                
                // CEK APAKAH TOMBOL/TAMPILAN HARUS DITAMPILKAN
                $canTake = ($tiket->status === 'OPEN');
                $showUploadForm = ($tiket->status === 'ON_PROCESS' && $isAssigned);
                $showWaitingForm = ($tiket->status === 'ON_PROCESS' && $isAssigned);
                $showResumeForm = ($tiket->status === 'WAITING' && $isAssigned);
                $showCompleteButton = ($tiket->status === 'ON_PROCESS' && $isAssigned);
                $showCloseButton = ($tiket->status === 'DONE' && $isAssigned);
                $showTransferButton = (in_array($tiket->status, ['ON_PROCESS', 'WAITING']) && $isAssigned);
            @endphp
            
            <!-- UPLOAD FOTO HASIL PEKERJAAN -->
            @if($showUploadForm)
                @include('help.partials.upload-foto-selesai', ['tiket' => $tiket])
            @endif
            
            <!-- Aksi Tiket -->
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-tasks text-blue-500 mr-2"></i> Aksi Tiket
                </h3>
                <div class="space-y-2">
                    <!-- AMBIL TIKET -->
                    @if($canTake)
                        <form action="{{ route('help.proses.take', $tiket) }}" method="POST" id="takeForm">
                            @csrf
                            <button type="button" 
                                    onclick="takeTicket()"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">
                                <i class="fas fa-hand-paper mr-2"></i> Ambil Tiket
                            </button>
                        </form>
                    @endif
                    
                    <!-- SELESAIKAN TIKET -->
                    @if($showCompleteButton)
                        <form action="{{ route('help.proses.complete', $tiket) }}" method="POST" class="mb-2">
                            @csrf
                            <input type="hidden" name="catatan" value="Tiket telah diselesaikan oleh petugas.">
                            <button type="button" 
                                    onclick="confirmComplete(this)"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                                <i class="fas fa-check-circle mr-2"></i> Selesaikan Tiket
                            </button>
                        </form>
                    @endif
                    
                    <!-- ============ ALIHKAN KE GA CORP - TOMBOL YANG SUDAH DIPERBAIKI ============ -->
                    @if($showTransferButton)
                        <button type="button" 
                                onclick="openTransferModal()"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                            <i class="fas fa-exchange-alt mr-2"></i> Alihkan ke GA Corp
                        </button>
                    @endif
                    
                    <!-- TUTUP TIKET -->
                    @if($showCloseButton)
                        <form action="{{ route('help.proses.close', $tiket) }}" method="POST">
                            @csrf
                            <button type="button" 
                                    onclick="confirmClose(this)"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gray-800 hover:bg-gray-900 text-white font-medium rounded-lg transition-colors">
                                <i class="fas fa-lock mr-2"></i> Tutup Tiket
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            
            <!-- SET STATUS WAITING -->
            @if($showWaitingForm)
                @include('help.partials.set-waiting', ['tiket' => $tiket])
            @endif
            
            <!-- RESUME KE ON_PROCESS -->
            @if($showResumeForm)
                @include('help.partials.resume-process', ['tiket' => $tiket])
            @endif
            
            <!-- Timeline Status -->
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-history text-gray-500 mr-2"></i> Timeline Status
                </h3>
                <div class="space-y-3">
                    @forelse($tiket->logStatus as $log)
                    @php
                        $logUserName = $log->pengguna->user->name ?? $log->pengguna->nama ?? 'System';
                    @endphp
                    <div class="relative pl-6 pb-3 last:pb-0">
                        @if(!$loop->last)
                            <div class="absolute left-2.5 top-3 bottom-0 w-0.5 bg-gray-200"></div>
                        @endif
                        
                        <div class="absolute left-0 top-1 w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center">
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

<!-- ========== MODAL TRANSFER GA CORP - VERSI DIPERBAIKI ========== -->
<div id="transferModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeTransferModal()"></div>
        
        <!-- Modal panel -->
        <div class="relative bg-white rounded-lg w-full max-w-md transform transition-all">
            <div class="p-6">
                <!-- Header -->
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-exchange-alt text-indigo-600 mr-2"></i>
                        Alihkan Tiket ke GA Corp
                    </h3>
                    <button type="button" onclick="closeTransferModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- ========== FORM - PASTIKAN ID INI ADA ========== -->
                <form action="{{ route('help.proses.transfer-to-corp', $tiket) }}" method="POST" id="transferForm">
                    @csrf
                    <div class="mb-4">
                        <label for="alasan_transfer" class="block text-sm font-medium text-gray-700 mb-2">
                            Alasan Pengalihan <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            name="alasan_transfer" 
                            id="alasan_transfer" 
                            rows="4"
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                            placeholder="Contoh: Membutuhkan penanganan khusus, perlu persetujuan Corp, kendala teknis, dll."
                            required
                        ></textarea>
                        <p class="mt-2 text-xs text-gray-500">
                            <i class="fas fa-info-circle"></i> 
                            Tiket akan dikembalikan ke status OPEN dan dapat diambil oleh petugas GA Corp lainnya.
                        </p>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" 
                                onclick="closeTransferModal()"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg border border-gray-300 transition-colors">
                            Batal
                        </button>
                        <button type="button" 
                                onclick="confirmTransfer()"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                            <i class="fas fa-exchange-alt mr-2"></i> Ya, Alihkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Image Viewer Modal -->
@include('help.partials.image-viewer')

@endsection

@push('scripts')
<!-- ========== SWEET ALERT WAJIB ADA ========== -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // ========== FUNGSI UNTUK MODAL TRANSFER - PASTIKAN INI ADA ==========
    
    // Buka modal transfer
    window.openTransferModal = function() {
        console.log('Opening transfer modal'); // Untuk debug
        const modal = document.getElementById('transferModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        } else {
            console.error('Modal transfer tidak ditemukan!');
            alert('Error: Modal tidak ditemukan. Refresh halaman dan coba lagi.');
        }
    };
    
    // Tutup modal transfer
    window.closeTransferModal = function() {
        console.log('Closing transfer modal');
        const modal = document.getElementById('transferModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            const form = document.getElementById('transferForm');
            if (form) form.reset();
        }
    };
    
    // Konfirmasi transfer
    window.confirmTransfer = function() {
        const alasan = document.getElementById('alasan_transfer');
        
        // Validasi alasan harus diisi
        if (!alasan.value.trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Peringatan',
                text: 'Silakan isi alasan pengalihan terlebih dahulu!',
                confirmButtonColor: '#f59e0b',
            });
            return;
        }
        
        // Validasi minimal 5 karakter
        if (alasan.value.trim().length < 5) {
            Swal.fire({
                icon: 'warning',
                title: 'Peringatan',
                text: 'Alasan pengalihan minimal 5 karakter!',
                confirmButtonColor: '#f59e0b',
            });
            return;
        }
        
        Swal.fire({
            title: 'Alihkan ke GA Corp',
            text: 'Apakah Anda yakin ingin mengalihkan tiket ini ke GA Corp?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Alihkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#6b7280',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('Submitting transfer form...');
                const form = document.getElementById('transferForm');
                form.submit();
            }
        });
    };
    
    // Auto-scroll chat
    const chatContainer = document.getElementById('chatContainer');
    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    
    // Auto-resize textarea
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
    
    // File upload indicator
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
    
    // Preview foto upload
    window.previewFotoFiles = function(input) {
        const previewContainer = document.getElementById('fotoPreview');
        if (!previewContainer) return;
        
        previewContainer.innerHTML = '';
        
        if (input.files.length > 0) {
            previewContainer.classList.remove('hidden');
            
            for (let i = 0; i < input.files.length; i++) {
                const file = input.files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'flex items-center p-2 bg-gray-50 rounded border border-gray-200 mb-2';
                    div.innerHTML = `
                        <div class="flex-shrink-0 mr-3">
                            <img src="${e.target.result}" class="w-12 h-12 object-cover rounded border">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                            <p class="text-xs text-gray-500">${(file.size / 1024).toFixed(0)} KB</p>
                        </div>
                        <button type="button" onclick="this.closest('div').remove()" class="ml-2 text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    previewContainer.appendChild(div);
                };
                
                reader.readAsDataURL(file);
            }
            
            const fileCount = document.getElementById('file-count');
            if (fileCount) {
                fileCount.textContent = input.files.length;
                fileCount.classList.remove('hidden');
            }
        } else {
            previewContainer.classList.add('hidden');
            const fileCount = document.getElementById('file-count');
            if (fileCount) {
                fileCount.classList.add('hidden');
            }
        }
    };
    
    // Confirm functions
    window.takeTicket = function() {
        Swal.fire({
            title: 'Ambil Tiket',
            text: 'Apakah Anda yakin ingin mengambil tiket ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Ambil',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#10b981',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('takeForm').submit();
            }
        });
    };
    
    window.confirmComplete = function(button) {
        Swal.fire({
            title: 'Selesaikan Tiket',
            text: 'Apakah Anda yakin ingin menyelesaikan tiket ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Selesaikan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#22c55e',
        }).then((result) => {
            if (result.isConfirmed) {
                button.closest('form').submit();
            }
        });
    };
    
    window.confirmClose = function(button) {
        Swal.fire({
            title: 'Tutup Tiket',
            text: 'Apakah Anda yakin ingin menutup tiket ini? Tiket tidak dapat diubah lagi.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Tutup',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#6b7280',
        }).then((result) => {
            if (result.isConfirmed) {
                button.closest('form').submit();
            }
        });
    };
    
    window.confirmResume = function(button) {
        Swal.fire({
            title: 'Lanjutkan Proses',
            text: 'Apakah Anda yakin ingin mengembalikan tiket ke status ON_PROCESS?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#3b82f6',
        }).then((result) => {
            if (result.isConfirmed) {
                button.closest('form').submit();
            }
        });
    };
    
    // DOM Ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Loaded - Transfer modal script ready');
        
        const uploadForm = document.getElementById('uploadFotoForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                const files = document.getElementById('foto_hasil').files;
                if (files.length === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: 'Silakan pilih foto terlebih dahulu!',
                        confirmButtonColor: '#f59e0b',
                    });
                    return false;
                }
                return true;
            });
        }
        
        // Event listener untuk tombol ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTransferModal();
            }
        });
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
    
    #imageViewer {
        transition: opacity 0.3s ease;
        cursor: pointer;
    }
    #viewerImage {
        transition: opacity 0.3s ease;
        cursor: default;
    }
    
    .swal2-popup {
        border-radius: 8px !important;
    }
    .swal2-title {
        font-size: 1.25rem !important;
    }
    .swal2-html-container {
        font-size: 0.875rem !important;
    }
    
    /* Modal animation */
    #transferModal {
        transition: opacity 0.3s ease;
    }
    #transferModal.hidden {
        display: none;
    }
    #transferModal:not(.hidden) {
        display: block;
    }
</style>
@endpush