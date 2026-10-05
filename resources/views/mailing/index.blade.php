@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800">

{{-- ================= HEADER ================= --}}
<div class="flex justify-between items-center">
    <div>
        <h1 class="text-xl font-semibold">Arsip Mailing Selesai</h1>
        <p class="text-xs text-gray-500">Hanya menampilkan mailing yang sudah selesai</p>
        
        {{-- Tampilkan info akses --}}
        @if(isset($canViewAll) && !$canViewAll)
        <div class="mt-1">
            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-md">
                <i class="fas fa-user-lock mr-1"></i> Hanya melihat mailing Anda sendiri
            </span>
        </div>
        @endif
    </div>
    <div class="flex gap-2">
        <button id="toggleFilterBtn"
                class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-semibold">
            Filter
        </button>
    </div>
</div>

{{-- ================= FILTER ================= --}}
<div id="filterSection" class="bg-white border rounded-xl p-4 hidden">
    <form method="GET" class="space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" 
                       class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" 
                       class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Cari Resi</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nomor resi"
                       class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
        <div class="flex gap-2">
            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                Terapkan Filter
            </button>
            <a href="{{ route('mailing.index') }}"
               class="bg-gray-200 px-4 py-2 rounded-lg text-sm font-semibold">
                Reset
            </a>
        </div>
    </form>
</div>

@if(request()->hasAny(['start_date', 'end_date', 'search']))
<div class="bg-blue-50 border border-blue-200 rounded-xl p-3">
    <div class="flex items-start">
        <i class="fas fa-filter text-blue-500 mt-0.5 mr-3"></i>
        <div>
            <p class="text-sm font-medium text-blue-800 mb-2">Filter Aktif</p>
            <div class="flex flex-wrap gap-2">
                @if(request('start_date'))
                <span class="px-2 py-1 bg-white text-blue-700 text-xs rounded border border-blue-200">
                    Mulai: {{ request('start_date') }}
                </span>
                @endif
                @if(request('end_date'))
                <span class="px-2 py-1 bg-white text-blue-700 text-xs rounded border border-blue-200">
                    Akhir: {{ request('end_date') }}
                </span>
                @endif
                @if(request('search'))
                <span class="px-2 py-1 bg-white text-blue-700 text-xs rounded border border-blue-200">
                    Resi: "{{ request('search') }}"
                </span>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

