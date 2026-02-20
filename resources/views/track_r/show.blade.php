@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Detail Dokumen</h1>
            <p class="text-sm text-gray-500 mt-1">Nomor: <span class="font-mono">{{ $document->nomor_dokumen }}</span></p>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('track-r.index') }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <a href="{{ route('track-r.pdf', $document->id) }}"
               target="_blank"
               class="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-semibold hover:bg-red-200 transition flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
        </div>
    </div>

    {{-- MAIN CONTENT GRID --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- KOLOM KIRI (2/3 - Informasi Utama) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- INFO DOKUMEN --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <h3 class="font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-file-alt"></i>
                        Informasi Dokumen
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Judul Dokumen</label>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-sm font-medium text-gray-800">{{ $document->judul }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Pengirim</label>
                                <div class="bg-gray-50 p-3 rounded-lg flex items-center gap-2">
                                    <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-green-600 text-xs"></i>
                                    </div>
                                    <p class="text-sm font-medium text-gray-800">{{ $document->pengirim->name ?? 'Tidak diketahui' }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Tanggal Dikirim</label>
                                <div class="bg-gray-50 p-3 rounded-lg flex items-center gap-2">
                                    <i class="fas fa-calendar-alt text-gray-400 text-sm"></i>
                                    <p class="text-sm font-medium text-gray-800">
                                        {{ $document->created_at->format('d F Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Penerima</label>
                                <div class="bg-gray-50 p-3 rounded-lg flex items-center gap-2">
                                    <div class="w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-yellow-600 text-xs"></i>
                                    </div>
                                    <p class="text-sm font-medium text-gray-800">{{ $document->penerima->name ?? 'Tidak diketahui' }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Keterangan</label>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-sm text-gray-700">{{ $document->keterangan ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FOTO DOKUMEN --}}
            @if($document->fotos && $document->fotos->count() > 0)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4 flex items-center justify-between">
                    <h3 class="font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-images"></i>
                        Lampiran Dokumen
                    </h3>
                    <span class="text-xs bg-white/20 text-white px-3 py-1.5 rounded-full">
                        {{ $document->fotos->count() }} file
                    </span>
                </div>
                <div class="p-6">
                    <p class="text-xs text-gray-500 mb-4 flex items-center gap-2 bg-blue-50 p-3 rounded-lg border border-blue-100">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        Klik gambar untuk memperbesar. Jika gambar tidak tampil, gunakan tombol download.
                    </p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($document->fotos as $foto)
                        @php
                            $ext = strtolower($foto->tipe);
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
                        @endphp
                        
                        <div class="group relative bg-white border rounded-xl overflow-hidden hover:shadow-lg transition-all duration-200">
                            {{-- THUMBNAIL / PREVIEW --}}
                            <div class="aspect-square bg-gray-50 relative cursor-pointer overflow-hidden"
                                 @if($isImage)
                                 onclick="openLightbox('{{ route('track-foto.view', $foto->id) }}', '{{ $foto->nama_file }}')"
                                 @else
                                 onclick="showFileInfo('{{ $foto->nama_file }}', '{{ $foto->tipe }}', '{{ number_format($foto->ukuran / 1024, 1) }}')"
                                 @endif>
                                
                                @if($isImage)
                                    <img src="{{ route('track-foto.view', $foto->id) }}" 
                                         alt="{{ $foto->nama_file }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                         onerror="this.onerror=null; this.src='{{ route('track-foto.view', $foto->id) }}?nocache=' + Date.now();"
                                         loading="lazy">
                                    
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center p-4 bg-gradient-to-br from-gray-50 to-gray-100">
                                        @php
                                            $icon = match($ext) {
                                                'pdf' => 'fa-file-pdf text-red-500',
                                                'doc', 'docx' => 'fa-file-word text-blue-500',
                                                'xls', 'xlsx' => 'fa-file-excel text-green-600',
                                                'ppt', 'pptx' => 'fa-file-powerpoint text-orange-500',
                                                'txt' => 'fa-file-alt text-gray-500',
                                                'zip', 'rar' => 'fa-file-archive text-yellow-600',
                                                default => 'fa-file text-gray-500'
                                            };
                                        @endphp
                                        <i class="fas {{ $icon }} text-5xl mb-3"></i>
                                        <span class="text-xs font-medium px-2 py-1 bg-white rounded-full shadow-sm">
                                            {{ strtoupper($ext) }}
                                        </span>
                                    </div>
                                @endif
                                
                                {{-- HOVER ACTIONS --}}
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    @if($isImage)
                                    <span class="w-12 h-12 bg-white/90 rounded-full flex items-center justify-center text-blue-600 hover:bg-white transform hover:scale-110 transition shadow-lg">
                                        <i class="fas fa-search-plus text-xl"></i>
                                    </span>
                                    @else
                                    <span class="w-12 h-12 bg-white/90 rounded-full flex items-center justify-center text-gray-600 hover:bg-white transform hover:scale-110 transition shadow-lg">
                                        <i class="fas fa-info-circle text-xl"></i>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- INFO FILE --}}
                            <div class="p-3 border-t bg-white">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas {{ $isImage ? 'fa-image text-green-600' : 'fa-file text-gray-600' }} text-xs"></i>
                                    <p class="text-xs font-medium truncate flex-1 text-gray-700" title="{{ $foto->nama_file }}">
                                        {{ Str::limit($foto->nama_file, 20) }}
                                    </p>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                        {{ number_format($foto->ukuran / 1024, 1) }} KB
                                    </span>
                                    <div class="flex gap-1">
                                        @if($isImage)
                                        <a href="{{ route('track-foto.view', $foto->id) }}" 
                                           target="_blank"
                                           class="w-7 h-7 bg-green-50 text-green-600 rounded flex items-center justify-center hover:bg-green-100 transition"
                                           title="Lihat"
                                           onclick="event.stopPropagation()">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        @endif
                                        <a href="{{ route('track-foto.download', $foto->id) }}"
                                           class="w-7 h-7 bg-blue-50 text-blue-600 rounded flex items-center justify-center hover:bg-blue-100 transition"
                                           title="Download"
                                           onclick="event.stopPropagation()">
                                            <i class="fas fa-download text-xs"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-image text-gray-400 text-3xl"></i>
                </div>
                <p class="text-gray-500 font-medium">Tidak ada lampiran file</p>
                <p class="text-sm text-gray-400 mt-1">Dokumen ini tidak memiliki file lampiran</p>
            </div>
            @endif
        </div>

        {{-- KOLOM KANAN (1/3 - Status & Aksi) --}}
        <div class="space-y-6">
            {{-- CARD STATUS --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                    <h3 class="font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        Status Dokumen
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        {{-- Status Badge --}}
                        <div class="text-center">
                            @php
                                $statusColors = [
                                    'dikirim' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'diterima' => 'bg-green-100 text-green-700 border-green-200',
                                    'ditolak' => 'bg-red-100 text-red-700 border-red-200',
                                    'diproses' => 'bg-orange-100 text-orange-700 border-orange-200',
                                    'diteruskan' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'selesai' => 'bg-green-100 text-green-700 border-green-200',
                                ];
                                $statusIcons = [
                                    'dikirim' => 'fa-paper-plane',
                                    'diterima' => 'fa-check-circle',
                                    'ditolak' => 'fa-times-circle',
                                    'diproses' => 'fa-spinner',
                                    'diteruskan' => 'fa-share-alt',
                                    'selesai' => 'fa-flag-checkered',
                                ];
                                $status = strtolower($document->status);
                            @endphp
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">
                                <i class="fas {{ $statusIcons[$status] ?? 'fa-circle' }}"></i>
                                <span class="font-semibold">{{ strtoupper($document->status) }}</span>
                            </div>
                        </div>

                        {{-- Info Tambahan --}}
                        <div class="border-t pt-4 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">Nomor Dokumen</span>
                                <span class="text-sm font-mono font-medium">{{ $document->nomor_dokumen }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">Dibuat</span>
                                <span class="text-sm">{{ $document->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">Terakhir Update</span>
                                <span class="text-sm">{{ $document->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>

                        {{-- Quick Stats --}}
                        <div class="grid grid-cols-2 gap-3 border-t pt-4">
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-blue-600">{{ $document->logs->count() }}</div>
                                <div class="text-xs text-gray-500">Total Logs</div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-green-600">{{ $document->fotos->count() }}</div>
                                <div class="text-xs text-gray-500">Lampiran</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ACTION BUTTONS --}}
            @if(auth()->id() == $document->penerima_id && $document->status == 'dikirim')
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <h3 class="font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-tasks"></i>
                        Aksi Dokumen
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    {{-- TERIMA --}}
                    <form action="{{ route('track-r.terima', $document->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2 shadow-sm">
                            <i class="fas fa-check-circle"></i>
                            Terima Dokumen
                        </button>
                    </form>
                    
                    {{-- TOLAK --}}
                    <button type="button" 
                            onclick="toggleTolakForm()"
                            class="w-full py-3 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition flex items-center justify-center gap-2 shadow-sm">
                        <i class="fas fa-times-circle"></i>
                        Tolak Dokumen
                    </button>
                    
                    {{-- TERUSKAN --}}
                    <button type="button" 
                            onclick="toggleTeruskanForm()"
                            class="w-full py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2 shadow-sm">
                        <i class="fas fa-share-alt"></i>
                        Teruskan
                    </button>
                    
                    {{-- FORM TOLAK --}}
                    <div id="tolakForm" class="hidden mt-4 p-4 border rounded-lg bg-red-50">
                        <form action="{{ route('track-r.tolak', $document->id) }}" method="POST">
                            @csrf
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Alasan Penolakan <span class="text-red-500">*</span>
                            </label>
                            <textarea name="catatan" rows="3" required
                                      class="w-full border border-red-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="Berikan alasan penolakan..."></textarea>
                            
                            <div class="flex justify-end gap-3 mt-4">
                                <button type="button" 
                                        onclick="toggleTolakForm()"
                                        class="px-4 py-2 text-gray-700 hover:text-gray-900">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 flex items-center gap-2">
                                    <i class="fas fa-times"></i> Tolak
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    {{-- FORM TERUSKAN --}}
                    <div id="teruskanForm" class="hidden mt-4 p-4 border rounded-lg bg-blue-50">
                        <form action="{{ route('track-r.teruskan', $document->id) }}" method="POST">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Teruskan ke <span class="text-red-500">*</span>
                                    </label>
                                    <select name="penerima_id" required
                                            class="w-full border border-blue-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">-- Pilih Penerima --</option>
                                        @php
                                            $users = App\Models\User::where('id', '!=', auth()->id())
                                                                   ->where('id', '!=', $document->pengirim_id)
                                                                   ->orderBy('name')->get();
                                        @endphp
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Catatan (Opsional)
                                    </label>
                                    <textarea name="catatan" rows="3"
                                              class="w-full border border-blue-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                              placeholder="Tambahkan catatan..."></textarea>
                                </div>
                            </div>
                            
                            <div class="flex justify-end gap-3 mt-4">
                                <button type="button" 
                                        onclick="toggleTeruskanForm()"
                                        class="px-4 py-2 text-gray-700 hover:text-gray-900">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 flex items-center gap-2">
                                    <i class="fas fa-share"></i> Teruskan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- RIWAYAT TRACKING --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4 flex items-center justify-between">
            <h3 class="font-semibold text-white flex items-center gap-2">
                <i class="fas fa-history"></i>
                Riwayat Tracking
            </h3>
            <span class="text-xs bg-white/20 text-white px-3 py-1.5 rounded-full">
                {{ $document->logs->count() }} event
            </span>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @forelse($document->logs as $log)
                <div class="flex gap-4 p-4 border rounded-lg hover:bg-gray-50 transition">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        @php
                            $logIcon = match($log->aksi) {
                                'kirim' => 'paper-plane',
                                'terima' => 'check',
                                'tolak' => 'times',
                                'teruskan' => 'share',
                                default => 'history'
                            };
                            $logColor = match($log->aksi) {
                                'kirim' => 'text-blue-600',
                                'terima' => 'text-green-600',
                                'tolak' => 'text-red-600',
                                'teruskan' => 'text-purple-600',
                                default => 'text-gray-600'
                            };
                        @endphp
                        <i class="fas fa-{{ $logIcon }} {{ $logColor }}"></i>
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800">
                                    {{ ucfirst($log->aksi) }} 
                                    <span class="text-gray-500">oleh</span>
                                    {{ $log->dariUser->name ?? 'System' }}
                                </p>
                                @if($log->catatan)
                                    <p class="text-sm text-gray-600 mt-1 italic bg-gray-50 p-2 rounded">"{{ $log->catatan }}"</p>
                                @endif
                                @if($log->ke_user_id && $log->keUser)
                                    <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                        <i class="fas fa-arrow-right text-blue-500"></i> 
                                        Diteruskan ke: <span class="font-medium">{{ $log->keUser->name }}</span>
                                    </p>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 whitespace-nowrap ml-4 bg-gray-100 px-2 py-1 rounded">
                                <i class="far fa-clock mr-1"></i>
                                {{ $log->created_at->format('d M Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-history text-5xl mb-3 text-gray-300"></i>
                    <p class="font-medium">Belum ada riwayat tracking</p>
                    <p class="text-sm text-gray-400 mt-1">Riwayat akan muncul saat dokumen diproses</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ACCESS DENIED NOTIFICATION --}}
    @if(auth()->id() != $document->pengirim_id && auth()->id() != $document->penerima_id)
    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-red-800 mb-2">Akses Ditolak</h3>
                <p class="text-red-700">
                    Anda tidak memiliki akses untuk melihat dokumen ini. Hanya pengirim dan penerima yang berhak mengakses dokumen.
                </p>
                <a href="{{ route('track-r.index') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>
    @endif

</div>

{{-- LIGHTBOX MODAL --}}
<div id="lightboxModal" class="fixed inset-0 z-50 hidden" onclick="closeLightbox()">
    <div class="absolute inset-0 bg-black bg-opacity-95"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative max-w-7xl max-h-full" onclick="event.stopPropagation()">
            {{-- Tombol Close --}}
            <button onclick="closeLightbox()" 
                    class="absolute -top-14 right-0 text-white hover:text-gray-300 text-3xl z-50 w-12 h-12 flex items-center justify-center bg-black/50 rounded-full hover:bg-black/70 transition border border-white/20">
                <i class="fas fa-times"></i>
            </button>
            
            {{-- Navigasi --}}
            <button onclick="prevImage()" 
                    class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-3xl z-50 w-12 h-12 flex items-center justify-center bg-black/50 rounded-full hover:bg-black/70 transition border border-white/20">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button onclick="nextImage()" 
                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-3xl z-50 w-12 h-12 flex items-center justify-center bg-black/50 rounded-full hover:bg-black/70 transition border border-white/20">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            {{-- Gambar --}}
            <img id="lightboxImage" src="" alt="" 
                 class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl border border-white/10">
            
            {{-- Caption --}}
            <div id="lightboxCaption" class="absolute -bottom-12 left-0 right-0 text-center text-white text-sm">
            </div>
            
            {{-- Counter --}}
            <div id="lightboxCounter" class="absolute top-4 left-4 text-white text-sm bg-black/50 px-3 py-1.5 rounded-full border border-white/20">
            </div>
            
            {{-- Download button --}}
            <a id="lightboxDownload" href="#" target="_blank"
               class="absolute top-4 right-4 text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-lg border border-blue-400">
                <i class="fas fa-download"></i> Download
            </a>
        </div>
    </div>
</div>

{{-- MODAL INFO FILE --}}
<div id="fileInfoModal" class="fixed inset-0 z-50 hidden" onclick="closeFileInfo()">
    <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    Informasi File
                </h3>
                <button onclick="closeFileInfo()" class="text-gray-500 hover:text-gray-700 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label class="text-xs text-gray-500 block mb-1">Nama File</label>
                    <p id="infoFileName" class="text-sm font-medium text-gray-800 break-words"></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <label class="text-xs text-gray-500 block mb-1">Tipe File</label>
                        <p id="infoFileType" class="text-sm font-medium text-gray-800"></p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <label class="text-xs text-gray-500 block mb-1">Ukuran</label>
                        <p id="infoFileSize" class="text-sm font-medium text-gray-800"></p>
                    </div>
                </div>
                <div class="text-sm text-gray-600 bg-yellow-50 p-4 rounded-lg flex items-start gap-3 border border-yellow-200">
                    <i class="fas fa-info-circle text-yellow-600 mt-0.5"></i>
                    <span>File ini tidak dapat ditampilkan di browser. Silakan download untuk melihat.</span>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeFileInfo()" 
                        class="px-4 py-2 text-gray-700 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Tutup
                </button>
                <a id="downloadFromModal" href="#" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 flex items-center gap-2 transition shadow-sm">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Data gambar
let currentImageIndex = 0;
let images = [];

@if($document->fotos)
    images = [
        @foreach($document->fotos as $foto)
            @php
                $ext = strtolower($foto->tipe);
            @endphp
            @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
            {
                url: "{{ route('track-foto.view', $foto->id) }}",
                name: "{{ $foto->nama_file }}",
                id: {{ $foto->id }},
                downloadUrl: "{{ route('track-foto.download', $foto->id) }}"
            },
            @endif
        @endforeach
    ];
@endif

// Lightbox functions
function openLightbox(imageUrl, imageName) {
    currentImageIndex = images.findIndex(img => img.url === imageUrl);
    if (currentImageIndex === -1) currentImageIndex = 0;
    updateLightboxImage();
    document.getElementById('lightboxModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightboxModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function updateLightboxImage() {
    if (images.length > 0 && currentImageIndex >= 0 && currentImageIndex < images.length) {
        const img = document.getElementById('lightboxImage');
        const image = images[currentImageIndex];
        
        img.src = image.url + '?t=' + Date.now();
        document.getElementById('lightboxCaption').innerHTML = 
            `<span class="bg-black/50 px-4 py-2 rounded-lg backdrop-blur-sm border border-white/20">${image.name}</span>`;
        document.getElementById('lightboxCounter').innerHTML = 
            `${currentImageIndex + 1} / ${images.length}`;
        document.getElementById('lightboxDownload').href = image.downloadUrl;
    }
}

function nextImage() {
    if (images.length > 0) {
        currentImageIndex = (currentImageIndex + 1) % images.length;
        updateLightboxImage();
    }
}

function prevImage() {
    if (images.length > 0) {
        currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
        updateLightboxImage();
    }
}

// File info modal
function showFileInfo(fileName, fileType, fileSize) {
    document.getElementById('infoFileName').textContent = fileName;
    document.getElementById('infoFileType').textContent = fileType.toUpperCase();
    document.getElementById('infoFileSize').textContent = fileSize + ' KB';
    
    const card = event.currentTarget.closest('.group');
    const downloadLink = card.querySelector('a[title="Download"]');
    if (downloadLink) {
        document.getElementById('downloadFromModal').href = downloadLink.href;
    }
    
    document.getElementById('fileInfoModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeFileInfo() {
    document.getElementById('fileInfoModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (!document.getElementById('lightboxModal').classList.contains('hidden')) {
        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            nextImage();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            prevImage();
        }
    }
    
    if (!document.getElementById('fileInfoModal').classList.contains('hidden')) {
        if (e.key === 'Escape') {
            closeFileInfo();
        }
    }
});

// Touch support
let touchstartX = 0;
let touchendX = 0;

const lightbox = document.getElementById('lightboxModal');
if (lightbox) {
    lightbox.addEventListener('touchstart', e => {
        touchstartX = e.changedTouches[0].screenX;
    });

    lightbox.addEventListener('touchend', e => {
        touchendX = e.changedTouches[0].screenX;
        if (touchendX < touchstartX - 50) nextImage();
        if (touchendX > touchstartX + 50) prevImage();
    });
}

// Toggle forms
function toggleTolakForm() {
    const tolakForm = document.getElementById('tolakForm');
    const teruskanForm = document.getElementById('teruskanForm');
    
    tolakForm.classList.toggle('hidden');
    if (!teruskanForm.classList.contains('hidden')) {
        teruskanForm.classList.add('hidden');
    }
}

function toggleTeruskanForm() {
    const tolakForm = document.getElementById('tolakForm');
    const teruskanForm = document.getElementById('teruskanForm');
    
    teruskanForm.classList.toggle('hidden');
    if (!tolakForm.classList.contains('hidden')) {
        tolakForm.classList.add('hidden');
    }
}

// Close forms when clicking outside
document.addEventListener('click', function(event) {
    const tolakForm = document.getElementById('tolakForm');
    const teruskanForm = document.getElementById('teruskanForm');
    const tolakBtn = document.querySelector('[onclick="toggleTolakForm()"]');
    const teruskanBtn = document.querySelector('[onclick="toggleTeruskanForm()"]');
    
    if (tolakForm && !tolakForm.classList.contains('hidden') && 
        !tolakForm.contains(event.target) && 
        event.target !== tolakBtn && 
        !tolakBtn?.contains(event.target)) {
        tolakForm.classList.add('hidden');
    }
    
    if (teruskanForm && !teruskanForm.classList.contains('hidden') && 
        !teruskanForm.contains(event.target) && 
        event.target !== teruskanBtn && 
        !teruskanBtn?.contains(event.target)) {
        teruskanForm.classList.add('hidden');
    }
});

// Prevent image drag
document.querySelectorAll('img').forEach(img => {
    img.addEventListener('dragstart', (e) => e.preventDefault());
});
</script>

<style>
#lightboxModal {
    transition: opacity 0.3s ease;
}

#lightboxModal.hidden {
    display: none;
}

#lightboxImage {
    transition: transform 0.3s ease;
}

#lightboxModal:not(.hidden) #lightboxImage {
    animation: zoomIn 0.3s ease;
}

@keyframes zoomIn {
    from {
        transform: scale(0.95);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.error-image {
    opacity: 0.7;
    filter: grayscale(50%);
}

.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}

@media (max-width: 640px) {
    #lightboxModal .w-12.h-12 {
        width: 2.5rem;
        height: 2.5rem;
    }
}

/* Loading animation */
img:not([src]), 
img[src=""] {
    opacity: 0;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

@endsection