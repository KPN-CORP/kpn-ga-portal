@extends('layouts.app-sidebar')

@section('content')
<div class="p-4 md:p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-800">{{ $tiket->judul }}</h2>
                    @php
                        $statusColors = [
                            'OPEN' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'ON_PROCESS' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'WAITING' => 'bg-orange-100 text-orange-800 border-orange-200',
                            'DONE' => 'bg-green-100 text-green-800 border-green-200',
                            'CLOSED' => 'bg-gray-100 text-gray-800 border-gray-200'
                        ];
                        $priorityColors = [
                            'URGENT' => 'bg-red-100 text-red-800 border-red-200',
                            'HIGH' => 'bg-orange-100 text-orange-800 border-orange-200',
                            'MEDIUM' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'LOW' => 'bg-gray-100 text-gray-800 border-gray-200'
                        ];
                    @endphp
                    <span class="px-3 py-1 text-sm font-medium rounded-full border {{ $statusColors[$tiket->status] }}">
                        {{ $tiket->status }}
                    </span>
                    <span class="px-3 py-1 text-sm font-medium rounded-full border {{ $priorityColors[$tiket->prioritas] }}">
                        {{ $tiket->prioritas }}
                    </span>
                </div>
                <p class="text-gray-600">{{ $tiket->nomor_tiket }}</p>
                <p class="text-sm text-gray-500">Dibuat: {{ $tiket->created_at->format('d/m/Y H:i') }}</p>
            </div>
            
            @if($tiket->status !== 'CLOSED')
            <div class="flex flex-wrap gap-2">
                @if($tiket->status === 'OPEN')
                <span class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 font-medium rounded-lg">
                    <i class="fas fa-clock mr-2"></i> Menunggu Penugasan
                </span>
                @elseif($tiket->status === 'ON_PROCESS')
                <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 font-medium rounded-lg">
                    <i class="fas fa-cog mr-2"></i> Sedang Diproses
                </span>
                @elseif($tiket->status === 'WAITING')
                <span class="inline-flex items-center px-4 py-2 bg-orange-100 text-orange-800 font-medium rounded-lg">
                    <i class="fas fa-hourglass-half mr-2"></i> Menunggu Respons
                </span>
                @elseif($tiket->status === 'DONE')
                <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 font-medium rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i> Selesai
                </span>
                @endif
            </div>
            @endif
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Dilaporkan</h3>
                <div class="flex items-center">
                    @php
                        $pelaporName = $tiket->pelapor ? $tiket->pelapor->name : '';
                        $pelaporInitial = substr($pelaporName, 0, 1);
                    @endphp
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center font-medium text-blue-700 mr-3">
                        {{ $pelaporInitial }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $pelaporName }}</p>
                        <p class="text-sm text-gray-500">{{ $tiket->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Kategori</h3>
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center mr-3">
                        <i class="fas fa-tag text-blue-600"></i>
                    </div>
                    <p class="font-medium text-gray-900">{{ $tiket->kategori->nama ?? '-' }}</p>
                </div>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Penanggung Jawab</h3>
                <div class="flex items-center">
                    @if($tiket->ditugaskanKe)
                        @php
                            if ($tiket->ditugaskanKe->user) {
                                $pjName = $tiket->ditugaskanKe->user->name;
                            } elseif ($tiket->ditugaskanKe->nama) {
                                $pjName = $tiket->ditugaskanKe->nama;
                            } else {
                                $pjName = 'Staff GA';
                            }
                            $pjInitial = substr($pjName, 0, 1);
                        @endphp
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center font-medium text-green-700 mr-3">
                            {{ $pjInitial }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $pjName }}</p>
                            @if($tiket->diproses_pada)
                            <p class="text-sm text-gray-500">{{ $tiket->diproses_pada->format('d/m/Y H:i') }}</p>
                            @endif
                        </div>
                    @else
                        <p class="text-gray-500 italic">Belum ditugaskan</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Deskripsi Masalah</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-700 whitespace-pre-line">{{ $tiket->deskripsi }}</p>
                </div>
            </div>
            
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">Diskusi Tiket</h3>
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-users mr-2"></i>
                            <span>{{ $tiket->komentar->count() }} pesan</span>
                        </div>
                    </div>
                </div>
                
                @if($tiket->status !== 'CLOSED')
                <div class="p-4 bg-gray-50" style="height: 400px; overflow-y: auto;" id="chatContainer">
                    @forelse($tiket->komentar as $komentar)
                        @if($komentar->pesan_sistem)
                            <div class="text-center my-4">
                                <div class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                    <i class="fas fa-robot mr-2"></i>
                                    @php
                                        $systemUserName = $komentar->pengguna && $komentar->pengguna->user 
                                            ? $komentar->pengguna->user->name 
                                            : 'System';
                                    @endphp
                                    {{ $systemUserName }} • {{ $komentar->created_at->format('d/m/Y H:i') }}
                                </div>
                                <div class="mt-2 text-sm text-gray-600 bg-white p-3 rounded-lg border border-gray-200 max-w-md mx-auto">
                                    {{ $komentar->komentar }}
                                </div>
                            </div>
                        @else
                            @php
                                $currentUserId = auth()->user()->pelanggan->id_pelanggan;
                                $penggunaName = $komentar->pengguna && $komentar->pengguna->user 
                                    ? $komentar->pengguna->user->name 
                                    : 'User';
                                $penggunaInitial = substr($penggunaName, 0, 1);
                                $isOwnMessage = $komentar->pengguna_id === $currentUserId;
                            @endphp
                            <div class="flex items-start mb-4 {{ $isOwnMessage ? 'justify-end' : '' }}">
                                @if(!$isOwnMessage)
                                    <div class="flex-shrink-0 mr-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center font-medium text-blue-700">
                                            {{ $penggunaInitial }}
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="{{ $isOwnMessage ? 'max-w-[70%]' : 'max-w-[70%]' }}">
                                    <div class="flex items-center mb-1 {{ $isOwnMessage ? 'justify-end' : '' }}">
                                        <span class="text-xs text-gray-500">{{ $penggunaName }}</span>
                                        <span class="text-xs text-gray-400 mx-2">•</span>
                                        <span class="text-xs text-gray-400">{{ $komentar->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    
                                    <div class="{{ $isOwnMessage ? 'bg-green-100' : 'bg-white' }} p-3 rounded-2xl {{ $isOwnMessage ? 'rounded-tr-none' : 'rounded-tl-none' }} border {{ $isOwnMessage ? 'border-green-200' : 'border-gray-200' }}">
                                        <p class="text-gray-800 whitespace-pre-line">{{ $komentar->komentar }}</p>
                                        
                                        @php
                                            $komentarLampiran = $tiket->lampiran->where('pengguna_id', $komentar->pengguna_id)
                                                ->where('created_at', '>=', $komentar->created_at->subMinute(1))
                                                ->where('created_at', '<=', $komentar->created_at->addMinute(1))
                                                ->first();
                                        @endphp
                                        @if($komentarLampiran)
                                            <div class="mt-2 pt-2 border-t border-gray-200">
                                                <div class="flex items-center">
                                                    <i class="fas fa-paperclip text-xs text-gray-500 mr-2"></i>
                                                    @php
                                                        $iconClass = '';
                                                        if (str_contains($komentarLampiran->tipe_file, 'image')) {
                                                            $iconClass = 'fas fa-image text-blue-600';
                                                        } elseif (str_contains($komentarLampiran->tipe_file, 'pdf')) {
                                                            $iconClass = 'fas fa-file-pdf text-red-600';
                                                        } elseif (str_contains($komentarLampiran->tipe_file, 'word')) {
                                                            $iconClass = 'fas fa-file-word text-blue-600';
                                                        } else {
                                                            $iconClass = 'fas fa-file text-gray-600';
                                                        }
                                                    @endphp
                                                    
                                                    @if(str_contains($komentarLampiran->tipe_file, 'image'))
                                                        <!-- TAMPILKAN THUMBNAIL GAMBAR LANGSUNG -->
                                                        <div class="mt-2">
                                                            <div class="relative inline-block group">
                                                                <img src="/help/tiket/lampiran/{{ $komentarLampiran->id }}/preview?thumb=true" 
                                                                     alt="{{ $komentarLampiran->nama_file }}"
                                                                     class="w-24 h-24 object-cover rounded-lg border border-gray-300 cursor-pointer hover:opacity-90 transition-opacity"
                                                                     onclick="previewFile('{{ $komentarLampiran->id }}', '{{ $komentarLampiran->nama_file }}')">
                                                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 rounded-lg transition-all cursor-pointer flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                                    <i class="fas fa-expand text-white text-lg"></i>
                                                                </div>
                                                            </div>
                                                            <div class="mt-1 flex items-center">
                                                                <span class="text-xs text-gray-600 truncate max-w-[180px]">{{ $komentarLampiran->nama_file }}</span>
                                                                <a href="/help/tiket/lampiran/{{ $komentarLampiran->id }}/download" 
                                                                   class="ml-2 text-xs text-blue-600 hover:text-blue-800"
                                                                   title="Download"
                                                                   onclick="event.stopPropagation();">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <a href="/help/tiket/lampiran/{{ $komentarLampiran->id }}/download" 
                                                           class="text-sm text-gray-700 hover:text-blue-600 flex items-center">
                                                            <i class="{{ $iconClass }} mr-2"></i>
                                                            {{ $komentarLampiran->nama_file }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    @if($isOwnMessage)
                                        <div class="text-right mt-1">
                                            <span class="text-xs text-gray-400"><i class="fas fa-check-double"></i></span>
                                        </div>
                                    @endif
                                </div>
                                
                                @if($isOwnMessage)
                                    @php
                                        $currentUser = auth()->user();
                                        $currentUserName = $currentUser->name ?? 'You';
                                        $currentUserInitial = substr($currentUserName, 0, 1);
                                    @endphp
                                    <div class="flex-shrink-0 ml-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center font-medium text-green-700">
                                            {{ $currentUserInitial }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @empty
                        <div class="flex flex-col items-center justify-center h-64 text-gray-400">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                <i class="fas fa-comments text-2xl"></i>
                            </div>
                            <p class="text-lg font-medium text-gray-500">Belum ada diskusi</p>
                            <p class="text-sm text-gray-400 mt-2">Mulai percakapan tentang tiket ini</p>
                        </div>
                    @endforelse
                </div>
                
                <div class="border-t border-gray-200 p-4">
                    <form action="{{ route('help.tiket.add-komentar', $tiket) }}" method="POST" enctype="multipart/form-data" id="chatForm">
                        @csrf
                        <div class="space-y-3">
                            <textarea name="komentar" 
                                      id="chatInput"
                                      rows="2"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                      placeholder="Ketik pesan..."
                                      required></textarea>
                            
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <input type="file" 
                                           id="chat-lampiran" 
                                           name="lampiran[]" 
                                           multiple
                                           class="hidden"
                                           accept="image/*,.pdf,.doc,.docx">
                                    <button type="button" 
                                            onclick="document.getElementById('chat-lampiran').click()"
                                            class="inline-flex items-center text-gray-500 hover:text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-100">
                                        <i class="fas fa-paperclip mr-2"></i> Lampirkan File
                                    </button>
                                    <span id="file-count" class="text-xs text-gray-500 ml-2"></span>
                                </div>
                                <button type="submit"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                    <i class="fas fa-paper-plane mr-2"></i> Kirim
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @else
                <div class="p-8 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <i class="fas fa-lock text-2xl text-gray-400"></i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-700 mb-2">Chat Dinonaktifkan</h4>
                    <p class="text-gray-500">Diskusi telah ditutup karena tiket sudah selesai</p>
                </div>
                @endif
            </div>
        </div>
        
        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Timeline</h3>
                <div class="space-y-4">
                    @foreach($tiket->logStatus as $log)
                    @php
                        $logUserName = $log->pengguna && $log->pengguna->user 
                            ? $log->pengguna->user->name 
                            : 'Unknown';
                    @endphp
                    <div class="relative pl-8 pb-4 last:pb-0">
                        @if(!$loop->last)
                            <div class="absolute left-3 top-3 bottom-0 w-0.5 bg-gray-200"></div>
                        @endif
                        
                        <div class="absolute left-0 top-1 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-blue-600 text-xs"></i>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-start mb-1">
                                <p class="font-medium text-gray-900">{{ $log->status_baru }}</p>
                                <span class="text-xs text-gray-500">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($logUserName)
                            <p class="text-sm text-gray-600">Oleh: {{ $logUserName }}</p>
                            @endif
                            @if($log->catatan)
                            <div class="mt-2 p-2 bg-gray-50 rounded text-sm text-gray-600">
                                {{ $log->catatan }}
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Lampiran</h3>
                <div class="space-y-4">
                    @php
                        $imageAttachments = $tiket->lampiran->filter(function($item) {
                            return str_contains($item->tipe_file, 'image');
                        });
                        $otherAttachments = $tiket->lampiran->filter(function($item) {
                            return !str_contains($item->tipe_file, 'image');
                        });
                    @endphp
                    
                    <!-- GAMBAR: Tampil Langsung -->
                    @if($imageAttachments->count() > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Before</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($imageAttachments as $lampiran)
                            @php
                                $lampiranUser = \App\Models\Pelanggan::find($lampiran->pengguna_id);
                                $uploaderName = $lampiranUser && $lampiranUser->user 
                                    ? $lampiranUser->user->name 
                                    : 'Unknown';
                            @endphp
                            <div class="group relative cursor-pointer" onclick="previewFile('{{ $lampiran->id }}', '{{ $lampiran->nama_file }}')">
                                <!-- Thumbnail Gambar -->
                                <div class="aspect-square overflow-hidden rounded-lg border border-gray-300 bg-gray-100">
                                    <img src="/help/tiket/lampiran/{{ $lampiran->id }}/preview?thumb=true" 
                                         alt="{{ $lampiran->nama_file }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                        <i class="fas fa-expand text-white text-xl"></i>
                                    </div>
                                </div>
                                
                                <!-- Overlay Info -->
                                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-2 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs truncate">{{ $lampiran->nama_file }}</span>
                                        <button onclick="event.stopPropagation(); window.open('/help/tiket/lampiran/{{ $lampiran->id }}/download', '_blank')" 
                                                class="text-white hover:text-blue-300"
                                                title="Download">
                                            <i class="fas fa-download text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- File Info (always visible) -->
                                <div class="mt-1">
                                    <p class="text-xs text-gray-500 truncate">{{ $lampiran->nama_file }}</p>
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-xs text-gray-400">{{ $lampiran->created_at->format('d/m H:i') }}</span>
                                        <span class="text-xs px-1.5 py-0.5 bg-gray-100 rounded text-gray-600">{{ $lampiran->tipe }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    <!-- FILE LAINNYA -->
                    @if($otherAttachments->count() > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-3">File Lainnya</h4>
                        <div class="space-y-2">
                            @foreach($otherAttachments as $lampiran)
                            @php
                                $lampiranUser = \App\Models\Pelanggan::find($lampiran->pengguna_id);
                                $uploaderName = $lampiranUser && $lampiranUser->user 
                                    ? $lampiranUser->user->name 
                                    : 'Unknown';
                                $fileSize = '';
                                if ($lampiran->ukuran_file < 1024) {
                                    $fileSize = $lampiran->ukuran_file . ' B';
                                } elseif ($lampiran->ukuran_file < 1048576) {
                                    $fileSize = round($lampiran->ukuran_file / 1024, 1) . ' KB';
                                } else {
                                    $fileSize = round($lampiran->ukuran_file / 1048576, 1) . ' MB';
                                }
                                
                                $iconClass = '';
                                if (str_contains($lampiran->tipe_file, 'pdf')) {
                                    $iconClass = 'fas fa-file-pdf text-red-600';
                                } elseif (str_contains($lampiran->tipe_file, 'word')) {
                                    $iconClass = 'fas fa-file-word text-blue-600';
                                } else {
                                    $iconClass = 'fas fa-file text-gray-600';
                                }
                            @endphp
                            <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center min-w-0 flex-1">
                                    <div class="flex-shrink-0 mr-3">
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                            <i class="{{ $iconClass }}"></i>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $lampiran->nama_file }}</p>
                                        <div class="flex items-center text-xs text-gray-500 mt-1">
                                            <span class="mr-3">{{ $lampiran->created_at->format('d/m/Y H:i') }}</span>
                                            <span class="mr-2">{{ $fileSize }}</span>
                                            <span class="px-2 py-0.5 bg-gray-100 rounded text-gray-600">{{ $lampiran->tipe }}</span>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-1">Diunggah oleh: {{ $uploaderName }}</p>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <a href="/help/tiket/lampiran/{{ $lampiran->id }}/download" 
                                       class="text-green-600 hover:text-green-800 p-1 rounded hover:bg-green-50"
                                       title="Download">
                                        <i class="fas fa-download text-sm"></i>
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    @if($tiket->lampiran->count() === 0)
                    <div class="text-center py-6">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-gray-100 rounded-full mb-4">
                            <i class="fas fa-paperclip text-gray-400"></i>
                        </div>
                        <p class="text-gray-500">Tidak ada lampiran</p>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Status</h3>
                <div class="space-y-3">
                    @if($tiket->status === 'OPEN')
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">Tiket Anda sedang menunggu untuk ditugaskan ke petugas GA.</p>
                        </div>
                    </div>
                    @elseif($tiket->status === 'ON_PROCESS')
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-cog text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">Tiket Anda sedang diproses oleh petugas GA.</p>
                            @if($tiket->diproses_pada)
                            <p class="text-xs text-gray-500 mt-1">Diproses sejak: {{ $tiket->diproses_pada->format('d/m/Y H:i') }}</p>
                            @endif
                        </div>
                    </div>
                    @elseif($tiket->status === 'WAITING')
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-hourglass-half text-orange-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">Petugas GA membutuhkan informasi tambahan dari Anda.</p>
                            <p class="text-sm font-medium text-gray-800 mt-1">Silakan berikan respons melalui kolom chat di atas.</p>
                        </div>
                    </div>
                    @elseif($tiket->status === 'DONE')
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">Tiket Anda telah diselesaikan oleh petugas GA.</p>
                            @if($tiket->diselesaikan_pada)
                            <p class="text-xs text-gray-500 mt-1">Diselesaikan: {{ $tiket->diselesaikan_pada->format('d/m/Y H:i') }}</p>
                            @endif
                        </div>
                    </div>
                    @elseif($tiket->status === 'CLOSED')
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-lock text-gray-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">Tiket ini telah ditutup.</p>
                            @if($tiket->ditutup_pada)
                            <p class="text-xs text-gray-500 mt-1">Ditutup: {{ $tiket->ditutup_pada->format('d/m/Y H:i') }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Preview Gambar -->
<div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-90 z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-6xl w-full max-h-[95vh] overflow-hidden shadow-2xl">
        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800" id="previewFileName">Preview Gambar</h3>
            <div class="flex items-center space-x-2">
                <span id="previewFileInfo" class="text-sm text-gray-600 mr-4">-</span>
                <div class="flex space-x-2">
                    <button onclick="rotateImage(-90)" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded text-gray-700 transition-colors" title="Rotate Kiri">
                        <i class="fas fa-undo-alt"></i>
                    </button>
                    <button onclick="rotateImage(90)" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded text-gray-700 transition-colors" title="Rotate Kanan">
                        <i class="fas fa-redo-alt"></i>
                    </button>
                    <button onclick="zoomInOut()" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded text-gray-700 transition-colors" title="Zoom In/Out">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button onclick="downloadCurrentPreview()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors" title="Download">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
                <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700 ml-2">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-4 overflow-auto max-h-[calc(95vh-8rem)] flex items-center justify-center bg-gray-900">
            <img id="previewImage" src="" alt="Preview" class="max-w-full max-h-[calc(95vh-10rem)] object-contain rounded transition-transform duration-300">
        </div>
        <div class="p-3 border-t bg-gray-50 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                <span id="previewDimensions">-</span>
                <span class="mx-2">•</span>
                <span id="previewZoomLevel">100%</span>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="resetPreview()" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded text-gray-700 text-sm">
                    <i class="fas fa-sync-alt mr-1"></i> Reset
                </button>
                <div class="flex items-center space-x-2">
                    <button onclick="changeZoom(-10)" class="w-8 h-8 flex items-center justify-center bg-gray-200 hover:bg-gray-300 rounded">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button onclick="changeZoom(10)" class="w-8 h-8 flex items-center justify-center bg-gray-200 hover:bg-gray-300 rounded">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Variabel global untuk kontrol preview
let currentRotation = 0;
let currentZoom = 100;
let currentFileId = null;
let currentFileName = '';

// Auto-scroll chat to bottom
const chatContainer = document.getElementById('chatContainer');
if (chatContainer) {
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

// Auto-resize textarea
const chatInput = document.getElementById('chatInput');
if (chatInput) {
    chatInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    chatInput.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            document.getElementById('chatForm').submit();
        }
    });
}

// File upload for chat
const chatFileInput = document.getElementById('chat-lampiran');
const fileCountSpan = document.getElementById('file-count');

if (chatFileInput) {
    chatFileInput.addEventListener('change', function() {
        const files = this.files;
        const count = files.length;
        
        if (fileCountSpan) {
            fileCountSpan.textContent = count > 0 ? `${count} file dipilih` : '';
        }
    });
}

// Preview File Function - Buka Popup Saat Klik Gambar
function previewFile(id, fileName) {
    currentFileId = id;
    currentFileName = fileName || 'image.jpg';
    currentRotation = 0;
    currentZoom = 100;
    
    const modal = document.getElementById('previewModal');
    const previewImage = document.getElementById('previewImage');
    const fileNameSpan = document.getElementById('previewFileName');
    const fileInfoSpan = document.getElementById('previewFileInfo');
    const dimensionsSpan = document.getElementById('previewDimensions');
    const zoomLevelSpan = document.getElementById('previewZoomLevel');
    
    // Reset image
    previewImage.style.transform = 'rotate(0deg) scale(1)';
    previewImage.classList.remove('cursor-zoom-in', 'cursor-zoom-out');
    previewImage.classList.add('cursor-zoom-in');
    
    // Show loading
    fileNameSpan.textContent = 'Memuat gambar...';
    fileInfoSpan.textContent = 'Sedang memuat...';
    dimensionsSpan.textContent = '-';
    zoomLevelSpan.textContent = '100%';
    
    // Show modal dengan animasi
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    
    // Load image dengan URL full size (bukan thumbnail)
    const previewUrl = `/help/tiket/lampiran/${id}/preview?_=${new Date().getTime()}`;
    
    previewImage.onload = function() {
        fileNameSpan.textContent = fileName || 'Preview Gambar';
        fileInfoSpan.textContent = `Format: ${this.naturalWidth} × ${this.naturalHeight} pixels`;
        dimensionsSpan.textContent = `${this.naturalWidth} × ${this.naturalHeight}`;
        zoomLevelSpan.textContent = '100%';
        
        // Auto-fit image
        const modalContent = modal.querySelector('.max-h-\\[calc\\(95vh-8rem\\)\\]');
        const modalWidth = modalContent.clientWidth;
        const modalHeight = modalContent.clientHeight;
        
        if (this.naturalWidth > modalWidth * 0.8 || this.naturalHeight > modalHeight * 0.8) {
            const widthRatio = modalWidth * 0.8 / this.naturalWidth;
            const heightRatio = modalHeight * 0.8 / this.naturalHeight;
            const minRatio = Math.min(widthRatio, heightRatio);
            
            if (minRatio < 1) {
                currentZoom = Math.round(minRatio * 100);
                zoomLevelSpan.textContent = `${currentZoom}%`;
                previewImage.style.transform = `rotate(0deg) scale(${minRatio})`;
            }
        }
        
        // Add click to zoom
        previewImage.addEventListener('click', toggleZoom);
    };
    
    previewImage.onerror = function() {
        fileNameSpan.textContent = 'Gagal memuat gambar';
        fileInfoSpan.textContent = 'Error: Gambar tidak dapat dimuat';
        this.alt = 'Gambar tidak dapat dimuat';
        this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YzZjRmNiIvPjx0ZXh0IHg9IjEwMCIgeT0iMTAwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmaWxsPSIjNmI3MjgwIj5HYW1iYXIgaWRhayBkYXBhdCBkaW11YXQ8L3RleHQ+PC9zdmc+';
    };
    
    previewImage.src = previewUrl;
}

function rotateImage(degrees) {
    currentRotation += degrees;
    const previewImage = document.getElementById('previewImage');
    previewImage.style.transform = `rotate(${currentRotation}deg) scale(${currentZoom / 100})`;
}

function zoomInOut() {
    const previewImage = document.getElementById('previewImage');
    if (currentZoom === 100) {
        currentZoom = 200;
        previewImage.classList.remove('cursor-zoom-in');
        previewImage.classList.add('cursor-zoom-out');
    } else {
        currentZoom = 100;
        previewImage.classList.remove('cursor-zoom-out');
        previewImage.classList.add('cursor-zoom-in');
    }
    updateZoom();
}

function toggleZoom() {
    const previewImage = document.getElementById('previewImage');
    if (currentZoom === 100) {
        currentZoom = 200;
        previewImage.classList.remove('cursor-zoom-in');
        previewImage.classList.add('cursor-zoom-out');
    } else {
        currentZoom = 100;
        previewImage.classList.remove('cursor-zoom-out');
        previewImage.classList.add('cursor-zoom-in');
    }
    updateZoom();
}

function changeZoom(delta) {
    currentZoom = Math.max(25, Math.min(500, currentZoom + delta));
    updateZoom();
}

function updateZoom() {
    const previewImage = document.getElementById('previewImage');
    const zoomLevelSpan = document.getElementById('previewZoomLevel');
    
    previewImage.style.transform = `rotate(${currentRotation}deg) scale(${currentZoom / 100})`;
    zoomLevelSpan.textContent = `${currentZoom}%`;
}

function resetPreview() {
    currentRotation = 0;
    currentZoom = 100;
    const previewImage = document.getElementById('previewImage');
    previewImage.style.transform = 'rotate(0deg) scale(1)';
    previewImage.classList.remove('cursor-zoom-out');
    previewImage.classList.add('cursor-zoom-in');
    document.getElementById('previewZoomLevel').textContent = '100%';
}

function downloadCurrentPreview() {
    if (currentFileId) {
        window.open(`/help/tiket/lampiran/${currentFileId}/download`, '_blank');
    }
}

function closePreview() {
    const modal = document.getElementById('previewModal');
    const image = document.getElementById('previewImage');
    
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    image.src = '';
    image.style.transform = 'rotate(0deg) scale(1)';
    
    // Remove event listener
    image.removeEventListener('click', toggleZoom);
    
    currentRotation = 0;
    currentZoom = 100;
    currentFileId = null;
    currentFileName = '';
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('previewModal').classList.contains('hidden')) {
        closePreview();
    }
});

// Close modal when clicking outside
const previewModal = document.getElementById('previewModal');
if (previewModal) {
    previewModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closePreview();
        }
    });
}

