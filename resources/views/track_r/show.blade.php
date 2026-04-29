@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tanda Terima Dokumen</h1>
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

    @php
        $userStatus = $document->statusForUser(auth()->user());
    @endphp

    {{-- GRID UTAMA --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- KOLOM KIRI (2/3): TANDA TERIMA & LAMPIRAN --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- KARTU TANDA TERIMA --}}
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-blue-700 to-blue-800 px-6 py-5 text-white">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                        <div>
                            <h1 class="text-xl font-bold tracking-wide">TANDA TERIMA DOKUMEN</h1>
                            <p class="text-xs opacity-80">Sistem Track R · GA Portal</p>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-mono block">No: {{ $document->nomor_dokumen }}</span>
                            <span class="text-xs">{{ $document->created_at->format('d F Y') }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-600 uppercase text-xs tracking-wider mb-3">Pengirim</h3>
                        <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium">{{ $document->pengirim->name }}</p>
                                <p class="text-xs text-gray-500">{{ $document->pengirim->email }}</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-600 uppercase text-xs tracking-wider mb-3">Penerima Saat Ini</h3>
                        <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-check text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium">{{ $document->penerima->name }}</p>
                                <p class="text-xs text-gray-500">{{ $document->penerima->email }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <h3 class="font-semibold text-gray-600 uppercase text-xs tracking-wider mb-2">Judul & Keterangan</h3>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                            <p class="font-semibold text-gray-800">{{ $document->judul }}</p>
                            <p class="text-sm text-gray-600">{{ $document->keterangan ?? 'Tidak ada keterangan' }}</p>
                        </div>
                    </div>
                    <div class="md:col-span-2 flex justify-between items-center pt-2">
                        <span class="text-sm text-gray-500">Status Anda:</span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $userStatus['color'] }}">
                            {{ $userStatus['label'] }}
                        </span>
                    </div>
                </div>

                <div class="border-t px-6 py-4">
                    <h3 class="font-semibold text-gray-600 uppercase text-xs tracking-wider mb-3">Riwayat Penerima</h3>
                    <div class="flex flex-wrap gap-2">
                        @forelse($document->recipients as $recip)
                            @php $isCurrent = $recip->id === $document->penerima_id; @endphp
                            <div class="flex items-center gap-2 px-3 py-2 rounded-full text-sm 
                                {{ $isCurrent ? 'bg-blue-50 border-2 border-blue-500' : 'bg-gray-50 border' }}">
                                <i class="fas fa-user-circle {{ $isCurrent ? 'text-blue-600' : 'text-gray-400' }}"></i>
                                <span class="font-medium">{{ $recip->name }}</span>
                                @if($recip->pivot->received_at)
                                    <span class="text-xs text-gray-400">({{ \Carbon\Carbon::parse($recip->pivot->received_at)->format('d/m H:i') }})</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada penerima.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- LAMPIRAN --}}
            @if($document->fotos && $document->fotos->count() > 0)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4 flex items-center justify-between">
                    <h3 class="font-semibold text-white flex items-center gap-2"><i class="fas fa-images"></i>Lampiran Dokumen</h3>
                    <span class="text-xs bg-white/20 text-white px-3 py-1.5 rounded-full">{{ $document->fotos->count() }} file</span>
                </div>
                <div class="p-6">
                    <p class="text-xs text-gray-500 mb-4 flex items-center gap-2 bg-blue-50 p-3 rounded-lg border border-blue-100">
                        <i class="fas fa-info-circle text-blue-500"></i> Klik gambar untuk memperbesar.
                    </p>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($document->fotos as $foto)
                        @php
                            $ext = strtolower($foto->tipe);
                            $isImage = in_array($ext, ['jpg','jpeg','png','gif','bmp','webp']);
                        @endphp
                        <div class="group relative bg-white border rounded-xl overflow-hidden hover:shadow-lg transition-all duration-200">
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
                                                'doc','docx' => 'fa-file-word text-blue-500',
                                                'xls','xlsx' => 'fa-file-excel text-green-600',
                                                'ppt','pptx' => 'fa-file-powerpoint text-orange-500',
                                                'txt' => 'fa-file-alt text-gray-500',
                                                'zip','rar' => 'fa-file-archive text-yellow-600',
                                                default => 'fa-file text-gray-500'
                                            };
                                        @endphp
                                        <i class="fas {{ $icon }} text-5xl mb-3"></i>
                                        <span class="text-xs font-medium px-2 py-1 bg-white rounded-full shadow-sm">{{ strtoupper($ext) }}</span>
                                    </div>
                                @endif
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
                            <div class="p-3 border-t bg-white">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas {{ $isImage ? 'fa-image text-green-600' : 'fa-file text-gray-600' }} text-xs"></i>
                                    <p class="text-xs font-medium truncate flex-1 text-gray-700" title="{{ $foto->nama_file }}">{{ Str::limit($foto->nama_file, 20) }}</p>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ number_format($foto->ukuran / 1024, 1) }} KB</span>
                                    <div class="flex gap-1">
                                        @if($isImage)
                                        <a href="{{ route('track-foto.view', $foto->id) }}" target="_blank"
                                           class="w-7 h-7 bg-green-50 text-green-600 rounded flex items-center justify-center hover:bg-green-100 transition"
                                           title="Lihat" onclick="event.stopPropagation()"><i class="fas fa-eye text-xs"></i></a>
                                        @endif
                                        <a href="{{ route('track-foto.download', $foto->id) }}"
                                           class="w-7 h-7 bg-blue-50 text-blue-600 rounded flex items-center justify-center hover:bg-blue-100 transition"
                                           title="Download" onclick="event.stopPropagation()"><i class="fas fa-download text-xs"></i></a>
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
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-image text-gray-400 text-3xl"></i></div>
                <p class="text-gray-500 font-medium">Tidak ada lampiran file</p>
                <p class="text-sm text-gray-400 mt-1">Dokumen ini tidak memiliki file lampiran</p>
            </div>
            @endif
        </div>

        {{-- KOLOM KANAN (1/3): STATUS, RIWAYAT, AKSI --}}
        <div class="space-y-6">
            {{-- STATUS CARD --}}
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                    <h3 class="font-semibold text-white flex items-center gap-2"><i class="fas fa-info-circle"></i>Status Anda</h3>
                </div>
                <div class="p-6 text-center">
                    <span class="px-4 py-2 rounded-full border text-sm font-semibold {{ $userStatus['color'] }}">
                        {{ $userStatus['label'] }}
                    </span>
                    <div class="mt-4 text-sm text-gray-600">
                        Status global: <span class="font-semibold">{{ strtoupper($document->status) }}</span>
                    </div>
                </div>
            </div>

            {{-- RIWAYAT AKTIVITAS --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4 flex items-center justify-between">
                    <h3 class="font-semibold text-white"><i class="fas fa-history"></i> Riwayat</h3>
                    <span class="text-xs bg-white/20 text-white px-3 py-1.5 rounded-full">{{ $document->logs->count() }}</span>
                </div>
                <div class="p-6 max-h-96 overflow-y-auto space-y-4">
                    @forelse($document->logs as $log)
                    <div class="flex gap-4 p-3 border rounded-lg hover:bg-gray-50 transition">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            @php
                                $icon = match($log->aksi) {'kirim'=>'paper-plane','terima'=>'check','tolak'=>'times','teruskan'=>'share',default=>'circle'};
                                $color = match($log->aksi) {'kirim'=>'text-blue-600','terima'=>'text-green-600','tolak'=>'text-red-600','teruskan'=>'text-purple-600',default=>'text-gray-600'};
                            @endphp
                            <i class="fas fa-{{ $icon }} {{ $color }}"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm"><span class="font-semibold">{{ $log->dariUser->name ?? 'System' }}</span> {{ $log->aksi }}
                                        @if($log->keUser) ke <span class="font-semibold">{{ $log->keUser->name }}</span> @endif
                                    </p>
                                    @if($log->catatan)<p class="text-xs text-gray-500 mt-1 italic">"{{ $log->catatan }}"</p>@endif
                                </div>
                                <span class="text-xs text-gray-400 whitespace-nowrap ml-4 bg-gray-100 px-2 py-1 rounded">{{ $log->created_at->format('d M H:i') }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6 text-gray-500"><i class="fas fa-history text-3xl mb-2 text-gray-300"></i><p>Belum ada aktivitas</p></div>
                    @endforelse
                </div>
            </div>

            {{-- AKSI --}}
            @if(auth()->id() == $document->penerima_id || auth()->id() == $document->pengirim_id)
                @if(in_array($document->status, ['dikirim','diteruskan']))
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                        <h3 class="font-semibold text-white"><i class="fas fa-tasks"></i> Aksi</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        @if(auth()->id() == $document->penerima_id && $document->status == 'dikirim')
                        <form action="{{ route('track-r.terima', $document->id) }}" method="POST">
                            @csrf
                            <button class="w-full py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 flex items-center justify-center gap-2">
                                <i class="fas fa-check-circle"></i> Terima
                            </button>
                        </form>
                        @endif
                        @if(auth()->id() == $document->penerima_id)
                        <button onclick="toggleTolakForm()" class="w-full py-3 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 flex items-center justify-center gap-2">
                            <i class="fas fa-times-circle"></i> Tolak
                        </button>
                        @endif
                        <button onclick="toggleTeruskanForm()" class="w-full py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 flex items-center justify-center gap-2">
                            <i class="fas fa-share-alt"></i> Teruskan
                        </button>

                        {{-- Form Tolak --}}
                        <div id="tolakForm" class="hidden mt-4 p-4 bg-red-50 border rounded-lg">
                            <form action="{{ route('track-r.tolak', $document->id) }}" method="POST">
                                @csrf
                                <label class="block text-sm font-medium mb-1">Alasan *</label>
                                <textarea name="catatan" rows="3" required class="w-full border rounded px-3 py-2 text-sm"></textarea>
                                <div class="flex justify-end gap-2 mt-3">
                                    <button type="button" onclick="toggleTolakForm()" class="px-4 py-2 border rounded">Batal</button>
                                    <button class="px-4 py-2 bg-red-600 text-white rounded">Tolak</button>
                                </div>
                            </form>
                        </div>

                        {{-- Form Teruskan dengan Pencarian Penerima --}}
                        <div id="teruskanForm" class="hidden mt-4 p-4 bg-blue-50 border rounded-lg">
                            <form action="{{ route('track-r.teruskan', $document->id) }}" method="POST">
                                @csrf
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Teruskan ke *</label>
                                        <div class="relative">
                                            <input type="text" id="penerima_search"
                                                   class="w-full border rounded px-3 py-2 text-sm"
                                                   placeholder="Ketik minimal 3 huruf nama..."
                                                   autocomplete="off">
                                            <input type="hidden" name="penerima_id" id="penerima_id" required>
                                            <div id="searchResults" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"></div>
                                            <div id="selectedUserDisplay" class="mt-2 hidden">
                                                <div class="flex items-center justify-between p-2 bg-blue-50 border border-blue-200 rounded-lg">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-800" id="selectedUserName"></p>
                                                        <p class="text-xs text-gray-500" id="selectedUserEmail"></p>
                                                    </div>
                                                    <button type="button" onclick="clearSelectedUser()" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Catatan</label>
                                        <textarea name="catatan" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 mt-3">
                                    <button type="button" onclick="toggleTeruskanForm()" class="px-4 py-2 border rounded">Batal</button>
                                    <button class="px-4 py-2 bg-blue-600 text-white rounded">Teruskan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
            @endif

            {{-- Fallback akses ditolak --}}
            @if(!$document->hasAccess(auth()->user()))
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-exclamation-triangle text-red-500 text-xl"></i></div>
                    <div>
                        <h3 class="text-lg font-semibold text-red-800">Akses Ditolak</h3>
                        <p class="text-red-700">Anda tidak memiliki akses ke dokumen ini.</p>
                        <a href="{{ route('track-r.index') }}" class="mt-2 inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg">Kembali</a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- LIGHTBOX MODAL --}}
<div id="lightboxModal" class="fixed inset-0 z-50 hidden" onclick="closeLightbox()">
    <div class="absolute inset-0 bg-black bg-opacity-95"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative max-w-7xl max-h-full" onclick="event.stopPropagation()">
            <button onclick="closeLightbox()" class="absolute -top-14 right-0 text-white hover:text-gray-300 text-3xl z-50 w-12 h-12 flex items-center justify-center bg-black/50 rounded-full hover:bg-black/70 transition border border-white/20"><i class="fas fa-times"></i></button>
            <button onclick="prevImage()" class="absolute left-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 text-3xl z-50 w-12 h-12 flex items-center justify-center bg-black/50 rounded-full hover:bg-black/70 transition border border-white/20"><i class="fas fa-chevron-left"></i></button>
            <button onclick="nextImage()" class="absolute right-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 text-3xl z-50 w-12 h-12 flex items-center justify-center bg-black/50 rounded-full hover:bg-black/70 transition border border-white/20"><i class="fas fa-chevron-right"></i></button>
            <img id="lightboxImage" src="" alt="" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl border border-white/10">
            <div id="lightboxCaption" class="absolute -bottom-12 left-0 right-0 text-center text-white text-sm"></div>
            <div id="lightboxCounter" class="absolute top-4 left-4 text-white text-sm bg-black/50 px-3 py-1.5 rounded-full border border-white/20"></div>
            <a id="lightboxDownload" href="#" target="_blank" class="absolute top-4 right-4 text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-lg border border-blue-400"><i class="fas fa-download"></i> Download</a>
        </div>
    </div>
</div>

{{-- MODAL INFO FILE --}}
<div id="fileInfoModal" class="fixed inset-0 z-50 hidden" onclick="closeFileInfo()">
    <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800"><i class="fas fa-info-circle text-blue-500"></i> Informasi File</h3>
                <button onclick="closeFileInfo()" class="text-gray-500 hover:text-gray-700 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-4">
                <div class="bg-gray-50 p-4 rounded-lg"><label class="text-xs text-gray-500 block mb-1">Nama File</label><p id="infoFileName" class="text-sm font-medium text-gray-800 break-words"></p></div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 p-3 rounded-lg"><label class="text-xs text-gray-500 block mb-1">Tipe</label><p id="infoFileType" class="text-sm font-medium"></p></div>
                    <div class="bg-gray-50 p-3 rounded-lg"><label class="text-xs text-gray-500 block mb-1">Ukuran</label><p id="infoFileSize" class="text-sm font-medium"></p></div>
                </div>
                <div class="text-sm text-gray-600 bg-yellow-50 p-4 rounded-lg border border-yellow-200 flex items-start gap-3">
                    <i class="fas fa-info-circle text-yellow-600 mt-0.5"></i>
                    <span>File ini tidak dapat ditampilkan di browser. Silakan download untuk melihat.</span>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeFileInfo()" class="px-4 py-2 border rounded-lg">Tutup</button>
                <a id="downloadFromModal" href="#" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700"><i class="fas fa-download"></i> Download</a>
            </div>
        </div>
    </div>
</div>

<script>
// Data lightbox
let currentImageIndex = 0;
let images = [];
@if($document->fotos)
    images = [
        @foreach($document->fotos as $foto)
            @if(in_array(strtolower($foto->tipe), ['jpg','jpeg','png','gif','bmp','webp']))
            { url:"{{ route('track-foto.view', $foto->id) }}", name:"{{ $foto->nama_file }}", downloadUrl:"{{ route('track-foto.download', $foto->id) }}" },
            @endif
        @endforeach
    ];
@endif

function openLightbox(url, name) {
    currentImageIndex = images.findIndex(img => img.url === url);
    if(currentImageIndex===-1) currentImageIndex=0;
    updateLightboxImage();
    document.getElementById('lightboxModal').classList.remove('hidden');
    document.body.style.overflow='hidden';
}
function closeLightbox() {
    document.getElementById('lightboxModal').classList.add('hidden');
    document.body.style.overflow='auto';
}
function updateLightboxImage() {
    if(images.length>0 && currentImageIndex>=0 && currentImageIndex<images.length) {
        let img = document.getElementById('lightboxImage');
        let image = images[currentImageIndex];
        img.src = image.url + '?t=' + Date.now();
        document.getElementById('lightboxCaption').innerHTML = `<span class="bg-black/50 px-4 py-2 rounded-lg backdrop-blur-sm border border-white/20">${image.name}</span>`;
        document.getElementById('lightboxCounter').innerHTML = `${currentImageIndex+1} / ${images.length}`;
        document.getElementById('lightboxDownload').href = image.downloadUrl;
    }
}
function nextImage() { if(images.length>0) { currentImageIndex = (currentImageIndex+1)%images.length; updateLightboxImage(); } }
function prevImage() { if(images.length>0) { currentImageIndex = (currentImageIndex-1+images.length)%images.length; updateLightboxImage(); } }

// File info
function showFileInfo(name, type, size) {
    document.getElementById('infoFileName').textContent = name;
    document.getElementById('infoFileType').textContent = type.toUpperCase();
    document.getElementById('infoFileSize').textContent = size + ' KB';
    let card = event.currentTarget.closest('.group');
    let dl = card?.querySelector('a[title="Download"]');
    if(dl) document.getElementById('downloadFromModal').href = dl.href;
    document.getElementById('fileInfoModal').classList.remove('hidden');
    document.body.style.overflow='hidden';
}
function closeFileInfo() {
    document.getElementById('fileInfoModal').classList.add('hidden');
    document.body.style.overflow='auto';
}

// Keyboard & touch
document.addEventListener('keydown', e => {
    if(!document.getElementById('lightboxModal').classList.contains('hidden')) {
        if(e.key==='Escape') closeLightbox();
        else if(e.key==='ArrowRight') { e.preventDefault(); nextImage(); }
        else if(e.key==='ArrowLeft') { e.preventDefault(); prevImage(); }
    }
    if(!document.getElementById('fileInfoModal').classList.contains('hidden') && e.key==='Escape') closeFileInfo();
});
let touchstartX=0, touchendX=0;
document.getElementById('lightboxModal')?.addEventListener('touchstart', e => touchstartX=e.changedTouches[0].screenX);
document.getElementById('lightboxModal')?.addEventListener('touchend', e => {
    touchendX=e.changedTouches[0].screenX;
    if(touchendX<touchstartX-50) nextImage();
    if(touchendX>touchstartX+50) prevImage();
});

// Toggle forms
function toggleTolakForm(){ document.getElementById('tolakForm').classList.toggle('hidden'); document.getElementById('teruskanForm').classList.add('hidden'); }
function toggleTeruskanForm(){ document.getElementById('teruskanForm').classList.toggle('hidden'); document.getElementById('tolakForm').classList.add('hidden'); }

// Pencarian penerima
const allUsers = [
    @foreach($users as $user)
        { id: {{ $user->id }}, name: "{{ $user->name }}", email: "{{ $user->email }}", searchText: "{{ strtolower($user->name) }} {{ strtolower($user->email) }}" },
    @endforeach
];

let searchTimeout;
document.getElementById('penerima_search').addEventListener('input', function() {
    const searchTerm = this.value.trim().toLowerCase();
    const resultsDiv = document.getElementById('searchResults');
    clearTimeout(searchTimeout);

    if (searchTerm.length < 3) {
        resultsDiv.classList.add('hidden');
        return;
    }

    searchTimeout = setTimeout(() => {
        const filtered = allUsers.filter(u => u.searchText.includes(searchTerm));
        let html = '';
        filtered.slice(0, 10).forEach(u => {
            html += `<div class="p-3 hover:bg-gray-50 cursor-pointer border-b" onclick="selectUser(${u.id}, '${u.name.replace(/'/g, "\\'")}', '${u.email}')">
                        <div class="font-medium text-gray-800">${u.name}</div>
                        <div class="text-xs text-gray-500">${u.email}</div>
                     </div>`;
        });
        if (filtered.length > 10) {
            html += `<div class="p-2 text-xs text-gray-500 text-center">${filtered.length - 10} lebih... ketik lebih spesifik</div>`;
        }
        resultsDiv.innerHTML = html || '<div class="p-3 text-sm text-gray-500 text-center">Tidak ditemukan</div>';
        resultsDiv.classList.remove('hidden');
    }, 300);
});

function selectUser(id, name, email) {
    document.getElementById('penerima_id').value = id;
    document.getElementById('selectedUserName').textContent = name;
    document.getElementById('selectedUserEmail').textContent = email;
    document.getElementById('selectedUserDisplay').classList.remove('hidden');
    document.getElementById('penerima_search').value = '';
    document.getElementById('searchResults').classList.add('hidden');
}

function clearSelectedUser() {
    document.getElementById('penerima_id').value = '';
    document.getElementById('selectedUserDisplay').classList.add('hidden');
    document.getElementById('penerima_search').value = '';
    document.getElementById('searchResults').classList.add('hidden');
}

// Prevent image drag
document.querySelectorAll('img').forEach(img => img.addEventListener('dragstart', e => e.preventDefault()));
</script>
<style>
#lightboxModal{transition:opacity 0.3s}#lightboxModal.hidden{display:none}#lightboxImage{transition:transform 0.3s}#lightboxModal:not(.hidden) #lightboxImage{animation:zoomIn 0.3s}@keyframes zoomIn{from{transform:scale(0.95);opacity:0}to{transform:scale(1);opacity:1}}.group:hover .group-hover\:scale-110{transform:scale(1.1)}
</style>
@endsection