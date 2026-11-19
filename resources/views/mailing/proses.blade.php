{{-- [file name]: proses.blade.php --}}
@extends('layouts.app-sidebar')

@section('content')
@php
    // Filter dianggap aktif hanya jika nilainya terisi (bukan sekadar ada di URL)
    $activeCount = collect(['start_date', 'end_date', 'search', 'status', 'lantai'])->filter(fn ($k) => request()->filled($k))->count();
    $hasFilter   = $activeCount > 0;
@endphp
<div class="p-4 md:p-6 space-y-4 md:space-y-6">
    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-lg md:text-2xl font-bold text-gray-900">Proses Mailing</h1>
            <p class="text-xs md:text-sm text-gray-600">Tracking: Mailing Room → Lantai 47 → Selesai</p>
            
            {{-- TAMPILKAN INFO AKSES --}}
            @if(isset($canViewAll))
                @if($canViewAll)
                    <div class="mt-1">
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-md">
                            <i class="fas fa-user-shield mr-1"></i> Admin: Dapat melihat semua mailing
                        </span>
                    </div>
                @else
                    <div class="mt-1">
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-md">
                            <i class="fas fa-user mr-1"></i> User: Hanya melihat mailing ditujukan kepada Anda
                        </span>
                        @if($pelangganId)
                            <span class="ml-2 text-xs text-gray-500">(ID Pelanggan: {{ $pelangganId }})</span>
                        @endif
                    </div>
                @endif
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('mailing.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                + Input Mailing
            </a>
            <button id="toggleFilterBtn" type="button"
                aria-controls="filterSection" aria-expanded="{{ $hasFilter ? 'true' : 'false' }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition
                       {{ $hasFilter ? 'bg-blue-100 border-blue-300 text-blue-700' : 'bg-gray-100 hover:bg-gray-200 border-transparent' }}">
            <i class="fas fa-sliders-h"></i>
            Filter
            @if($activeCount > 0)
            <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full bg-blue-600 text-white text-[10px] leading-none">{{ $activeCount }}</span>
            @endif
            <i id="filterChevron" class="fas fa-chevron-down text-xs transition-transform {{ $hasFilter ? 'rotate-180' : '' }}"></i>
        </button>
        </div>
    </div>

    {{-- ================= FILTER ================= --}}
    <div id="filterSection" class="bg-white border rounded-xl p-4 {{ $hasFilter ? '' : 'hidden' }}">
        <form method="GET" action="{{ route('mailing.proses') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="f_start_date" class="block text-xs font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input id="f_start_date" type="date" name="start_date" value="{{ request('start_date') ?? $today }}"
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="f_end_date" class="block text-xs font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                    <input id="f_end_date" type="date" name="end_date" value="{{ request('end_date') ?? $today }}"
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="f_lantai" class="block text-xs font-medium text-gray-700 mb-1">Lantai Tujuan</label>
                    <select id="f_lantai" name="lantai" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">Semua Lantai</option>
                        @foreach([41, 42, 43, 45, 46, 47] as $lt)
                        <option value="{{ $lt }}" {{ (string) request('lantai') === (string) $lt ? 'selected' : '' }}>Lantai {{ $lt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="f_status" class="block text-xs font-medium text-gray-700 mb-1">Status Proses</label>
                    <select id="f_status" name="status" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">Semua Status</option>
                        @foreach(['Mailing Room', 'Lantai 47'] as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <label for="f_search" class="block text-xs font-medium text-gray-700 mb-1">Pencarian</label>
                    <input id="f_search" type="text" name="search" value="{{ request('search') }}"
                           placeholder="Resi, pengirim, penerima, ekspedisi, lantai..."
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-3 border-t">
                <a href="{{ route('mailing.proses') }}"
                   class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200">
                    Reset
                </a>
                <button type="submit"
                        class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700">
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    @include('mailing.partials.filter-chips', [
        'route'   => 'mailing.proses',
        'filters' => ['start_date' => 'Mulai', 'end_date' => 'Akhir', 'lantai' => 'Lantai', 'status' => 'Status', 'search' => 'Cari'],
    ])

    {{-- BULK ACTIONS --}}
    <div id="bulkActions" class="hidden bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <p class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-500"></i>
                    <span id="selectedCount">0</span> item dipilih
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button id="selectAllBtn" class="px-4 py-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg text-sm font-medium hover:bg-blue-100">
                    Pilih Semua
                </button>
                <button id="deselectAllBtn" class="px-4 py-2 bg-gray-50 text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-100">
                    Batalkan Semua
                </button>
                <button id="bulkMoveBtn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 hidden">
                    <i class="fas fa-arrow-right mr-2"></i>Pindahkan ke Lantai 47
                </button>
                <button id="bulkCompleteBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 hidden">
                    <i class="fas fa-check-circle mr-2"></i>Selesaikan Distribusi
                </button>
            </div>
        </div>
    </div>

    {{-- MAILING LIST --}}
    <div class="space-y-3">
        @foreach($mailings as $m)
        <div data-mailing-id="{{ $m->id_mailing }}" 
             data-status="{{ $m->mailing_status }}"
             class="bg-white rounded-xl border border-gray-200 p-4 relative group mailing-item cursor-pointer hover:border-blue-300 transition-colors">
            
            <div class="absolute left-4 top-4">
                <input type="checkbox" value="{{ $m->id_mailing }}" data-status="{{ $m->mailing_status }}"
                       class="mailing-checkbox hidden peer group-hover:block checked:block h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            </div>

            <div class="pl-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fas fa-box text-blue-500"></i>
                            <h3 class="font-bold text-gray-900">{{ $m->mailing_resi }}</h3>
                            @if($m->mailing_expedisi)
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded">
                                {{ $m->mailing_expedisi }}
                            </span>
                            @endif
                            @if($m->mailing_lantai)
                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-xs rounded font-medium">
                                Lantai {{ $m->mailing_lantai }}
                            </span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-600 mb-1">
                            {{ $m->mailing_pengirim }} → 
                            <span class="font-medium">{{ $m->mailing_penerima }}</span>
                        </div>
                        
                        {{-- TAMPILKAN KETERANGAN JIKA ADA --}}
                        @if($m->mailing_keterangan)
                        <div class="text-xs text-gray-500 mb-1">
                            <i class="fas fa-info-circle text-gray-400 mr-1"></i>
                            {{ Str::limit($m->mailing_keterangan, 100) }}
                            
                            {{-- TAMPILKAN JIKA INI MILIK USER YANG LOGIN --}}
                            @if($pelangganId && str_contains($m->mailing_keterangan, "Pelanggan ID: {$pelangganId}"))
                            <span class="ml-2 px-1 py-0.5 bg-green-100 text-green-700 text-xs rounded">
                                <i class="fas fa-check mr-0.5"></i> Milik Anda
                            </span>
                            @endif
                        </div>
                        @endif
                        
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <div>
                                <i class="fas fa-calendar mr-1"></i>
                                {{ $m->mailing_tanggal_input->format('d M Y, H:i') }}
                            </div>
                            @if($m->mailing_tanggal_ob47)
                            <div>
                                <i class="fas fa-arrow-right text-indigo-500 mr-1"></i>
                                Lantai 47: {{ $m->mailing_tanggal_ob47->format('d M Y, H:i') }}
                            </div>
                            @endif
                        </div>
                    </div>
                    <div>
                        @php
                            $statusColors = [
                                'Mailing Room' => 'bg-blue-100 text-blue-700 border-blue-200',
                                'Lantai 47' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                'Selesai' => 'bg-green-100 text-green-700 border-green-200'
                            ];
                            $colorClass = $statusColors[$m->mailing_status] ?? 'bg-gray-100 text-gray-700 border-gray-300';
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $colorClass }}">
                            {{ $m->mailing_status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        @if($mailings->count() == 0)
        <div class="text-center py-10 border-2 border-dashed border-gray-300 rounded-xl">
            <i class="fas fa-inbox text-4xl text-gray-400 mb-3"></i>
            <p class="font-semibold text-gray-600">Tidak ada data mailing</p>
            <p class="text-sm text-gray-500 mt-1">
                @if($hasFilter)
                Coba ubah filter pencarian
                @else
                Semua mailing sudah diproses
                @endif
            </p>
        </div>
        @endif
    </div>

    {{-- PAGINATION --}}
    @if($mailings->hasPages())
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="text-sm text-gray-600">
                Menampilkan {{ $mailings->firstItem() }} - {{ $mailings->lastItem() }} dari {{ $mailings->total() }} data
            </div>
            <div>
                {{ $mailings->links() }}
            </div>
        </div>
    </div>
    @endif
</div>

{{-- [file name]: proses.blade.php - Bagian modal bulk complete --}}
<div id="bulkCompleteModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Selesaikan Distribusi</h3>
                    <p class="text-sm text-gray-600" id="completeCountText">0 item terpilih</p>
                </div>
            </div>
            
            {{-- FORM DENGAN SISTEM YANG BENAR --}}
            <form id="bulkCompleteForm" method="POST" action="{{ route('mailing.bulk-selesai') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                
                {{-- Hidden inputs untuk mailing_ids --}}
                <div id="hiddenMailingIdsContainer"></div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user-check text-gray-500 mr-2"></i>
                        Diterima oleh
                        <span class="text-red-500">*</span>
                    </label>
                    
                    <div class="space-y-3">
                        {{-- SEARCH INPUT --}}
                        <div class="relative">
                            <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                <i class="fas fa-search text-gray-400 ml-3"></i>
                                <input type="text" 
                                       id="penerimaSearch" 
                                       placeholder="Ketik nama penerima..."
                                       class="flex-1 px-3 py-2 text-sm outline-none">
                                <button type="button" 
                                        id="toggleManualBtn"
                                        class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-sm font-medium border-l">
                                    Manual
                                </button>
                            </div>
                            
                            {{-- SEARCH RESULTS --}}
                            <div id="penerimaResults" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                <div class="p-2 border-b bg-gray-50">
                                    <p class="text-xs text-gray-600">Klik untuk memilih penerima</p>
                                </div>
                                <div id="penerimaList" class="max-h-48 overflow-y-auto">
                                    {{-- Results akan dimuat via JavaScript --}}
                                </div>
                            </div>
                        </div>
                        
                        {{-- MANUAL INPUT (HIDDEN DEFAULT) --}}
                        <div id="manualInputContainer" class="hidden">
                            <input type="text" 
                                   id="manualPenerima" 
                                   placeholder="Masukkan nama penerima manual..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <p class="text-xs text-gray-500 mt-1">
                                Nama akan otomatis tersimpan
                            </p>
                        </div>
                        
                        {{-- HIDDEN INPUTS UNTUK FORM SUBMIT --}}
                        <input type="hidden" id="selectedPenerimaId" name="penerima_id" value="">
                        <input type="hidden" id="selectedPenerimaNama" name="mailing_penerima_distribusi" value="">
                        
                        {{-- SELECTED DISPLAY --}}
                        <div id="selectedPenerimaDisplay" class="hidden p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-green-600 font-medium">Penerima terpilih:</p>
                                    <p class="text-sm font-semibold text-gray-800" id="displayPenerimaNama"></p>
                                    <p class="text-xs text-gray-500" id="displayPenerimaSource"></p>
                                </div>
                                <button type="button" 
                                        onclick="clearPenerima()" 
                                        class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-camera text-gray-500 mr-2"></i>
                        Foto Bukti Distribusi
                        <span class="text-red-500">*</span>
                    </label>
                    
                    {{-- PILIHAN KAMERA --}}
                    <div class="space-y-3">
                        {{-- Input file hidden --}}
                        <input type="file" 
                               name="mailing_foto" 
                               accept="image/*" 
                               id="mailingFotoInput"
                               required 
                               class="hidden">
                        
                        {{-- Tombol pilihan --}}
                        <div class="grid grid-cols-2 gap-3">
                            {{-- KAMERA HP --}}
                            <button type="button" 
                                    onclick="openCameraPhone()"
                                    class="flex flex-col items-center justify-center p-4 bg-blue-50 border-2 border-blue-200 rounded-xl hover:bg-blue-100 transition-colors">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                                    <i class="fas fa-camera text-blue-600 text-lg"></i>
                                </div>
                                <span class="font-semibold text-blue-700 text-sm">Kamera HP</span>
                            </button>
                            
                            {{-- GALERI --}}
                            <button type="button" 
                                    onclick="openGallery()"
                                    class="flex flex-col items-center justify-center p-4 bg-purple-50 border-2 border-purple-200 rounded-xl hover:bg-purple-100 transition-colors">
                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mb-2">
                                    <i class="fas fa-images text-purple-600 text-lg"></i>
                                </div>
                                <span class="font-semibold text-purple-700 text-sm">Galeri</span>
                            </button>
                        </div>
                        
                        {{-- FOTO PREVIEW --}}
                        <div id="fotoPreview" class="hidden">
                            <div class="border-2 border-green-200 rounded-xl p-4 bg-green-50">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <p class="text-sm font-semibold text-green-800">Foto siap diupload</p>
                                    </div>
                                    <button type="button" 
                                            onclick="removeFoto()" 
                                            class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                
                                <div class="flex items-center gap-3">
                                    <div class="w-16 h-16 bg-white rounded-lg overflow-hidden border border-green-200">
                                        <img id="fotoPreviewImage" 
                                             src="" 
                                             alt="Preview" 
                                             class="w-full h-full object-cover">
                                    </div>
                                    
                                    <div class="flex-1">
                                        <p id="fotoFileName" class="text-sm font-medium text-gray-900 truncate"></p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span id="fotoFileSize" class="text-xs text-gray-500"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 pt-6">
                    <button type="button" id="cancelBulkComplete" class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" id="submitBulkComplete" class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                        Simpan Distribusi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- BULK MOVE MODAL - SUPER SIMPLE --}}
<div id="bulkMoveModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                    <i class="fas fa-arrow-right text-indigo-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Pindahkan ke Lantai 47</h3>
                    <p class="text-sm text-gray-600" id="moveCountText">0 item terpilih</p>
                </div>
            </div>
            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800">Anda akan memindahkan <span id="moveCount">0</span> mailing ke status "Lantai 47"</p>
                </div>
                
                {{-- SIMPLE FORM - NO AJAX --}}
                <form id="bulkMoveForm" method="POST" action="{{ route('mailing.bulk-lantai47') }}">
                    @csrf
                    
                    {{-- Hidden inputs untuk mailing_ids --}}
                    <div id="hiddenMoveIdsContainer"></div>
                    
                    <div class="flex gap-3">
                        <button type="button" id="cancelBulkMove" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" id="confirmBulkMove" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                            Ya, Pindahkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ================= FILTER TOGGLE =================
    // Status buka/tutup awal sudah ditentukan server (terbuka jika ada filter aktif)
    const toggleFilterBtn = document.getElementById('toggleFilterBtn');
    const filterSection   = document.getElementById('filterSection');
    const filterChevron   = document.getElementById('filterChevron');

    if (toggleFilterBtn && filterSection) {
        toggleFilterBtn.addEventListener('click', function() {
            const isHidden = filterSection.classList.toggle('hidden');
            toggleFilterBtn.setAttribute('aria-expanded', String(!isHidden));
            if (filterChevron) filterChevron.classList.toggle('rotate-180', !isHidden);
        });
    }

    // ================= DATE VALIDATION =================
    document.querySelectorAll('input[type="date"]').forEach(input => {
        input.addEventListener('change', function() {
            const startDate = document.querySelector('input[name="start_date"]');
            const endDate = document.querySelector('input[name="end_date"]');
            
            if (startDate.value && endDate.value && startDate.value > endDate.value) {
                alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir');
                this.value = '';
            }
        });
    });
    
    // ================= BULK SELECTION LOGIC =================
    let selectedMailings = new Set();
    let isManualMode = false;
    let currentSelectedPenerima = {
        id: '',
        nama: '',
        source: '' // 'search' atau 'manual'
    };
    
    // Data pelanggan dari PHP (simpan sebagai array)
    const pelanggans = @json($pelanggans->map(function($p) {
        return ['id' => $p->id_pelanggan, 'nama' => $p->nama_pelanggan];
    }));
    
    function updateUI() {
        const count = selectedMailings.size;
        document.getElementById('selectedCount').textContent = count;
        
        if (count > 0) {
            document.getElementById('bulkActions').classList.remove('hidden');
            
            const statuses = new Set();
            document.querySelectorAll('.mailing-checkbox:checked').forEach(cb => {
                statuses.add(cb.dataset.status);
            });
            
            const allMailingRoom = Array.from(statuses).every(s => s === 'Mailing Room');
            const allLantai47 = Array.from(statuses).every(s => s === 'Lantai 47');
            
            document.getElementById('bulkMoveBtn').classList.toggle('hidden', !allMailingRoom);
            document.getElementById('bulkCompleteBtn').classList.toggle('hidden', !allLantai47);
            
            document.getElementById('completeCountText').textContent = count + ' item terpilih';
            document.getElementById('moveCountText').textContent = count + ' item terpilih';
            document.getElementById('moveCount').textContent = count;
            
        } else {
            document.getElementById('bulkActions').classList.add('hidden');
        }
    }
    
    function toggleSelection(mailingId) {
        if (selectedMailings.has(mailingId)) {
            selectedMailings.delete(mailingId);
        } else {
            selectedMailings.add(mailingId);
        }
        
        const checkbox = document.querySelector(`.mailing-checkbox[value="${mailingId}"]`);
        if (checkbox) {
            checkbox.checked = selectedMailings.has(mailingId);
        }
        
        updateUI();
    }
    
    // ================= EVENT LISTENERS =================
    
    document.querySelectorAll('.mailing-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.type === 'checkbox' || e.target.closest('.mailing-checkbox')) {
                return;
            }
            
            const mailingId = this.dataset.mailingId;
            toggleSelection(mailingId);
        });
    });
    
    document.querySelectorAll('.mailing-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function(e) {
            e.stopPropagation();
            const mailingId = this.value;
            
            if (this.checked) {
                selectedMailings.add(mailingId);
            } else {
                selectedMailings.delete(mailingId);
            }
            
            updateUI();
        });
    });
    
    document.getElementById('selectAllBtn')?.addEventListener('click', function() {
        selectedMailings.clear();
        document.querySelectorAll('.mailing-checkbox').forEach(cb => {
            cb.checked = true;
            selectedMailings.add(cb.value);
        });
        updateUI();
    });
    
    document.getElementById('deselectAllBtn')?.addEventListener('click', function() {
        selectedMailings.clear();
        document.querySelectorAll('.mailing-checkbox').forEach(cb => {
            cb.checked = false;
        });
        updateUI();
    });
    
    // ================= PENERIMA FUNCTIONS =================
    
    // Bulk Complete button click
    document.getElementById('bulkCompleteBtn')?.addEventListener('click', function() {
        if (selectedMailings.size === 0) return;
        
        // Clear previous hidden inputs
        const container = document.getElementById('hiddenMailingIdsContainer');
        container.innerHTML = '';
        
        // Add hidden inputs for each selected mailing
        selectedMailings.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'mailing_ids[]';
            input.value = id;
            container.appendChild(input);
        });
        
        // Reset form state
        resetModal();
        
        // Show modal
        document.getElementById('bulkCompleteModal').classList.remove('hidden');
    });
    
    // Reset modal state
    function resetModal() {
        // Reset form inputs
        document.getElementById('bulkCompleteForm').reset();
        
        // Reset penerima
        clearPenerima();
        resetManualMode();
        
        // Reset foto
        removeFoto();
        
        // Reset state
        currentSelectedPenerima = { id: '', nama: '', source: '' };
    }
    
    // Search penerima
    document.getElementById('penerimaSearch')?.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const resultsDiv = document.getElementById('penerimaList');
        const resultsContainer = document.getElementById('penerimaResults');
        
        if (searchTerm.length < 2) {
            resultsContainer.classList.add('hidden');
            return;
        }
        
        // Filter pelanggan
        const filtered = pelanggans.filter(pelanggan => 
            pelanggan.nama.toLowerCase().includes(searchTerm)
        );
        
        // Display results
        if (filtered.length > 0) {
            resultsDiv.innerHTML = filtered.map(pelanggan => `
                <div class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 penerima-option"
                     data-id="${pelanggan.id}"
                     data-nama="${pelanggan.nama}">
                    <div class="font-medium text-gray-800">${pelanggan.nama}</div>
                    <div class="text-xs text-gray-500">ID: ${pelanggan.id}</div>
                </div>
            `).join('');
            
            resultsContainer.classList.remove('hidden');
            
            // Add click events
            document.querySelectorAll('.penerima-option').forEach(option => {
                option.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nama = this.getAttribute('data-nama');
                    
                    selectPenerimaFromSearch(id, nama);
                });
            });
        } else {
            resultsDiv.innerHTML = `
                <div class="p-4 text-center text-gray-500">
                    <i class="fas fa-search text-gray-300 text-lg mb-2"></i>
                    <p class="text-sm">Tidak ditemukan</p>
                    <p class="text-xs">Klik "Manual" untuk input nama</p>
                </div>
            `;
            resultsContainer.classList.remove('hidden');
        }
    });
    
    // Select penerima from search
    function selectPenerimaFromSearch(id, nama) {
        currentSelectedPenerima = {
            id: id,
            nama: nama,
            source: 'search'
        };
        
        updatePenerimaDisplay();
        document.getElementById('penerimaResults').classList.add('hidden');
        document.getElementById('penerimaSearch').value = nama;
    }
    
    // Update penerima display
    function updatePenerimaDisplay() {
        const displayDiv = document.getElementById('selectedPenerimaDisplay');
        const displayNama = document.getElementById('displayPenerimaNama');
        const displaySource = document.getElementById('displayPenerimaSource');
        
        if (currentSelectedPenerima.nama) {
            displayNama.textContent = currentSelectedPenerima.nama;
            
            if (currentSelectedPenerima.source === 'search') {
                displaySource.textContent = 'Dipilih dari daftar';
            } else {
                displaySource.textContent = 'Input manual';
            }
            
            displayDiv.classList.remove('hidden');
            
            // Update hidden inputs for form submit
            document.getElementById('selectedPenerimaId').value = currentSelectedPenerima.id;
            document.getElementById('selectedPenerimaNama').value = currentSelectedPenerima.nama;
        } else {
            displayDiv.classList.add('hidden');
            document.getElementById('selectedPenerimaId').value = '';
            document.getElementById('selectedPenerimaNama').value = '';
        }
    }
    
    // Clear penerima
    window.clearPenerima = function() {
        currentSelectedPenerima = { id: '', nama: '', source: '' };
        document.getElementById('penerimaSearch').value = '';
        document.getElementById('manualPenerima').value = '';
        updatePenerimaDisplay();
        document.getElementById('penerimaResults').classList.add('hidden');
    };
    
    // Reset manual mode
    function resetManualMode() {
        isManualMode = false;
        document.getElementById('manualInputContainer').classList.add('hidden');
        const toggleBtn = document.getElementById('toggleManualBtn');
        if (toggleBtn) {
            toggleBtn.innerHTML = 'Manual';
            toggleBtn.classList.remove('bg-yellow-100', 'text-yellow-700');
        }
        document.getElementById('penerimaSearch').disabled = false;
        document.getElementById('penerimaSearch').placeholder = 'Ketik nama penerima...';
    }
    
    // Toggle manual input
    document.getElementById('toggleManualBtn')?.addEventListener('click', function() {
        isManualMode = !isManualMode;
        const manualContainer = document.getElementById('manualInputContainer');
        const searchInput = document.getElementById('penerimaSearch');
        
        if (isManualMode) {
            // Switch to manual mode
            manualContainer.classList.remove('hidden');
            this.innerHTML = '← Kembali';
            this.classList.add('bg-yellow-100', 'text-yellow-700');
            searchInput.placeholder = 'Input manual aktif';
            searchInput.disabled = true;
            searchInput.value = '';
            
            // Clear previous selection
            clearPenerima();
            
            // Focus on manual input
            setTimeout(() => {
                document.getElementById('manualPenerima').focus();
            }, 100);
        } else {
            // Switch back to search mode
            manualContainer.classList.add('hidden');
            this.innerHTML = 'Manual';
            this.classList.remove('bg-yellow-100', 'text-yellow-700');
            searchInput.placeholder = 'Ketik nama penerima...';
            searchInput.disabled = false;
            searchInput.focus();
        }
    });
    
    // Manual input change
    document.getElementById('manualPenerima')?.addEventListener('input', function() {
        const nama = this.value.trim();
        if (nama) {
            currentSelectedPenerima = {
                id: '',
                nama: nama,
                source: 'manual'
            };
            updatePenerimaDisplay();
        }
    });
    
    // ================= KAMERA & GALERI FUNCTIONS =================
    
    window.openCameraPhone = function() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        
        // Prioritize camera on mobile
        if ('mediaDevices' in navigator) {
            input.capture = 'environment';
        }
        
        input.onchange = function(e) {
            if (e.target.files && e.target.files[0]) {
                handleSelectedFile(e.target.files[0]);
            }
        };
        
        input.click();
    };
    
    window.openGallery = function() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = false;
        
        input.onchange = function(e) {
            if (e.target.files && e.target.files[0]) {
                handleSelectedFile(e.target.files[0]);
            }
        };
        
        input.click();
    };
    
    function handleSelectedFile(file) {
        // Update hidden input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById('mailingFotoInput').files = dataTransfer.files;
        
        // Show preview
        showFilePreview(file);
        
        // Trigger validation
        document.getElementById('mailingFotoInput').dispatchEvent(new Event('change'));
    }
    
    function showFilePreview(file) {
        const previewDiv = document.getElementById('fotoPreview');
        const previewImg = document.getElementById('fotoPreviewImage');
        const fileName = document.getElementById('fotoFileName');
        const fileSize = document.getElementById('fotoFileSize');
        
        // Set file info
        fileName.textContent = file.name;
        
        // Format size
        const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
        fileSize.textContent = sizeInMB + ' MB';
        
        // Preview image
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewDiv.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            previewDiv.classList.remove('hidden');
        }
    }
    
    window.removeFoto = function() {
        document.getElementById('mailingFotoInput').value = '';
        document.getElementById('fotoPreview').classList.add('hidden');
        document.getElementById('fotoPreviewImage').src = '';
        document.getElementById('fotoFileName').textContent = '';
        document.getElementById('fotoFileSize').textContent = '';
    };
    
    // ================= FORM VALIDATION =================
    document.getElementById('bulkCompleteForm')?.addEventListener('submit', function(e) {
        const fileInput = document.getElementById('mailingFotoInput');
        const penerimaNama = currentSelectedPenerima.nama;
        
        // Validate foto
        if (!fileInput.files || fileInput.files.length === 0) {
            e.preventDefault();
            alert('⚠️ Silakan upload foto bukti distribusi');
            return;
        }
        
        // Validate penerima
        if (!penerimaNama) {
            e.preventDefault();
            alert('⚠️ Silakan pilih atau masukkan nama penerima');
            return;
        }
        
        // Ensure hidden inputs are set
        document.getElementById('selectedPenerimaNama').value = penerimaNama;
        document.getElementById('selectedPenerimaId').value = currentSelectedPenerima.id;
        
        // Show loading
        const submitBtn = document.getElementById('submitBulkComplete');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
        submitBtn.disabled = true;
    });
    
    // ================= BULK MOVE =================
    document.getElementById('bulkMoveBtn')?.addEventListener('click', function() {
        if (selectedMailings.size === 0) return;
        
        const container = document.getElementById('hiddenMoveIdsContainer');
        container.innerHTML = '';
        
        selectedMailings.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'mailing_ids[]';
            input.value = id;
            container.appendChild(input);
        });
        
        document.getElementById('bulkMoveModal').classList.remove('hidden');
    });
    
    document.getElementById('cancelBulkMove')?.addEventListener('click', function() {
        document.getElementById('bulkMoveModal').classList.add('hidden');
    });
    
    document.getElementById('bulkMoveForm')?.addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('confirmBulkMove');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
        submitBtn.disabled = true;
    });
    
    // ================= MODAL CLOSE =================
    document.getElementById('cancelBulkComplete')?.addEventListener('click', function() {
        document.getElementById('bulkCompleteModal').classList.add('hidden');
        resetModal();
    });
    
    document.addEventListener('click', function(e) {
        if (e.target.id === 'bulkCompleteModal') {
            document.getElementById('bulkCompleteModal').classList.add('hidden');
            resetModal();
        }
        if (e.target.id === 'bulkMoveModal') {
            document.getElementById('bulkMoveModal').classList.add('hidden');
        }
        
        // Close search results when clicking outside
        const searchInput = document.getElementById('penerimaSearch');
        const resultsContainer = document.getElementById('penerimaResults');
        
        if (searchInput && resultsContainer && 
            !searchInput.contains(e.target) && 
            !resultsContainer.contains(e.target)) {
            resultsContainer.classList.add('hidden');
        }
    });
    
    // Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (selectedMailings.size > 0) {
                selectedMailings.clear();
                document.querySelectorAll('.mailing-checkbox').forEach(cb => cb.checked = false);
                updateUI();
            }
            document.getElementById('penerimaResults').classList.add('hidden');
        }
    });
});
</script>
@endpush