{{-- ================= MOBILE ================= --}}
<div class="block sm:hidden space-y-3">
    @forelse($mailings as $m)
    @php
    // Hitung durasi proses
    $sla = null;
    if($m->mailing_tanggal_input && $m->mailing_tanggal_selesai){
        $diff = $m->mailing_tanggal_input->diff($m->mailing_tanggal_selesai);
        $sla = ($diff->d ? $diff->d.' hari ' : '') .
               ($diff->h ? $diff->h.' jam ' : '') .
               ($diff->i ? $diff->i.' menit' : '');
    }
    @endphp

    <div class="bg-white rounded-xl border p-4 shadow-sm">
        {{-- HEADER --}}
        <div class="flex justify-between items-center cursor-pointer toggleCardHeader">
            <div>
                <div class="font-semibold text-gray-900">{{ $m->mailing_resi }}</div>
                <div class="text-xs text-gray-500">
                    {{ $m->mailing_pengirim }} → 
                    @if($m->pelanggan && $m->pelanggan->nama_pelanggan)
                        {{ $m->pelanggan->nama_pelanggan }}
                    @else
                        {{ $m->mailing_penerima }}
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                    Selesai
                </span>
                <svg class="w-4 h-4 transition-transform" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>

        {{-- DROPDOWN CONTENT --}}
        <div class="hidden toggleCardContent mt-4 space-y-4">
            <div class="flex flex-col gap-4">
                {{-- TIMELINE VERTICAL --}}
                <div class="relative pl-8">
                    {{-- Garis vertikal --}}
                    <div class="absolute left-3 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                    
                    {{-- Item timeline --}}
                    <div class="relative mb-4">
                        <div class="absolute -left-7 top-0 w-4 h-4 bg-blue-500 rounded-full"></div>
                        <div class="ml-1">
                            <div class="text-xs font-medium text-gray-700">Mailing Room</div>
                            <div class="text-xs text-gray-500">{{ optional($m->mailing_tanggal_input)->format('d M Y H:i') }}</div>
                        </div>
                    </div>

                    @if($m->mailing_tanggal_ob47)
                    <div class="relative mb-4">
                        <div class="absolute -left-7 top-0 w-4 h-4 bg-indigo-500 rounded-full"></div>
                        <div class="ml-1">
                            <div class="text-xs font-medium text-gray-700">Lantai 47</div>
                            <div class="text-xs text-gray-500">{{ $m->mailing_tanggal_ob47->format('d M Y H:i') }}</div>
                        </div>
                    </div>
                    @endif

                    @if($m->mailing_tanggal_selesai)
                    <div class="relative mb-4">
                        <div class="absolute -left-7 top-0 w-4 h-4 bg-green-500 rounded-full"></div>
                        <div class="ml-1">
                            <div class="text-xs font-medium text-gray-700">Selesai</div>
                            <div class="text-xs text-gray-500">{{ $m->mailing_tanggal_selesai->format('d M Y H:i') }}</div>
                        </div>
                    </div>
                    @endif
                    
                    @if($m->mailing_penerima_distribusi)
                    <div class="relative">
                        <div class="absolute -left-7 top-0 w-4 h-4 bg-yellow-500 rounded-full"></div>
                        <div class="ml-1">
                            <div class="text-xs font-medium text-gray-700">Diterima oleh</div>
                            <div class="text-xs text-gray-600">{{ $m->mailing_penerima_distribusi }}</div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- FOTO BUKTI --}}
                @if($m->mailing_foto)
                <div class="w-full">
                    <p class="text-xs font-medium text-gray-700 mb-2">Foto Bukti Distribusi:</p>
                    <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                        data-ga-src="{{ route('mailing.view-foto', $m->id_mailing) }}"
                        class="w-full max-h-40 object-contain bg-gray-100 p-1 rounded-lg border cursor-pointer"
                        onclick="openPhotoModal(this.src.indexOf('blob:') === 0 ? this.src : this.dataset.gaSrc)"
                        alt="Foto bukti distribusi">
                </div>
                @endif
            </div>

            @if($sla)
            <div class="bg-gray-100 border rounded-lg px-3 py-2 text-xs">
                <i class="fas fa-clock text-gray-500 mr-1"></i>
                <span class="font-semibold">Durasi Proses:</span> {{ $sla }}
            </div>
            @endif
            
            {{-- INFO TAMBAHAN --}}
            @if($m->mailing_lantai || $m->mailing_expedisi)
            <div class="flex flex-wrap gap-2 text-xs">
                @if($m->mailing_lantai)
                <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded">
                    <i class="fas fa-building mr-1"></i> Lantai {{ $m->mailing_lantai }}
                </span>
                @endif
                @if($m->mailing_expedisi)
                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded">
                    <i class="fas fa-truck mr-1"></i> {{ $m->mailing_expedisi }}
                </span>
                @endif
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="text-center py-10 border-2 border-dashed border-gray-300 rounded-xl">
        <i class="fas fa-inbox text-4xl text-gray-400 mb-3"></i>
        <p class="font-semibold text-gray-600">Tidak ada data mailing selesai</p>
        <p class="text-sm text-gray-500 mt-1">
            @if(request()->hasAny(['start_date', 'end_date', 'search']))
            Coba ubah filter pencarian
            @else
            Belum ada mailing yang diselesaikan
            @endif
        </p>
        <a href="{{ route('mailing.proses') }}" 
           class="mt-3 inline-block text-blue-600 hover:text-blue-800 text-sm font-semibold">
            Lihat mailing dalam proses →
        </a>
    </div>
    @endforelse
</div>

