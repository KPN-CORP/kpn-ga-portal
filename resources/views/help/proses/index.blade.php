{{-- resources/views/help/proses/index.blade.php --}}
@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Proses Tiket</h2>
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('help.tiket.index') }}"
               class="flex-1 sm:flex-none inline-flex items-center justify-center
                      px-4 py-2 bg-blue-100 text-blue-700 rounded-lg
                      text-sm font-semibold hover:bg-blue-200 transition">
                <i class="fas fa-arrow-left mr-2"></i> Tiket Saya
            </a>

            <button id="toggleFilterBtn"
                class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700
                       rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div id="filterSection" class="bg-white border rounded-xl p-4 {{ request()->anyFilled(['search', 'status', 'bisnis_unit_id', 'prioritas', 'kategori_id', 'start_date', 'end_date']) ? '' : 'hidden' }}">
        <form method="GET" action="{{ route('help.proses.index') }}" id="filterForm">
            {{-- Grid untuk filter --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                {{-- Search --}}
                <div>
                    <label class="text-sm font-medium text-gray-600">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Judul atau nomor tiket"
                           class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- Status --}}
                <div>
                    <label class="text-sm font-medium text-gray-600">Status</label>
                    <select name="status"
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="OPEN" {{ request('status')=='OPEN'?'selected':'' }}>OPEN</option>
                        <option value="ON_PROCESS" {{ request('status')=='ON_PROCESS'?'selected':'' }}>ON PROCESS</option>
                        <option value="WAITING" {{ request('status')=='WAITING'?'selected':'' }}>WAITING</option>
                        <option value="DONE" {{ request('status')=='DONE'?'selected':'' }}>DONE</option>
                        <option value="CLOSED" {{ request('status')=='CLOSED'?'selected':'' }}>CLOSED</option>
                    </select>
                </div>

                {{-- Bisnis Unit --}}
                <div>
                    <label class="text-sm font-medium text-gray-600">Bisnis Unit</label>
                    <select name="bisnis_unit_id"
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">All Units</option>
                        @foreach($bisnisUnits as $unit)
                            <option value="{{ $unit->id_bisnis_unit }}" {{ request('bisnis_unit_id')==$unit->id_bisnis_unit?'selected':'' }}>
                                {{ $unit->nama_bisnis_unit }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Prioritas --}}
                <div>
                    <label class="text-sm font-medium text-gray-600">Prioritas</label>
                    <select name="prioritas"
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">All Priorities</option>
                        <option value="LOW" {{ request('prioritas')=='LOW'?'selected':'' }}>LOW</option>
                        <option value="MEDIUM" {{ request('prioritas')=='MEDIUM'?'selected':'' }}>MEDIUM</option>
                        <option value="HIGH" {{ request('prioritas')=='HIGH'?'selected':'' }}>HIGH</option>
                        <option value="URGENT" {{ request('prioritas')=='URGENT'?'selected':'' }}>URGENT</option>
                    </select>
                </div>

                {{-- Kategori --}}
                <div>
                    <label class="text-sm font-medium text-gray-600">Kategori</label>
                    <select name="kategori_id"
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        @foreach($kategori as $kat)
                            <option value="{{ $kat->id }}" {{ request('kategori_id')==$kat->id?'selected':'' }}>
                                {{ $kat->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal Mulai --}}
                <div>
                    <label class="text-sm font-medium text-gray-600">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="filter_start_date" 
                           class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                           value="{{ request('start_date') }}">
                </div>

                {{-- Tanggal Akhir --}}
                <div>
                    <label class="text-sm font-medium text-gray-600">Tanggal Akhir</label>
                    <input type="date" name="end_date" id="filter_end_date" 
                           class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                           value="{{ request('end_date') }}">
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row gap-3 justify-end mt-6 pt-4 border-t border-gray-200">
                <button type="button" onclick="openDownloadModal()"
                        class="px-5 py-2.5 bg-green-600 text-white rounded-lg text-sm font-semibold 
                               hover:bg-green-700 transition inline-flex items-center justify-center
                               shadow-sm hover:shadow">
                    <i class="fas fa-download mr-2"></i> Download Report
                </button>
                
                <div class="flex gap-2">
                    <button type="submit"
                            class="px-5 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold 
                                   hover:bg-blue-700 transition inline-flex items-center justify-center
                                   shadow-sm hover:shadow">
                        <i class="fas fa-search mr-2"></i> Apply Filters
                    </button>
                    
                    <a href="{{ route('help.proses.index') }}"
                       class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold 
                              hover:bg-gray-300 transition inline-flex items-center justify-center">
                        <i class="fas fa-redo-alt mr-2"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- MODAL DOWNLOAD REPORT --}}
    <div id="downloadModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Download Report Tiket</h3>
                    <button onclick="closeDownloadModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form action="{{ route('help.proses.download') }}" method="GET" id="downloadForm">
                    {{-- FILTER TERSEMBUNYI (mengikuti filter yang aktif) --}}
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="bisnis_unit_id" value="{{ request('bisnis_unit_id') }}">
                    <input type="hidden" name="prioritas" value="{{ request('prioritas') }}">
                    <input type="hidden" name="kategori_id" value="{{ request('kategori_id') }}">
                    
                    <div class="space-y-4">
                        {{-- INFO FILTER AKTIF --}}
                        @php
                            $hasActiveFilters = request()->anyFilled(['search', 'status', 'bisnis_unit_id', 'prioritas', 'kategori_id']);
                        @endphp
                        
                        @if($hasActiveFilters)
                        <div class="text-xs bg-blue-50 p-3 rounded border border-blue-200">
                            <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                            <span class="font-medium">Filter aktif akan diterapkan:</span>
                            <ul class="mt-2 space-y-1 list-disc list-inside text-gray-600">
                                @if(request('search')) 
                                    <li>Pencarian: "{{ request('search') }}"</li> 
                                @endif
                                @if(request('status')) 
                                    <li>Status: {{ request('status') }}</li> 
                                @endif
                                @if(request('bisnis_unit_id')) 
                                    <li>Bisnis Unit: 
                                        @foreach($bisnisUnits as $unit)
                                            @if($unit->id_bisnis_unit == request('bisnis_unit_id'))
                                                {{ $unit->nama_bisnis_unit }}
                                            @endif
                                        @endforeach
                                    </li>
                                @endif
                                @if(request('prioritas')) 
                                    <li>Prioritas: {{ request('prioritas') }}</li> 
                                @endif
                                @if(request('kategori_id')) 
                                    <li>Kategori: 
                                        @foreach($kategori as $kat)
                                            @if($kat->id == request('kategori_id'))
                                                {{ $kat->nama }}
                                            @endif
                                        @endforeach
                                    </li>
                                @endif
                            </ul>
                        </div>
                        @endif

                        {{-- RENTANG TANGGAL DOWNLOAD --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar-alt mr-1 text-gray-500"></i>
                                Filter Tanggal Download
                            </label>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-500">Tanggal Mulai</label>
                                    <input type="date" name="start_date" id="download_start_date" 
                                           class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                                           value="{{ request('start_date') }}">
                                </div>
                                
                                <div>
                                    <label class="text-xs text-gray-500">Tanggal Akhir</label>
                                    <input type="date" name="end_date" id="download_end_date" 
                                           class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                                           value="{{ request('end_date') }}">
                                </div>
                            </div>
                            
                            <div class="flex items-center mt-3 space-x-2">
                                <input type="checkbox" name="use_filter_dates" id="use_filter_dates" value="1" 
                                       class="rounded text-blue-600 focus:ring-blue-500">
                                <label for="use_filter_dates" class="text-sm text-gray-600">
                                    Gunakan tanggal dari filter halaman
                                    @if(request('start_date') || request('end_date'))
                                        <span class="text-xs text-blue-600 ml-1">
                                            ({{ request('start_date') ?: 'kosong' }} - {{ request('end_date') ?: 'kosong' }})
                                        </span>
                                    @endif
                                </label>
                            </div>
                            
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Kosongkan tanggal jika ingin semua data (tanpa filter tanggal)
                            </p>
                        </div>

                        {{-- OPSI DOWNLOAD --}}
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" name="ignore_filters" id="ignore_filters" value="1" class="rounded text-blue-600">
                                <span class="text-sm text-gray-700">Download semua data (abaikan semua filter)</span>
                            </label>
                        </div>
                        
                        <div class="text-xs text-gray-500 bg-yellow-50 p-3 rounded border border-yellow-200">
                            <i class="fas fa-exclamation-triangle mr-1 text-yellow-600"></i>
                            File akan didownload dalam format CSV dan dapat dibuka dengan Microsoft Excel.
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" onclick="closeDownloadModal()"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300">
                            Batal
                        </button>
                        <button type="submit" id="downloadBtn"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">
                            <i class="fas fa-download mr-1"></i> Download CSV
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ALERTS --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center">
        <i class="fas fa-check-circle text-green-500 mr-3"></i>
        <span class="text-sm text-green-700">{{ session('success') }}</span>
        <button class="ml-auto text-green-500 hover:text-green-700" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex items-center">
        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
        <span class="text-sm text-red-700">{{ session('error') }}</span>
        <button class="ml-auto text-red-500 hover:text-red-700" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    @endif

    {{-- TABLE --}}
    @if($tiket->isEmpty())
    <div class="bg-white border rounded-xl p-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-50 rounded-full mb-4">
            <i class="fas fa-check-circle text-2xl text-blue-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-700 mb-2">Tidak ada tiket yang perlu diproses</h3>
        <p class="text-gray-500">Semua tiket sudah ditangani atau belum ada tiket yang dibuat.</p>
    </div>
    @else
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">No. Tiket</th>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Pelapor</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($tiket as $item)
                <tr class="hover:bg-gray-50">
                    {{-- No. Tiket --}}
                    <td class="px-4 py-3">
                        <div class="text-blue-600 font-medium">{{ $item->nomor_tiket }}</div>
                        <div class="text-xs text-gray-500">{{ $item->created_at->format('d M Y H:i') }}</div>
                    </td>

                    {{-- Judul --}}
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ Str::limit($item->judul, 25) }}</div>
                    </td>

                    {{-- Pelapor (Desktop) --}}
                    <td class="px-4 py-3 hidden md:table-cell">
                        <div class="flex items-center">
                            @php
                                $pelaporName = "ID: {$item->pelapor_id}";
                                $pelaporInitial = "P";
                                
                                if ($item->pelapor) {
                                    if ($item->pelapor->user && $item->pelapor->user->name) {
                                        $pelaporName = $item->pelapor->user->name;
                                    } else {
                                        $pelaporName = $item->pelapor->nama ?? "Pelanggan";
                                    }
                                    $pelaporInitial = substr($pelaporName, 0, 1);
                                }
                            @endphp
                            
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-xs font-medium text-blue-700 mr-2">
                                {{ $pelaporInitial }}
                            </div>
                            <span title="{{ $pelaporName }}">
                                {{ Str::limit($pelaporName, 15) }}
                            </span>
                        </div>
                        
                        {{-- Bisnis Unit di bawah Pelapor --}}
                        <div class="text-xs text-purple-600 pl-8 mt-1">
                            @if($item->bisnisUnit)
                                <i class="fas fa-building mr-1"></i>{{ $item->bisnisUnit->nama_bisnis_unit }}
                            @else
                                <span class="text-gray-400 italic">-</span>
                            @endif
                        </div>
                    </td>

                    {{-- Kategori (Desktop) --}}
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                            {{ $item->kategori->nama ?? '-' }}
                        </span>
                        
                        {{-- Prioritas di bawah Kategori --}}
                        <div class="mt-1">
                            @php
                                $priorityColors = [
                                    'URGENT' => 'bg-red-100 text-red-800',
                                    'HIGH' => 'bg-orange-100 text-orange-800',
                                    'MEDIUM' => 'bg-blue-100 text-blue-800',
                                    'LOW' => 'bg-gray-100 text-gray-800'
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $priorityColors[$item->prioritas] }}">
                                {{ $item->prioritas }}
                            </span>
                        </div>
                    </td>

                    {{-- Status --}}
                    <td class="px-4 py-3">
                        @php
                            $statusColors = [
                                'OPEN' => 'bg-yellow-100 text-yellow-800',
                                'ON_PROCESS' => 'bg-blue-100 text-blue-800',
                                'WAITING' => 'bg-orange-100 text-orange-800',
                                'DONE' => 'bg-green-100 text-green-800',
                                'CLOSED' => 'bg-gray-100 text-gray-800'
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$item->status] }}">
                            {{ $item->status }}
                        </span>
                        
                        {{-- Info Mobile: Bisnis Unit, Kategori --}}
                        <div class="mt-1 md:hidden">
                            @if($item->bisnisUnit)
                            <div class="text-xs text-purple-600">
                                <i class="fas fa-building mr-1"></i>{{ Str::limit($item->bisnisUnit->nama_bisnis_unit, 15) }}
                            </div>
                            @endif
                            @if($item->kategori)
                            <div class="text-xs text-gray-500 mt-1">
                                {{ Str::limit($item->kategori->nama, 15) }}
                            </div>
                            @endif
                        </div>
                    </td>

                    {{-- Action --}}
                    <td class="px-4 py-3">
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('help.proses.show', $item) }}"
                               class="text-blue-600 font-semibold hover:underline text-sm flex items-center">
                                <i class="fas fa-eye mr-1"></i> Detail
                            </a>
                            
                            @if($item->status === 'OPEN')
                            <form action="{{ route('help.proses.take', $item) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Ambil tiket ini untuk diproses?')"
                                        class="text-green-600 font-semibold hover:underline text-sm flex items-center">
                                    <i class="fas fa-hand-paper mr-1"></i> Ambil
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($tiket->hasPages())
        <div class="mt-4">
            {{ $tiket->appends(request()->query())->links() }}
        </div>
    @endif
    @endif
