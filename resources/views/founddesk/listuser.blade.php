@extends('layouts.app-sidebar')

@section('title', 'Lost & Found')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Lost & Found</h2>
            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full
                         text-xs font-semibold bg-blue-100 text-blue-800">
                Barang Temuan & Penyerahan
            </span>
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('founddesk.create') }}"
               class="flex-1 sm:flex-none inline-flex items-center justify-center
                      px-4 py-2 bg-blue-600 text-white rounded-lg
                      text-sm font-semibold hover:bg-blue-700 transition">
                + Tambah Barang
            </a>

            <button id="toggleFilterBtn"
                class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700
                       rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div id="filterSection" class="bg-white border rounded-xl p-4 hidden">
        <form method="GET" action="{{ route('founddesk.index') }}"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari barang atau penemu..."
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status')==$status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Kategori</label>
                <select name="category_id"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id')==$category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Lokasi</label>
                <select name="location_id"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ request('location_id')==$location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Kondisi</label>
                <select name="condition_id"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Kondisi</option>
                    @foreach($conditions as $condition)
                        <option value="{{ $condition->id }}" {{ request('condition_id')==$condition->id ? 'selected' : '' }}>
                            {{ $condition->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- PERBAIKAN: Filter Tanggal Ditemukan (RANGE) --}}
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-sm font-medium text-gray-600">Dari Tgl</label>
                    <input type="date" name="found_date_from" value="{{ request('found_date_from') }}"
                           class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Sampai Tgl</label>
                    <input type="date" name="found_date_to" value="{{ request('found_date_to') }}"
                           class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="lg:col-span-3 flex flex-col sm:flex-row gap-2 justify-end">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">
                    Apply Filter
                </button>
                <a href="{{ route('founddesk.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 text-center">
                    Reset
                </a>
                
                {{-- TOMBOL DOWNLOAD CSV --}}
                <a href="{{ route('founddesk.export', request()->query()) }}"
                   class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 text-center inline-flex items-center justify-center gap-2">
                    <i class="fas fa-download"></i>
                    <span class="hidden sm:inline">Download CSV</span>
                    <span class="sm:hidden">CSV</span>
                </a>
            </div>
        </form>
    </div>

    {{-- ACTIVE FILTERS BADGE --}}
    @if(request('search') || request('status') || request('category_id') || request('location_id') || request('condition_id') || request('found_date_from') || request('found_date_to'))
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs text-gray-500">Filter aktif:</span>
        @if(request('search'))
        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-xs">
            {{ request('search') }}
            <a href="{{ route('founddesk.index', array_merge(request()->except('search'), ['page' => null])) }}" class="hover:text-blue-900">
                <i class="fas fa-times"></i>
            </a>
        </span>
        @endif
        @if(request('status'))
        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-xs">
            Status: {{ ucfirst(request('status')) }}
            <a href="{{ route('founddesk.index', array_merge(request()->except('status'), ['page' => null])) }}" class="hover:text-blue-900">
                <i class="fas fa-times"></i>
            </a>
        </span>
        @endif
        @if(request('category_id') && isset($categories) && $categories->find(request('category_id')))
        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-xs">
            Kategori: {{ $categories->find(request('category_id'))->name }}
            <a href="{{ route('founddesk.index', array_merge(request()->except('category_id'), ['page' => null])) }}" class="hover:text-blue-900">
                <i class="fas fa-times"></i>
            </a>
        </span>
        @endif
        @if(request('location_id') && isset($locations) && $locations->find(request('location_id')))
        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-xs">
            Lokasi: {{ $locations->find(request('location_id'))->name }}
            <a href="{{ route('founddesk.index', array_merge(request()->except('location_id'), ['page' => null])) }}" class="hover:text-blue-900">
                <i class="fas fa-times"></i>
            </a>
        </span>
        @endif
        @if(request('condition_id') && isset($conditions) && $conditions->find(request('condition_id')))
        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-xs">
            Kondisi: {{ $conditions->find(request('condition_id'))->name }}
            <a href="{{ route('founddesk.index', array_merge(request()->except('condition_id'), ['page' => null])) }}" class="hover:text-blue-900">
                <i class="fas fa-times"></i>
            </a>
        </span>
        @endif
        @if(request('found_date_from') || request('found_date_to'))
        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-xs">
            Tanggal: 
            @if(request('found_date_from') && request('found_date_to'))
                {{ request('found_date_from') }} s/d {{ request('found_date_to') }}
            @elseif(request('found_date_from'))
                Dari {{ request('found_date_from') }}
            @elseif(request('found_date_to'))
                Sampai {{ request('found_date_to') }}
            @endif
            <a href="{{ route('founddesk.index', array_merge(request()->except(['found_date_from', 'found_date_to']), ['page' => null])) }}" class="hover:text-blue-900">
                <i class="fas fa-times"></i>
            </a>
        </span>
        @endif
    </div>
    @endif

    {{-- DESKTOP TABLE --}}
    <div class="hidden md:block bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Id Transaksi</th>
                    <th class="px-4 py-3 text-left">Barang</th>
                    <th class="px-4 py-3 text-left">Penemu</th>
                    <th class="px-4 py-3 text-center">Foto</th>
                    <th class="px-4 py-3 text-center">Bukti Penyerahan</th>
                    <th class="px-4 py-3 text-left">Penerima</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($items as $item)
                @php
                    $disposition = $item->dispositions->sortByDesc('created_at')->first();
                    
                    $statusColors = [
                        'tersedia' => 'bg-green-100 text-green-800',
                        'diklaim' => 'bg-yellow-100 text-yellow-800',
                        'dikirim' => 'bg-blue-100 text-blue-800',
                        'diserahkan' => 'bg-purple-100 text-purple-800',
                        'kadaluarsa' => 'bg-red-100 text-red-800'
                    ];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                            {{ $item->item_code }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $item->name }}</div>
                        @if($item->category)
                        <div class="text-xs text-gray-500">{{ $item->category->name }}</div>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        {{ $item->found_by ?? '-' }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        @if($item->photo)
                        <div class="flex flex-col items-center gap-1">
                            <button onclick="showImage('{{ route('founddesk.photo', $item->id) }}', 'Foto Barang: {{ $item->name }}')"
                                    class="inline-flex items-center justify-center w-7 h-7 bg-blue-50 text-blue-600 rounded hover:bg-blue-100"
                                    title="Lihat Foto Barang">
                                <i class="fas fa-image"></i>
                            </button>
                            <span class="text-[10px] text-gray-400">{{ $item->created_at ? date('d/m/y H:i', strtotime($item->created_at)) : '-' }}</span>
                        </div>
                        @else
                        <span class="text-gray-300">-</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-center">
                        @if($disposition && $disposition->handover_photo)
                        <div class="flex flex-col items-center gap-1">
                            <button onclick="showImage('{{ route('founddesk.disposition.photo', [$disposition->id, 'handover']) }}', 'Foto Penyerahan: {{ $disposition->recipient_name }}')"
                                    class="inline-flex items-center justify-center w-7 h-7 bg-green-50 text-green-600 rounded hover:bg-green-100"
                                    title="Lihat Foto Penyerahan">
                                <i class="fas fa-camera"></i>
                            </button>
                            <span class="text-[10px] text-gray-400">{{ $disposition->created_at ? date('d/m/y H:i', strtotime($disposition->created_at)) : '-' }}</span>
                        </div>
                        @else
                        <span class="text-gray-300">-</span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        @if($disposition && $disposition->recipient_name)
                        <span>{{ $disposition->recipient_name }}</span>
                        @if($disposition->recipient_contact)
                        <div class="text-xs text-gray-400">{{ $disposition->recipient_contact }}</div>
                        @endif
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($item->status) }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-2">
                            @if($item->status == 'tersedia' && $item->current_stock > 0)
                            <a href="{{ route('founddesk.disposition.create', ['item_id' => $item->id]) }}" 
                               class="p-2 text-green-600 hover:text-green-800 hover:bg-green-50 rounded-lg transition"
                               title="Serahkan Barang">
                                <i class="fas fa-hand-holding-heart"></i>
                            </a>
                            @endif
                            
                            @if($disposition)
                            <a href="{{ route('founddesk.disposition.show', $disposition->id) }}" 
                               class="p-2 text-purple-600 hover:text-purple-800 hover:bg-purple-50 rounded-lg transition"
                               title="Detail Penyerahan">
                                <i class="fas fa-file-alt"></i>
                            </a>
                            @endif
                            
                            <!-- <button onclick="confirmDelete({{ $item->id }})" 
                                    class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition"
                                    title="Hapus Barang">
                                <i class="fas fa-trash"></i>
                            </button> -->
                        </div>
                        <form id="delete-form-{{ $item->id }}" 
                              action="{{ route('founddesk.destroy', $item->id) }}" 
                              method="POST" 
                              class="hidden">
                            @csrf @method('DELETE')
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-10 text-center text-gray-500">
                        <i class="fas fa-box-open mb-2 text-2xl opacity-50"></i>
                        <p class="text-sm">Belum ada barang temuan</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        {{-- DESKTOP PAGINATION --}}
        @if($items->hasPages())
        <div class="p-4 border-t">
            {{ $items->links() }}
        </div>
        @endif
    </div>

    {{-- MOBILE CARDS --}}
    <div class="md:hidden space-y-2">
        @forelse($items as $item)
        @php
            $disposition = $item->dispositions->sortByDesc('created_at')->first();
        @endphp
        <div class="bg-white border rounded-lg overflow-hidden">
            {{-- Header Row --}}
            <div class="grid grid-cols-5 bg-gray-100 text-[10px] font-semibold text-gray-700 p-2 border-b">
                <div class="col-span-1">Id Transaksi</div>
                <div class="col-span-1">Barang</div>
                <div class="col-span-1 text-center">Foto</div>
                <div class="col-span-1 text-center">Bukti Penyerahan</div>
                <div class="col-span-1 text-center">Action</div>
            </div>

            {{-- Data Row 1 --}}
            <div class="grid grid-cols-5 p-2 text-xs border-b">
                <div class="col-span-1 font-mono font-semibold">{{ $item->item_code }}</div>
                <div class="col-span-1 font-medium">{{ $item->name }}</div>
                <div class="col-span-1 text-center">
                    @if($item->photo)
                    <div class="flex flex-col items-center gap-1">
                        <button onclick="showImage('{{ route('founddesk.photo', $item->id) }}', 'Foto Barang: {{ $item->name }}')"
                                class="inline-flex items-center justify-center w-6 h-6 bg-blue-50 text-blue-600 rounded hover:bg-blue-100">
                            <i class="fas fa-image text-xs"></i>
                        </button>
                        <span class="text-[8px] text-gray-400">{{ $item->created_at ? date('d/m/y H:i', strtotime($item->created_at)) : '-' }}</span>
                    </div>
                    @else
                    <span class="text-gray-300">-</span>
                    @endif
                </div>
                <div class="col-span-1 text-center">
                    @if($disposition && $disposition->handover_photo)
                    <div class="flex flex-col items-center gap-1">
                        <button onclick="showImage('{{ route('founddesk.disposition.photo', [$disposition->id, 'handover']) }}', 'Foto Penyerahan: {{ $disposition->recipient_name }}')"
                                class="inline-flex items-center justify-center w-6 h-6 bg-green-50 text-green-600 rounded hover:bg-green-100">
                            <i class="fas fa-camera text-xs"></i>
                        </button>
                        <span class="text-[8px] text-gray-400">{{ $disposition->created_at ? date('d/m/y H:i', strtotime($disposition->created_at)) : '-' }}</span>
                    </div>
                    @else
                    <span class="text-gray-300">-</span>
                    @endif
                </div>
                <div class="col-span-1 text-center">
                    <div class="flex items-center justify-center gap-1">
                        @if($item->status == 'tersedia' && $item->current_stock > 0)
                        <a href="{{ route('founddesk.disposition.create', ['item_id' => $item->id]) }}" 
                           class="p-1 text-green-600 hover:text-green-800" title="Serahkan">
                            <i class="fas fa-hand-holding-heart text-xs"></i>
                        </a>
                        @endif
                        @if($disposition)
                        <a href="{{ route('founddesk.disposition.show', $disposition->id) }}" 
                           class="p-1 text-purple-600 hover:text-purple-800" title="Penyerahan">
                            <i class="fas fa-file-alt text-xs"></i>
                        </a>
                        @endif
                        <!-- <button onclick="confirmDelete({{ $item->id }})" 
                                class="p-1 text-red-600 hover:text-red-800" title="Hapus">
                            <i class="fas fa-trash text-xs"></i>
                        </button> -->
                    </div>
                </div>
            </div>

            {{-- Data Row 2 --}}
            <div class="grid grid-cols-5 p-2 text-[10px] text-gray-500 bg-gray-50">
                <div class="col-span-1">{{ $item->found_date ? date('d-m-y', strtotime($item->found_date)) : '-' }}</div>
                <div class="col-span-1">{{ $item->category->name ?? '-' }}</div>
                <div class="col-span-1 text-center">{{ $item->found_by ?? '-' }}</div>
                <div class="col-span-1 text-center">{{ $disposition->recipient_name ?? '-' }}</div>
                <div class="col-span-1 text-center">
                    <span class="px-1 py-0.5 rounded-full text-[8px] font-semibold
                        @if($item->status == 'tersedia') bg-green-100 text-green-700
                        @elseif($item->status == 'diklaim') bg-yellow-100 text-yellow-700
                        @elseif($item->status == 'dikirim') bg-blue-100 text-blue-700
                        @elseif($item->status == 'diserahkan') bg-purple-100 text-purple-700
                        @else bg-gray-100 text-gray-700
                        @endif">
                        {{ substr(ucfirst($item->status), 0, 3) }}
                    </span>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white border rounded-lg p-8 text-center text-gray-500">
            <i class="fas fa-box-open text-3xl mb-2 opacity-50"></i>
            <p class="text-sm">Belum ada barang temuan</p>
        </div>
        @endforelse

        {{-- MOBILE PAGINATION --}}
        @if($items->hasPages())
        <div class="mt-4">
            {{ $items->links() }}
        </div>
        @endif
    </div>