{{-- ================= DESKTOP ================= --}}
<div class="hidden sm:block bg-white rounded-xl border overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left">Resi</th>
                <th class="px-4 py-3 text-left">Pengirim</th>
                <th class="px-4 py-3 text-left">Penerima</th>
                <th class="px-4 py-3 text-left">Lantai</th>
                <th class="px-4 py-3 text-left">Selesai</th>
                <th class="px-4 py-3 text-left">Foto</th>
                <th class="px-4 py-3 text-left">Info</th>
            </tr>
        </thead>

        <tbody class="divide-y">
            @forelse($mailings as $m)
            <tr class="toggleRow cursor-pointer hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ $m->mailing_resi }}</td>
                <td class="px-4 py-3">{{ $m->mailing_pengirim }}</td>
                <td class="px-4 py-3">
                    @if($m->pelanggan && $m->pelanggan->nama_pelanggan)
                        {{ $m->pelanggan->nama_pelanggan }}
                    @else
                        {{ $m->mailing_penerima }}
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($m->mailing_lantai)
                    <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded">
                        Lantai {{ $m->mailing_lantai }}
                    </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs">
                    {{ optional($m->mailing_tanggal_selesai)->format('d M Y H:i') }}
                </td>
                <td class="px-4 py-3">
                    @if($m->mailing_foto)
                    <button onclick="openPhotoModal('{{ route('mailing.view-foto', $m->id_mailing) }}')"
                            class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                        <i class="fas fa-eye mr-1"></i> Lihat
                    </button>
                    @else
                    <span class="text-gray-400 text-xs">-</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <button class="text-gray-600 hover:text-gray-800 text-xs">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </td>
            </tr>

            <tr class="hidden toggleContent bg-gray-50">
                <td colspan="7" class="p-6 bg-gray-50">
    <div class="grid grid-cols-12 gap-8">

        {{-- ================= TIMELINE ================= --}}
        <div class="col-span-12 lg:col-span-6">
            <h4 class="text-xs font-semibold text-gray-700 mb-4">TIMELINE PROSES</h4>

            <div class="relative pl-8">
                <div class="absolute left-3 top-0 bottom-0 w-0.5 bg-gray-300"></div>

                {{-- Mailing Room --}}
                <div class="relative mb-5">
                    <div class="absolute -left-7 w-4 h-4 bg-blue-500 rounded-full"></div>
                    <div>
                        <div class="text-xs font-medium">Mailing Room</div>
                        <div class="text-xs text-gray-500">
                            {{ optional($m->mailing_tanggal_input)->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>

                {{-- Lantai --}}
                @if($m->mailing_tanggal_ob47)
                <div class="relative mb-5">
                    <div class="absolute -left-7 w-4 h-4 bg-indigo-500 rounded-full"></div>
                    <div>
                        <div class="text-xs font-medium">Lantai {{ $m->mailing_lantai }}</div>
                        <div class="text-xs text-gray-500">
                            {{ $m->mailing_tanggal_ob47->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>
                @endif

                {{-- Selesai --}}
                @if($m->mailing_tanggal_selesai)
                <div class="relative mb-5">
                    <div class="absolute -left-7 w-4 h-4 bg-green-500 rounded-full"></div>
                    <div>
                        <div class="text-xs font-medium">Selesai</div>
                        <div class="text-xs text-gray-500">
                            {{ $m->mailing_tanggal_selesai->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>
                @endif

                {{-- Penerima --}}
                @if($m->mailing_penerima_distribusi)
                <div class="relative">
                    <div class="absolute -left-7 w-4 h-4 bg-yellow-500 rounded-full"></div>
                    <div>
                        <div class="text-xs font-medium">Diterima oleh</div>
                        <div class="text-xs text-gray-600">
                            {{ $m->mailing_penerima_distribusi }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- ================= INFO ================= --}}
        <div class="col-span-12 lg:col-span-3 space-y-3">
            <h4 class="text-xs font-semibold text-gray-700">INFORMASI</h4>

            <div class="text-xs text-gray-600 flex items-center gap-2">
                <i class="fas fa-building text-blue-500"></i>
                Lantai {{ $m->mailing_lantai }}
            </div>

            <div class="text-xs text-gray-600 flex items-center gap-2">
                <i class="fas fa-truck"></i>
                {{ $m->mailing_expedisi }}
            </div>

            @php
                $sla = null;
                if($m->mailing_tanggal_input && $m->mailing_tanggal_selesai){
                    $diff = $m->mailing_tanggal_input->diff($m->mailing_tanggal_selesai);
                    $sla = ($diff->d ? $diff->d.' hari ' : '') .
                           ($diff->h ? $diff->h.' jam ' : '') .
                           ($diff->i ? $diff->i.' menit' : '');
                }
            @endphp

            @if($sla)
            <div class="text-xs text-gray-600 flex items-center gap-2">
                <i class="fas fa-clock"></i>
                {{ $sla }}
            </div>
            @endif
        </div>

        {{-- ================= FOTO ================= --}}
        <div class="col-span-12 lg:col-span-3">
            <h4 class="text-xs font-semibold text-gray-700 mb-2">FOTO BUKTI</h4>

            @if($m->mailing_foto)
            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                 data-ga-src="{{ route('mailing.view-foto', $m->id_mailing) }}"
                 class="rounded-xl border w-full cursor-pointer hover:opacity-90"
                 onclick="openPhotoModal(this.src.indexOf('blob:') === 0 ? this.src : this.dataset.gaSrc)">
            <p class="text-xs text-gray-500 text-center mt-1">
                Klik untuk memperbesar
            </p>
            @else
            <div class="text-xs text-gray-400 italic">Tidak ada foto</div>
            @endif
        </div>

    </div>
</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center">
                    <i class="fas fa-inbox text-3xl text-gray-400 mb-3"></i>
                    <p class="font-semibold text-gray-600">Tidak ada data mailing selesai</p>
                    <p class="text-sm text-gray-500 mt-1">
                        @if(request()->hasAny(['start_date', 'end_date', 'search']))
                        Coba ubah filter pencarian
                        @else
                        Belum ada mailing yang diselesaikan
                        @endif
                    </p>
                    <a href="{{ route('mailing.proses') }}" 
                       class="mt-3 inline-block text-blue-600 hover:text-blue-800 text-sm font-semibold">
                        Lihat mailing dalam proses →
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

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

{{-- ================= PHOTO MODAL ================= --}}
<div id="photoModal"
     class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4">
    <div class="relative max-w-4xl max-h-[90vh]">
        <img id="photoModalImg"
             class="rounded-xl bg-white shadow-lg max-h-[85vh] object-contain">
        <button onclick="document.getElementById('photoModal').classList.add('hidden')"
                class="absolute -top-3 -right-3 bg-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg hover:bg-gray-100">
            <i class="fas fa-times text-gray-700"></i>
        </button>
    </div>
</div>

{{-- ================= SCRIPT ================= --}}
<script src="{{ asset('js/ga-file-loader.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle filter section
    const toggleFilterBtn = document.getElementById('toggleFilterBtn');
    const filterSection = document.getElementById('filterSection');
    
    if (toggleFilterBtn && filterSection) {
        toggleFilterBtn.addEventListener('click', function() {
            filterSection.classList.toggle('hidden');
            const icon = this.querySelector('i') || this;
            if (filterSection.classList.contains('hidden')) {
                icon.innerHTML = '<i class="fas fa-chevron-down mr-1"></i> Filter';
            } else {
                icon.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> Filter';
            }
        });
    }

    // Mobile card toggle
    document.querySelectorAll('.toggleCardHeader').forEach(h => {
        h.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const icon = this.querySelector('svg');
            content.classList.toggle('hidden');
            if (icon) {
                icon.classList.toggle('rotate-180');
            }
        });
    });

    // Desktop row toggle
    document.querySelectorAll('.toggleRow').forEach(row => {
        row.addEventListener('click', function() {
            const content = this.nextElementSibling;
            content.classList.toggle('hidden');
            
            // Toggle icon
            const icon = this.querySelector('i');
            if (icon) {
                if (content.classList.contains('hidden')) {
                    icon.className = 'fas fa-chevron-down';
                } else {
                    icon.className = 'fas fa-chevron-up';
                }
            }
        });
    });
});

function openPhotoModal(src){
    const modal = document.getElementById('photoModal');
    const img = document.getElementById('photoModalImg');
    if (window.GaFileLoader) { GaFileLoader.load(img, src); } else { img.src = src; }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Close modal on click outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    });
}
</script>
@endsection