</div>

{{-- SCRIPT --}}
<script>
// Toggle filter section
document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden')
})

// Modal functions
function openDownloadModal() {
    const modal = document.getElementById('downloadModal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Reset form state
    resetDownloadModal();
}

function closeDownloadModal() {
    const modal = document.getElementById('downloadModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('downloadModal');
    if (event.target == modal) {
        closeDownloadModal();
    }
}

// Auto hide alerts after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.bg-green-50, .bg-red-50').forEach(alert => {
        alert.style.transition = 'opacity 0.3s ease-out';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    });
}, 5000);

// Validasi tanggal filter di halaman utama
document.addEventListener('DOMContentLoaded', function() {
    const filterStartDate = document.getElementById('filter_start_date');
    const filterEndDate = document.getElementById('filter_end_date');
    
    if (filterStartDate && filterEndDate) {
        filterStartDate.addEventListener('change', function() {
            if (filterEndDate.value && this.value > filterEndDate.value) {
                alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir!');
                this.value = '';
            }
        });
        
        filterEndDate.addEventListener('change', function() {
            if (filterStartDate.value && this.value < filterStartDate.value) {
                alert('Tanggal akhir tidak boleh lebih kecil dari tanggal mulai!');
                this.value = '';
            }
        });
    }
});