</div>

{{-- MODAL PREVIEW GAMBAR --}}
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 p-4" style="display: none;">
    <div class="relative w-full max-w-4xl max-h-screen">
        <div class="absolute top-2 right-2 flex gap-2 z-10">
            <button onclick="closeModal()" class="bg-white rounded-full p-2 hover:bg-gray-200 transition shadow-lg">
                <i class="fas fa-times text-gray-600"></i>
            </button>
        </div>
        <div class="bg-white rounded-lg overflow-hidden">
            <div class="p-3 sm:p-4 border-b">
                <h3 id="modalTitle" class="font-semibold text-sm sm:text-lg">Preview Gambar</h3>
            </div>
            <div class="p-3 sm:p-4 flex items-center justify-center">
                <img id="modalImage" src="" alt="Preview" class="max-w-full max-h-[50vh] sm:max-h-[70vh] object-contain">
            </div>
            <div class="p-3 sm:p-4 border-t flex justify-end">
                <a id="downloadLink" href="#" download class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm text-center">
                    <i class="fas fa-download mr-2"></i>Download
                </a>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT --}}
<script>
document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden')
});

function confirmDelete(id) {
    if (confirm('Yakin ingin menghapus barang ini?')) {
        document.getElementById('delete-form-' + id).submit();
    }
}

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

// Auto show filter if there are parameters
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const hasParams = urlParams.toString().length > 0;
    const filterSection = document.getElementById('filterSection');
    
    if (hasParams) {
        filterSection.classList.remove('hidden');
    }
});

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