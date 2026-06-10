@extends('layouts.app_apartadmin_sidebar')
@section('content')
<div class="p-3 md:p-6">

    {{-- HEADER --}}
    <div class="mb-4 md:mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">Laporan Apartemen</h1>
                <p class="text-xs md:text-sm text-gray-500 mt-0.5 md:mt-1">Riwayat dan statistik sistem apartemen</p>
            </div>
            <a href="{{ route('apartemen.admin.report.export', request()->query()) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 md:px-4 md:py-2 rounded-lg text-xs md:text-sm font-medium flex items-center justify-center w-full md:w-auto">
                <svg class="w-3 h-3 md:w-4 md:h-4 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Excel
            </a>
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div class="bg-white rounded-lg md:rounded-xl border border-gray-200 shadow-sm mb-4 md:mb-6">
        <div class="p-3 md:p-5">
            <h3 class="text-base md:text-lg font-semibold text-gray-800 mb-3 md:mb-4">Filter Laporan</h3>
            <form method="GET" action="{{ route('apartemen.admin.report') }}" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}"
                               class="w-full border border-gray-300 rounded-md md:rounded-lg px-2 py-1.5 md:px-3 md:py-2 text-xs md:text-sm">
                    </div>
                    <div>
                        <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}"
                               class="w-full border border-gray-300 rounded-md md:rounded-lg px-2 py-1.5 md:px-3 md:py-2 text-xs md:text-sm">
                    </div>
                    <div>
                        <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full border border-gray-300 rounded-md md:rounded-lg px-2 py-1.5 md:px-3 md:py-2 text-xs md:text-sm">
                            <option value="">Semua Status</option>
                            <option value="SELESAI" {{ request('status') == 'SELESAI' ? 'selected' : '' }}>Selesai</option>
                            <option value="AKTIF" {{ request('status') == 'AKTIF' ? 'selected' : '' }}>Aktif (menginap)</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <div class="flex space-x-2 w-full">
                            <button type="submit" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1.5 md:px-4 md:py-2 rounded-md md:rounded-lg text-xs md:text-sm font-medium">
                                Terapkan Filter
                            </button>
                            <a href="{{ route('apartemen.admin.report') }}" 
                               class="px-2 py-1.5 md:px-4 md:py-2 border border-gray-300 rounded-md md:rounded-lg text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- STATISTICS --}}
    <div class="grid grid-cols-3 gap-2 md:gap-4 mb-4 md:mb-6">
        <div class="bg-white rounded-lg md:rounded-xl border border-gray-200 p-2 md:p-4 shadow-sm text-center">
            <p class="text-[10px] md:text-sm text-gray-500">Total Penempatan</p>
            <p class="text-lg md:text-2xl font-bold text-gray-800">{{ $histories->total() }}</p>
        </div>
        <div class="bg-white rounded-lg md:rounded-xl border border-gray-200 p-2 md:p-4 shadow-sm text-center">
            <p class="text-[10px] md:text-sm text-gray-500">Selesai</p>
            <p class="text-lg md:text-2xl font-bold text-gray-800">
                {{ $histories->filter(fn($h) => $h->assign && $h->assign->status == 'SELESAI')->count() }}
            </p>
        </div>
        <div class="bg-white rounded-lg md:rounded-xl border border-gray-200 p-2 md:p-4 shadow-sm text-center">
            <p class="text-[10px] md:text-sm text-gray-500">Aktif (menginap)</p>
            <p class="text-lg md:text-2xl font-bold text-gray-800">
                {{ $histories->filter(function($h) {
                    $assign = $h->assign;
                    return $assign && $assign->status == 'AKTIF' && $assign->tanggal_mulai <= now() && $assign->tanggal_selesai >= now();
                })->count() }}
            </p>
        </div>
    </div>

    {{-- REPORT CONTENT: TABLET/DESKTOP (TABEL) vs MOBILE (CARD) --}}
    <div class="bg-white rounded-lg md:rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-3 md:px-6 py-3 md:py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h3 class="text-base md:text-lg font-semibold text-gray-800">Riwayat Apartemen</h3>
                <div class="text-xs md:text-sm text-gray-500">
                    Menampilkan {{ $histories->firstItem() }} - {{ $histories->lastItem() }} dari {{ $histories->total() }} data
                </div>
            </div>
        </div>

        {{-- TABEL untuk layar >= 768px (tablet/desktop) --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-3 text-left text-xs font-medium text-gray-500 uppercase">Request</th>
                        <th class="py-3 px-3 text-left text-xs font-medium text-gray-500 uppercase">Penghuni</th>
                        <th class="py-3 px-3 text-left text-xs font-medium text-gray-500 uppercase">ID Karyawan</th>
                        <th class="py-3 px-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Kerja</th>
                        <th class="py-3 px-3 text-left text-xs font-medium text-gray-500 uppercase">Gol</th>
                        <th class="py-3 px-3 text-left text-xs font-medium text-gray-500 uppercase">Apartemen</th>
                        <th class="py-3 px-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                        <th class="py-3 px-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                        <th class="py-3 px-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-3 px-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($histories as $penghuni)
                    @php
                        $assign = $penghuni->assign;
                        if (!$assign) continue;
                        $unit = $assign->unit;
                        $apartemen = $unit->apartemen ?? null;
                        $pemohon = $assign->request && $assign->request->user ? $assign->request->user->name : '-';
                        $now = now();
                        if ($assign->status == 'SELESAI') {
                            $statusText = 'Selesai';
                            $statusColor = 'green';
                        } elseif ($assign->status == 'AKTIF') {
                            if ($assign->tanggal_mulai <= $now && $assign->tanggal_selesai >= $now) {
                                $statusText = 'Aktif (menginap)';
                                $statusColor = 'green';
                            } elseif ($assign->tanggal_mulai > $now) {
                                $statusText = 'Belum aktif';
                                $statusColor = 'yellow';
                            } else {
                                $statusText = 'Belum check-out';
                                $statusColor = 'red';
                            }
                        } else {
                            $statusText = $assign->status;
                            $statusColor = 'gray';
                        }
                        $periode = $assign->tanggal_mulai->format('d/m/Y') . ' - ' . $assign->tanggal_selesai->format('d/m/Y');
                        $tanggal = $assign->created_at ? $assign->created_at->format('d/m/Y H:i') : '-';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 whitespace-nowrap">{{ $pemohon }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $penghuni->nama }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $penghuni->id_karyawan }}</td>
                        <td class="py-2 px-3">{{ $penghuni->unit_kerja ?? '-' }}</td>
                        <td class="py-2 px-3">{{ $penghuni->gol ?? '-' }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $apartemen->nama_apartemen ?? '-' }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $unit->nomor_unit ?? '-' }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $periode }}</td>
                        <td class="py-2 px-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">{{ $statusText }}</span>
                        </td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $tanggal }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- CARD untuk layar < 768px (mobile) --}}
        <div class="md:hidden divide-y divide-gray-200">
            @forelse($histories as $penghuni)
            @php
                $assign = $penghuni->assign;
                if (!$assign) continue;
                $unit = $assign->unit;
                $apartemen = $unit->apartemen ?? null;
                $pemohon = $assign->request && $assign->request->user ? $assign->request->user->name : '-';
                $now = now();
                if ($assign->status == 'SELESAI') {
                    $statusText = 'Selesai';
                    $statusColor = 'green';
                } elseif ($assign->status == 'AKTIF') {
                    if ($assign->tanggal_mulai <= $now && $assign->tanggal_selesai >= $now) {
                        $statusText = 'Aktif (menginap)';
                        $statusColor = 'green';
                    } elseif ($assign->tanggal_mulai > $now) {
                        $statusText = 'Belum aktif';
                        $statusColor = 'yellow';
                    } else {
                        $statusText = 'Belum check-out';
                        $statusColor = 'red';
                    }
                } else {
                    $statusText = $assign->status;
                    $statusColor = 'gray';
                }
                $periode = $assign->tanggal_mulai->format('d/m/Y') . ' - ' . $assign->tanggal_selesai->format('d/m/Y');
                $tanggal = $assign->created_at ? $assign->created_at->format('d/m/Y H:i') : '-';
            @endphp
            <div class="p-3 bg-white hover:bg-gray-50 transition">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <span class="font-semibold text-gray-800 text-sm">{{ $penghuni->nama }}</span>
                        <div class="text-xs text-gray-500">{{ $penghuni->id_karyawan }}</div>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">{{ $statusText }}</span>
                </div>
                <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs mt-2">
                    <div><span class="text-gray-500">Request:</span> {{ $pemohon }}</div>
                    <div><span class="text-gray-500">Unit Kerja:</span> {{ $penghuni->unit_kerja ?? '-' }}</div>
                    <div><span class="text-gray-500">Gol:</span> {{ $penghuni->gol ?? '-' }}</div>
                    <div><span class="text-gray-500">Apartemen:</span> {{ $apartemen->nama_apartemen ?? '-' }}</div>
                    <div><span class="text-gray-500">Unit:</span> {{ $unit->nomor_unit ?? '-' }}</div>
                    <div><span class="text-gray-500">Periode:</span> {{ $periode }}</div>
                    <div><span class="text-gray-500">Tanggal:</span> {{ $tanggal }}</div>
                </div>
            </div>
            @empty
            <div class="p-6 text-center text-gray-500 text-sm">Tidak ada data penempatan.</div>
            @endforelse
        </div>

        {{-- PAGINATION --}}
        @if($histories->hasPages())
        <div class="px-3 md:px-6 py-3 md:py-4 border-t border-gray-200">
            {{ $histories->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const lastMonth = new Date();
        lastMonth.setMonth(today.getMonth() - 1);
        
        const tglMulai = document.querySelector('input[name="tanggal_mulai"]');
        const tglSelesai = document.querySelector('input[name="tanggal_selesai"]');
        
        if (tglMulai && !tglMulai.value) {
            tglMulai.value = lastMonth.toISOString().split('T')[0];
        }
        if (tglSelesai && !tglSelesai.value) {
            tglSelesai.value = today.toISOString().split('T')[0];
        }
    });
</script>
@endsection