// Fungsi untuk reset modal download
function resetDownloadModal() {
    const downloadStartDate = document.getElementById('download_start_date');
    const downloadEndDate = document.getElementById('download_end_date');
    const useFilterDates = document.getElementById('use_filter_dates');
    const ignoreFilters = document.getElementById('ignore_filters');
    const filterStartDate = document.getElementById('filter_start_date');
    const filterEndDate = document.getElementById('filter_end_date');
    
    // Reset nilai ke filter yang aktif
    if (downloadStartDate) downloadStartDate.value = filterStartDate?.value || '';
    if (downloadEndDate) downloadEndDate.value = filterEndDate?.value || '';
    
    // Uncheck semua checkbox
    if (useFilterDates) useFilterDates.checked = false;
    if (ignoreFilters) ignoreFilters.checked = false;
    
    // Enable semua input
    if (downloadStartDate) {
        downloadStartDate.disabled = false;
        downloadStartDate.classList.remove('bg-gray-100');
    }
    if (downloadEndDate) {
        downloadEndDate.disabled = false;
        downloadEndDate.classList.remove('bg-gray-100');
    }
    if (useFilterDates) useFilterDates.disabled = false;
}

// Validasi dan interaktivitas modal download
document.addEventListener('DOMContentLoaded', function() {
    const downloadStartDate = document.getElementById('download_start_date');
    const downloadEndDate = document.getElementById('download_end_date');
    const useFilterDates = document.getElementById('use_filter_dates');
    const ignoreFilters = document.getElementById('ignore_filters');
    const filterStartDate = document.getElementById('filter_start_date');
    const filterEndDate = document.getElementById('filter_end_date');
    
    // Validasi tanggal di modal download
    if (downloadStartDate && downloadEndDate) {
        downloadStartDate.addEventListener('change', function() {
            if (downloadEndDate.value && this.value > downloadEndDate.value) {
                alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir!');
                this.value = '';
            }
        });
        
        downloadEndDate.addEventListener('change', function() {
            if (downloadStartDate.value && this.value < downloadStartDate.value) {
                alert('Tanggal akhir tidak boleh lebih kecil dari tanggal mulai!');
                this.value = '';
            }
        });
    }
    
    // Handle "use filter dates" checkbox
    if (useFilterDates && filterStartDate && filterEndDate) {
        useFilterDates.addEventListener('change', function() {
            if (this.checked) {
                // Gunakan tanggal dari filter halaman
                downloadStartDate.value = filterStartDate.value || '';
                downloadEndDate.value = filterEndDate.value || '';
                
                // Validasi tanggal
                if (filterStartDate.value && filterEndDate.value) {
                    if (filterStartDate.value > filterEndDate.value) {
                        alert('Filter tanggal di halaman utama tidak valid. Silakan perbaiki terlebih dahulu.');
                        this.checked = false;
                        return;
                    }
                }
                
                // Disable input tanggal
                downloadStartDate.disabled = true;
                downloadEndDate.disabled = true;
                downloadStartDate.classList.add('bg-gray-100');
                downloadEndDate.classList.add('bg-gray-100');
            } else {
                // Enable input tanggal
                downloadStartDate.disabled = false;
                downloadEndDate.disabled = false;
                downloadStartDate.classList.remove('bg-gray-100');
                downloadEndDate.classList.remove('bg-gray-100');
            }
        });
    }
    
    // Handle "ignore filters" checkbox
    if (ignoreFilters) {
        ignoreFilters.addEventListener('change', function() {
            if (this.checked) {
                // Disable semua kontrol tanggal
                if (downloadStartDate) {
                    downloadStartDate.disabled = true;
                    downloadStartDate.classList.add('bg-gray-100');
                }
                if (downloadEndDate) {
                    downloadEndDate.disabled = true;
                    downloadEndDate.classList.add('bg-gray-100');
                }
                if (useFilterDates) {
                    useFilterDates.disabled = true;
                    useFilterDates.checked = false;
                }
                
                // Kosongkan nilai tanggal
                if (downloadStartDate) downloadStartDate.value = '';
                if (downloadEndDate) downloadEndDate.value = '';
            } else {
                // Enable kembali berdasarkan kondisi useFilterDates
                if (useFilterDates && useFilterDates.checked) {
                    if (downloadStartDate) {
                        downloadStartDate.disabled = true;
                        downloadStartDate.classList.add('bg-gray-100');
                    }
                    if (downloadEndDate) {
                        downloadEndDate.disabled = true;
                        downloadEndDate.classList.add('bg-gray-100');
                    }
                } else {
                    if (downloadStartDate) {
                        downloadStartDate.disabled = false;
                        downloadStartDate.classList.remove('bg-gray-100');
                    }
                    if (downloadEndDate) {
                        downloadEndDate.disabled = false;
                        downloadEndDate.classList.remove('bg-gray-100');
                    }
                }
                if (useFilterDates) {
                    useFilterDates.disabled = false;
                }
            }
        });
    }
    
    // Submit form download dengan validasi
    const downloadForm = document.getElementById('downloadForm');
    if (downloadForm) {
        downloadForm.addEventListener('submit', function(e) {
            const ignoreFiltersCheck = document.getElementById('ignore_filters');
            const downloadStart = document.getElementById('download_start_date');
            const downloadEnd = document.getElementById('download_end_date');
            
            // Jika ignore filters dicentang, pastikan tidak ada parameter filter yang dikirim
            if (ignoreFiltersCheck && ignoreFiltersCheck.checked) {
                // Hapus semua input hidden filter
                const hiddenInputs = this.querySelectorAll('input[type="hidden"]');
                hiddenInputs.forEach(input => {
                    if (input.name !== '_token' && input.name !== '_method') {
                        input.remove();
                    }
                });
            }
            
            // Validasi tanggal jika diisi
            if (downloadStart.value && downloadEnd.value) {
                if (downloadStart.value > downloadEnd.value) {
                    e.preventDefault();
                    alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir!');
                    return false;
                }
            }
        });
    }
});

// Fungsi untuk memastikan filter section tampil jika ada parameter
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const hasParams = Array.from(urlParams.keys()).length > 0;
    
    if (hasParams) {
        const filterSection = document.getElementById('filterSection');
        if (filterSection) {
            filterSection.classList.remove('hidden');
        }
    }
});
</script>

{{-- STYLE UNTUK MODAL --}}
<style>
#downloadModal {
    display: none;
    z-index: 9999;
}

#downloadModal.active {
    display: block;
}

/* Animasi modal */
#downloadModal .relative {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Pastikan tombol tidak berubah styling */
button, .btn {
    transition: all 0.2s ease;
}

button:focus, .btn:focus {
    outline: none;
    ring: 2px;
    ring-color: #3b82f6;
}

/* Styling untuk input yang di-disable */
input:disabled, select:disabled {
    background-color: #f3f4f6;
    cursor: not-allowed;
    opacity: 0.7;
}

/* Styling untuk modal background */
.fixed.inset-0.bg-gray-600.bg-opacity-50 {
    backdrop-filter: blur(2px);
}
</style>
@endsection