@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <h1 class="text-2xl font-bold">📄 Dokumen Kendaraan</h1>
        <a href="{{ route('drms.vehicle-documents.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
            <span>+</span> Tambah Dokumen
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    {{-- FILTER --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <form method="GET" action="{{ route('drms.vehicle-documents.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🔍 Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Cari kendaraan..." 
                       class="w-full border rounded-lg px-3 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🚗 Kendaraan</label>
                @include('drms.partials.vehicle-search', [
                    'vehicles' => $vehicles,
                    'name' => 'vehicle_id',
                    'selectedId' => request('vehicle_id'),
                    'placeholder' => 'Cari plat nomor...',
                    'required' => false,
                    'allowAll' => true,
                    'uid' => 'vehicle_documents_filter_vehicle',
                ])
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📌 Status Kadaluarsa</label>
                <select name="expiry_status" class="w-full border rounded-lg px-3 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="expired" {{ request('expiry_status') == 'expired' ? 'selected' : '' }}>🔴 Sudah Kadaluarsa</option>
                    <option value="h30" {{ request('expiry_status') == 'h30' ? 'selected' : '' }}>🟡 H-30 Mendekati Kadaluarsa</option>
                </select>
            </div>
            <div class="flex gap-2 items-end">
                <button type="submit" class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition">
                    🔍 Tampilkan
                </button>
                @if(request()->anyFilled(['search', 'vehicle_id', 'expiry_status']))
                    <a href="{{ route('drms.vehicle-documents.index') }}" class="flex-1 sm:flex-none text-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- QUICK STATS --}}
    @php
        $total = $documents->count();
        $today = now()->format('Y-m-d');
        $h30 = now()->addDays(30)->format('Y-m-d');
        
        $expired = $documents->filter(function($doc) use ($today) {
            return ($doc->stnk_expiry && $doc->stnk_expiry < $today) ||
                   ($doc->tax_yearly_expiry && $doc->tax_yearly_expiry < $today) ||
                   ($doc->tax_5year_expiry && $doc->tax_5year_expiry < $today) ||
                   ($doc->insurance_expiry && $doc->insurance_expiry < $today);
        })->count();
        
        $expiringSoon = $documents->filter(function($doc) use ($today, $h30) {
            return ($doc->stnk_expiry && $doc->stnk_expiry >= $today && $doc->stnk_expiry <= $h30) ||
                   ($doc->tax_yearly_expiry && $doc->tax_yearly_expiry >= $today && $doc->tax_yearly_expiry <= $h30) ||
                   ($doc->tax_5year_expiry && $doc->tax_5year_expiry >= $today && $doc->tax_5year_expiry <= $h30) ||
                   ($doc->insurance_expiry && $doc->insurance_expiry >= $today && $doc->insurance_expiry <= $h30);
        })->count();
        
        $complete = $documents->filter(function($doc) {
            return $doc->stnk_file && $doc->tax_file && $doc->insurance_file;
        })->count();
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Total Dokumen</p>
            <p class="text-2xl font-bold">{{ $total }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">Lengkap</p>
            <p class="text-2xl font-bold text-green-600">{{ $complete }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase">🔴 Kadaluarsa</p>
            <p class="text-2xl font-bold text-red-600">{{ $expired }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">🟡 H-30</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $expiringSoon }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 uppercase">Kendaraan</p>
            <p class="text-2xl font-bold text-purple-600">{{ $documents->pluck('vehicle_id')->unique()->count() }}</p>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendaraan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">STNK</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pajak Tahunan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pajak 5 Tahun</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asuransi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($documents as $doc)
                    @php
                        $today = now()->format('Y-m-d');
                        $status = '✅ Valid';
                        $statusColor = 'text-green-600';
                        $statusBg = 'bg-green-100 text-green-800';
                        
                        if ($doc->stnk_expiry && $doc->stnk_expiry < $today) {
                            $status = '🔴 STNK Kadaluarsa';
                            $statusColor = 'text-red-600';
                            $statusBg = 'bg-red-100 text-red-800';
                        } elseif ($doc->tax_yearly_expiry && $doc->tax_yearly_expiry < $today) {
                            $status = '🔴 Pajak Kadaluarsa';
                            $statusColor = 'text-red-600';
                            $statusBg = 'bg-red-100 text-red-800';
                        } elseif ($doc->tax_5year_expiry && $doc->tax_5year_expiry < $today) {
                            $status = '🔴 Pajak 5 Tahun Kadaluarsa';
                            $statusColor = 'text-red-600';
                            $statusBg = 'bg-red-100 text-red-800';
                        } elseif ($doc->insurance_expiry && $doc->insurance_expiry < $today) {
                            $status = '🔴 Asuransi Kadaluarsa';
                            $statusColor = 'text-red-600';
                            $statusBg = 'bg-red-100 text-red-800';
                        } elseif ($doc->stnk_expiry && $doc->stnk_expiry <= now()->addDays(30)->format('Y-m-d')) {
                            $status = '🟡 H-30 STNK';
                            $statusColor = 'text-yellow-600';
                            $statusBg = 'bg-yellow-100 text-yellow-800';
                        } elseif ($doc->tax_yearly_expiry && $doc->tax_yearly_expiry <= now()->addDays(30)->format('Y-m-d')) {
                            $status = '🟡 H-30 Pajak';
                            $statusColor = 'text-yellow-600';
                            $statusBg = 'bg-yellow-100 text-yellow-800';
                        } elseif ($doc->insurance_expiry && $doc->insurance_expiry <= now()->addDays(30)->format('Y-m-d')) {
                            $status = '🟡 H-30 Asuransi';
                            $statusColor = 'text-yellow-600';
                            $statusBg = 'bg-yellow-100 text-yellow-800';
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="font-medium">{{ $doc->vehicle->plate_number }}</span>
                            <span class="text-xs text-gray-400 block">{{ $doc->vehicle->type }}</span>
                        </td>
                        <td class="px-6 py-4">
                            {{ $doc->stnk_expiry ? \Carbon\Carbon::parse($doc->stnk_expiry)->format('d M Y') : '-' }}
                            @if($doc->stnk_file) 
                                <a href="{{ route('drms.private.image', $doc->stnk_file) }}" target="_blank" class="text-blue-600 text-xs">📎</a> 
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            {{ $doc->tax_yearly_expiry ? \Carbon\Carbon::parse($doc->tax_yearly_expiry)->format('d M Y') : '-' }}
                            @if($doc->tax_file) 
                                <a href="{{ route('drms.private.image', $doc->tax_file) }}" target="_blank" class="text-blue-600 text-xs">📎</a> 
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $doc->tax_5year_expiry ? \Carbon\Carbon::parse($doc->tax_5year_expiry)->format('d M Y') : '-' }}</td>
                        <td class="px-6 py-4">
                            {{ $doc->insurance_expiry ? \Carbon\Carbon::parse($doc->insurance_expiry)->format('d M Y') : '-' }}
                            @if($doc->insurance_file) 
                                <a href="{{ route('drms.private.image', $doc->insurance_file) }}" target="_blank" class="text-blue-600 text-xs">📎</a> 
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusBg }}">
                                {{ $status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('drms.vehicle-documents.show', $doc->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">Detail</a>
                            <a href="{{ route('drms.vehicle-documents.edit', $doc->id) }}" class="text-green-600 hover:text-green-800 text-sm">Edit</a>
                            <form action="{{ route('drms.vehicle-documents.destroy', $doc->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                            <div class="text-4xl mb-2">📄</div>
                            <p>Belum ada dokumen.</p>
                            @if(request()->anyFilled(['search', 'vehicle_id', 'expiry_status']))
                                <p class="text-sm mt-1">Coba ubah filter pencarian.</p>
                            @endif
                            <a href="{{ route('drms.vehicle-documents.create') }}" class="mt-2 inline-block text-blue-600 hover:underline">+ Tambah Dokumen</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection