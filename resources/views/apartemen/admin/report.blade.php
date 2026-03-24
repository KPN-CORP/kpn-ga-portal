@extends('layouts.app_apartadmin_sidebar')
@section('content')
<div class="p-4 md:p-6">

    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Laporan Apartemen</h1>
                <p class="text-gray-500 text-sm mt-1">Riwayat dan statistik sistem apartemen</p>
            </div>
            <button onclick="exportReport()" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Excel
            </button>
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Laporan</h3>
            <form method="GET" action="{{ route('apartemen.admin.report') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="SELESAI" {{ request('status') == 'SELESAI' ? 'selected' : '' }}>Selesai</option>
                            <option value="DIPINDAH" {{ request('status') == 'DIPINDAH' ? 'selected' : '' }}>Dipindah</option>
                            <option value="DIBATALKAN" {{ request('status') == 'DIBATALKAN' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <div class="flex space-x-2 w-full">
                            <button type="submit" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Terapkan Filter
                            </button>
                            <a href="{{ route('apartemen.admin.report') }}" 
                               class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- STATISTICS --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-center">
                <p class="text-sm text-gray-500">Total Riwayat</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $histories->total() }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-center">
                <p class="text-sm text-gray-500">Selesai</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">
                    {{ $histories->where('status_selesai', 'SELESAI')->count() }}
                </p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-center">
                <p class="text-sm text-gray-500">Dipindah</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">
                    {{ $histories->where('status_selesai', 'DIPINDAH')->count() }}
                </p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-center">
                <p class="text-sm text-gray-500">Dibatalkan</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">
                    {{ $histories->where('status_selesai', 'DIBATALKAN')->count() }}
                </p>
            </div>
        </div>
    </div>

    {{-- REPORT TABLE --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Riwayat Apartemen</h3>
                <div class="text-sm text-gray-500">
                    Menampilkan {{ $histories->firstItem() }} - {{ $histories->lastItem() }} dari {{ $histories->total() }} data
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Penghuni</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Karyawan</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Kerja</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Apartemen & Unit</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($histories as $history)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <div class="font-medium text-gray-900">{{ $history->nama }}</div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="text-gray-900">{{ $history->id_karyawan }}</div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="text-gray-900">{{ $history->unit_kerja ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $history->gol ?? '-' }}</div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-medium text-gray-900">{{ $history->apartemen }}</div>
                            <div class="text-sm text-gray-500">{{ $history->unit }}</div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="text-gray-900">{{ $history->periode }}</div>
                        </td>
                        <td class="py-3 px-4">
                            @php
                                $statusColors = [
                                    'SELESAI' => 'bg-green-100 text-green-800',
                                    'DIPINDAH' => 'bg-yellow-100 text-yellow-800',
                                    'DIBATALKAN' => 'bg-red-100 text-red-800'
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$history->status_selesai] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $history->status_selesai_text }}
                            </span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $history->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $history->created_at->format('H:i') }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-lg font-medium">Tidak ada data riwayat</p>
                                <p class="text-sm mt-1">Data riwayat akan muncul di sini</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($histories->hasPages())
        <div class="px-6 py-4 border-t">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    Menampilkan {{ $histories->firstItem() }} - {{ $histories->lastItem() }} dari {{ $histories->total() }} data
                </div>
                <div class="flex space-x-2">
                    @if($histories->previousPageUrl())
                        <a href="{{ $histories->previousPageUrl() }}" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">
                            Previous
                        </a>
                    @endif
                    
                    @if($histories->nextPageUrl())
                        <a href="{{ $histories->nextPageUrl() }}" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">
                            Next
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

</div>

<script>
function exportReport() {
    // Collect filter parameters
    const params = new URLSearchParams({
        tanggal_mulai: document.querySelector('input[name="tanggal_mulai"]').value || '',
        tanggal_selesai: document.querySelector('input[name="tanggal_selesai"]').value || '',
        status: document.querySelector('select[name="status"]').value || '',
        export: 'excel'
    });

    // Show loading
    const originalText = event.target.innerHTML;
    event.target.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>Exporting...';
    event.target.disabled = true;

    // Make export request
    fetch(`{{ route('apartemen.admin.report') }}?${params.toString()}`)
        .then(response => {
            if (!response.ok) throw new Error('Export failed');
            return response.blob();
        })
        .then(blob => {
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `laporan-apartemen-${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            // Restore button
            event.target.innerHTML = originalText;
            event.target.disabled = false;
        })
        .catch(error => {
            console.error('Export error:', error);
            alert('Gagal melakukan export. Silakan coba lagi.');
            
            // Restore button
            event.target.innerHTML = originalText;
            event.target.disabled = false;
        });
}

// Set default date range to last month
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const lastMonth = new Date();
    lastMonth.setMonth(today.getMonth() - 1);
    
    const tanggalMulai = document.querySelector('input[name="tanggal_mulai"]');
    const tanggalSelesai = document.querySelector('input[name="tanggal_selesai"]');
    
    if (tanggalMulai && !tanggalMulai.value) {
        tanggalMulai.value = lastMonth.toISOString().split('T')[0];
    }
    
    if (tanggalSelesai && !tanggalSelesai.value) {
        tanggalSelesai.value = today.toISOString().split('T')[0];
    }
});
</script>
@endsection