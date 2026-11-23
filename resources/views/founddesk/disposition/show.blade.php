@extends('layouts.app-sidebar')

@section('content')
<div class="p-6 bg-slate-50 min-h-screen space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Detail Penyerahan Barang</h1>
            <p class="text-sm text-slate-500">Informasi lengkap penyerahan barang</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('founddesk.index') }}"
               class="px-4 py-2 rounded-lg border bg-white hover:bg-slate-100 text-sm">
                ← Kembali
            </a>
            @if($disposition->status == 'pending')
                <form action="{{ route('founddesk.disposition.cancel', $disposition->id) }}"
                      method="POST"
                      class="inline">
                    @csrf @method('PATCH')
                    <button type="submit"
                            onclick="return confirm('Batalkan penyerahan ini? Stok akan dikembalikan.')"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 text-sm">
                        <i class="fas fa-times mr-2"></i>Batalkan Penyerahan
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Status Banner --}}
    <div class="rounded-xl p-4 
        @if($disposition->status == 'diserahkan') bg-green-50 border border-green-200
        @elseif($disposition->status == 'pending') bg-yellow-50 border border-yellow-200
        @elseif($disposition->status == 'dibatalkan') bg-red-50 border border-red-200
        @endif">
        <div class="flex items-center gap-3">
            <i class="fas 
                @if($disposition->status == 'diserahkan') fa-check-circle text-green-600
                @elseif($disposition->status == 'pending') fa-clock text-yellow-600
                @elseif($disposition->status == 'dibatalkan') fa-times-circle text-red-600
                @endif text-xl">
            </i>
            <div>
                <p class="font-semibold 
                    @if($disposition->status == 'diserahkan') text-green-700
                    @elseif($disposition->status == 'pending') text-yellow-700
                    @elseif($disposition->status == 'dibatalkan') text-red-700
                    @endif">
                    Status: {{ ucfirst($disposition->status) }}
                </p>
                <p class="text-sm text-slate-600">
                    @if($disposition->status == 'diserahkan')
                        Barang telah diserahkan pada {{ date('d/m/Y H:i', strtotime($disposition->created_at)) }}
                    @elseif($disposition->status == 'pending')
                        Menunggu proses penyerahan
                    @elseif($disposition->status == 'dibatalkan')
                        Penyerahan dibatalkan pada {{ date('d/m/Y H:i', strtotime($disposition->updated_at)) }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Main Info (Kiri) --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- SATU KARTU UTAMA: No. Transaksi + Informasi Penerima + Detail Penyerahan --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                {{-- Header dengan No. Transaksi dan Tanggal --}}
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-hand-holding-heart text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-blue-600">No. Transaksi</p>
                            <p class="font-semibold text-lg text-blue-700">{{ $disposition->disposition_no }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-400">Diserahkan Pada</p>
                        <p class="text-sm font-medium text-slate-700">{{ $disposition->created_at ? date('d/m/Y H:i', strtotime($disposition->created_at)) : '-' }}</p>
                    </div>
                </div>

                {{-- Grid 2 Kolom: Informasi Penerima (kiri) dan Detail Penyerahan (kanan) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Kolom Kiri: Informasi Penerima --}}
                    <div>
                        <h3 class="font-semibold text-md mb-3 text-slate-700 flex items-center gap-2">
                            <i class="fas fa-user text-blue-500 text-sm"></i>
                            Informasi Penerima
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-slate-500">Nama Lengkap</p>
                                <p class="font-medium">{{ $disposition->recipient_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">No. Identitas</p>
                                <p class="font-medium">{{ $disposition->recipient_id ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">No. Kontak</p>
                                <p class="font-medium">{{ $disposition->recipient_contact ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Kolom Kanan: Detail Penyerahan --}}
                    <div>
                        <h3 class="font-semibold text-md mb-3 text-slate-700 flex items-center gap-2">
                            <i class="fas fa-clipboard-list text-blue-500 text-sm"></i>
                            Detail Penyerahan
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-slate-500">Tanggal Penyerahan</p>
                                <p class="font-medium">{{ date('d/m/Y', strtotime($disposition->disposition_date)) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Qty</p>
                                <p class="font-medium">{{ $disposition->quantity }} {{ $disposition->item->unit }}</p>
                            </div>
                            @if($disposition->notes)
                            <div>
                                <p class="text-xs text-slate-500">Catatan</p>
                                <p class="text-sm text-slate-700 bg-slate-50 p-2 rounded">{{ $disposition->notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Informasi Barang (dengan Nama Penemu) --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-lg">Informasi Barang</h3>
                    <div class="text-right">
                        <p class="text-xs text-slate-400">Barang Dibuat</p>
                        <p class="text-sm font-medium text-slate-700">{{ $disposition->item->created_at ? date('d/m/Y H:i', strtotime($disposition->item->created_at)) : '-' }}</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-slate-500">Kode Barang</p>
                        <p class="font-mono text-blue-600">{{ $disposition->item->item_code }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mt-3">
                        <div>
                            <p class="text-xs text-slate-500">Nama Barang</p>
                            <p class="font-medium text-lg">{{ $disposition->item->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Ditemukan Oleh</p>
                            <p class="font-medium">{{ $disposition->item->found_by ?? '-' }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mt-3">
                        <div>
                            <p class="text-xs text-slate-500">Kategori</p>
                            <p class="font-medium">{{ $disposition->item->category->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Kondisi</p>
                            <p class="font-medium">{{ $disposition->item->condition->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Qty</p>
                            <p class="font-medium">{{ $disposition->item->current_stock }} {{ $disposition->item->unit }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Tanggal Ditemukan</p>
                            <p class="font-medium">{{ $disposition->item->found_date ? date('d/m/Y', strtotime($disposition->item->found_date)) : '-' }}</p>
                        </div>
                    </div>
                    {{-- DESKRIPSI BARANG (BARU) --}}
                    @if($disposition->item->description)
                    <div class="mt-3 pt-2 border-t border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Deskripsi Barang</p>
                        <p class="text-sm text-slate-700 bg-slate-50 p-3 rounded-lg">
                            {{ $disposition->item->description }}
                        </p>
                    </div>
                    @endif
                    <div class="pt-3">
                        <a href="{{ route('founddesk.index') }}?search={{ $disposition->item->item_code }}" 
                        class="inline-block text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-right mr-1"></i>Lihat Daftar Barang
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Sidebar (Kanan) - Semua Foto --}}
        <div class="space-y-6">
            
            {{-- FOTO PENYERAHAN (DI ATAS) --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Foto Penyerahan</h3>
                    <span class="text-[10px] text-gray-400">{{ $disposition->created_at ? date('d/m/y H:i', strtotime($disposition->created_at)) : '-' }}</span>
                </div>
                
                @if($disposition->handover_photo)
                    <div class="border rounded-lg overflow-hidden cursor-pointer" onclick="showImage('{{ route('founddesk.disposition.photo', [$disposition->id, 'handover']) }}', 'Foto Penyerahan')">
                        <img src="{{ route('founddesk.disposition.photo', [$disposition->id, 'handover']) }}" 
                             class="w-full object-cover hover:opacity-90 transition">
                    </div>
                    <div class="flex gap-2 mt-3">
                        <button onclick="showImage('{{ route('founddesk.disposition.photo', [$disposition->id, 'handover']) }}', 'Foto Penyerahan')"
                                class="flex-1 px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-search-plus mr-2"></i>Perbesar
                        </button>
                        <a href="{{ route('founddesk.disposition.photo', [$disposition->id, 'handover']) }}" 
                           target="_blank"
                           class="px-3 py-2 text-sm border rounded-lg hover:bg-slate-100">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                @else
                    <div class="border-2 border-dashed rounded-lg p-8 text-center text-slate-400">
                        <i class="fas fa-camera text-4xl mb-2"></i>
                        <p class="text-sm">Tidak ada foto penyerahan</p>
                    </div>
                @endif
            </div>
            
            {{-- FOTO BARANG (DI BAWAH) --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Foto Barang</h3>
                    <span class="text-[10px] text-gray-400">{{ $disposition->item->created_at ? date('d/m/y H:i', strtotime($disposition->item->created_at)) : '-' }}</span>
                </div>
                
                @if($disposition->item->photo)
                    <div class="border rounded-lg overflow-hidden cursor-pointer" onclick="showImage('{{ route('founddesk.photo', $disposition->item->id) }}', 'Foto Barang: {{ $disposition->item->name }}')">
                        <img src="{{ route('founddesk.photo', $disposition->item->id) }}" 
                             class="w-full object-cover hover:opacity-90 transition">
                    </div>
                    <div class="flex gap-2 mt-3">
                        <button onclick="showImage('{{ route('founddesk.photo', $disposition->item->id) }}', 'Foto Barang: {{ $disposition->item->name }}')"
                                class="flex-1 px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-search-plus mr-2"></i>Perbesar
                        </button>
                        <a href="{{ route('founddesk.photo', $disposition->item->id) }}" 
                           target="_blank"
                           class="px-3 py-2 text-sm border rounded-lg hover:bg-slate-100">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                @else
                    <div class="border-2 border-dashed rounded-lg p-8 text-center text-slate-400">
                        <i class="fas fa-image text-4xl mb-2"></i>
                        <p class="text-sm">Tidak ada foto barang</p>
                    </div>
                @endif
            </div>
            
            {{-- Info Sistem --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold mb-3">Informasi Sistem</h3>
                
                <div class="space-y-2 text-sm">
                    <div>
                        <p class="text-xs text-slate-500">Dibuat Oleh</p>
                        <p class="font-medium">{{ $disposition->creator->name ?? 'System' }}</p>
                    </div>
                    
                    @if($disposition->approved_at)
                    <div class="pt-2 border-t">
                        <p class="text-xs text-slate-500">Dikonfirmasi Oleh</p>
                        <p class="font-medium">{{ $disposition->approver->name ?? '-' }}</p>
                        <p class="text-xs text-slate-400">{{ date('d/m/Y H:i', strtotime($disposition->approved_at)) }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal untuk Preview Gambar --}}
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50" style="display: none;">
    <div class="relative max-w-4xl max-h-screen p-4">
        <div class="absolute top-2 right-2 flex gap-2">
            <button onclick="closeModal()" class="bg-white rounded-full p-2 hover:bg-gray-200 transition">
                <i class="fas fa-times text-gray-600"></i>
            </button>
        </div>
        <div class="bg-white rounded-lg overflow-hidden">
            <div class="p-4 border-b">
                <h3 id="modalTitle" class="font-semibold text-lg">Preview Gambar</h3>
            </div>
            <div class="p-4 flex items-center justify-center">
                <img id="modalImage" src="" alt="Preview" class="max-w-full max-h-[70vh] object-contain">
            </div>
            <div class="p-4 border-t flex justify-end">
                <a id="downloadLink" href="#" download class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-download mr-2"></i>Download
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi untuk menampilkan modal gambar
function showImage(imageUrl, title) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    const downloadLink = document.getElementById('downloadLink');
    
    modalImg.src = imageUrl;
    modalTitle.textContent = title || 'Preview Gambar';
    downloadLink.href = imageUrl;
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

function closeModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Style tambahan untuk modal
const style = document.createElement('style');
style.textContent = `
    #imageModal {
        animation: fadeIn 0.3s ease;
    }
    
    #imageModal .bg-white {
        animation: slideIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from {
            transform: scale(0.9);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
</script>
@endsection