// Prevent form submission on Enter in textarea
if (chatInput) {
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (this.value.trim() !== '') {
                document.getElementById('chatForm').submit();
            }
        }
    });
}

// Add hover effect for all image thumbnails
document.addEventListener('DOMContentLoaded', function() {
    const imageThumbnails = document.querySelectorAll('img[src*="/preview?thumb=true"]');
    imageThumbnails.forEach(img => {
        img.addEventListener('mouseenter', function() {
            this.parentElement.classList.add('scale-105');
        });
        img.addEventListener('mouseleave', function() {
            this.parentElement.classList.remove('scale-105');
        });
    });
});
</script>
@endpush

<style>
    /* Chat Container Scrollbar */
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
    
    /* Gradient Colors */
    .from-blue-100 { --tw-gradient-from: #dbeafe; }
    .to-blue-50 { --tw-gradient-to: #eff6ff; }
    .from-green-100 { --tw-gradient-from: #d1fae5; }
    .to-green-50 { --tw-gradient-to: #ecfdf5; }
    
    /* Background Colors */
    .bg-green-100 { background-color: #d1fae5; }
    .bg-green-50 { background-color: #ecfdf5; }
    .bg-blue-50 { background-color: #eff6ff; }
    .bg-gray-100 { background-color: #f3f4f6; }
    .bg-red-50 { background-color: #fef2f2; }
    .bg-yellow-100 { background-color: #fef3c7; }
    .bg-orange-100 { background-color: #ffedd5; }
    
    /* Border Colors */
    .border-green-200 { border-color: #a7f3d0; }
    .border-blue-200 { border-color: #bfdbfe; }
    .border-gray-300 { border-color: #d1d5db; }
    .border-red-200 { border-color: #fecaca; }
    .border-yellow-200 { border-color: #fde68a; }
    .border-orange-200 { border-color: #fed7aa; }
    
    /* Text Colors */
    .text-blue-600 { color: #2563eb; }
    .text-green-600 { color: #059669; }
    .text-red-600 { color: #dc2626; }
    .text-yellow-800 { color: #92400e; }
    .text-orange-800 { color: #9a3412; }
    
    /* Modal Styles */
    #previewModal {
        transition: opacity 0.3s ease;
        z-index: 9999 !important;
    }
    
    #previewModal:not(.hidden) {
        display: flex !important;
        opacity: 1;
    }
    
    #previewModal.hidden {
        opacity: 0;
        pointer-events: none;
    }
    
    #previewImage {
        transition: transform 0.3s ease;
    }
    
    .cursor-zoom-in {
        cursor: zoom-in;
    }
    
    .cursor-zoom-out {
        cursor: zoom-out;
    }
    
    .shadow-2xl {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    
    /* Image Thumbnail Hover Effects */
    .group:hover .group-hover\:scale-105 {
        transform: scale(1.05);
    }
    
    .aspect-square {
        aspect-ratio: 1 / 1;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        #previewModal .flex-col {
            flex-direction: column;
        }
        
        #previewModal .space-x-2 {
            margin-top: 0.5rem;
        }
        
        .grid-cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .sm\:grid-cols-3 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        @media (max-width: 480px) {
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
            
            .sm\:grid-cols-3 {
                grid-template-columns: 1fr;
            }
        }
    }
    
    /* Animation for image hover */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fade-in-up {
        animation: fadeInUp 0.3s ease-out;
    }
    
    /* Smooth transitions */
    .transition-transform {
        transition-property: transform;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 300ms;
    }
    
    .transition-all {
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 300ms;
    }
</style>
@endsection