@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold">🔧 Service Kendaraan</h1>
            <p class="text-gray-600 text-sm">Kelola riwayat perawatan dan service kendaraan</p>
        </div>
        <button onclick="document.getElementById('serviceModal').classList.remove('hidden')" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition flex items-center gap-2">
            ➕ Tambah Service
        </button>
    </div>

    {{-- Filter & Pencarian --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('drms.admin.vehicle.services') }}" class="space-y-4">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari kendaraan, deskripsi..." 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" 
                           class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" 
                           class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">🔍 Filter</button>
                    @if(request()->anyFilled(['search', 'date_from', 'date_to']))
                        <a href="{{ route('drms.admin.vehicle.services') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition">Reset</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Quick Stats --}}
    @php
        $totalServices = $services->total();
        $totalCost = $services->sum('cost');
        $avgCost = $services->avg('cost');
        $uniqueVehicles = $services->pluck('vehicle_id')->unique()->count();
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Total Service</p>
            <p class="text-2xl font-bold">{{ $totalServices }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">Total Biaya</p>
            <p class="text-2xl font-bold text-green-600">Rp {{ number_format($totalCost, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 uppercase">Rata-rata Biaya</p>
            <p class="text-2xl font-bold text-purple-600">Rp {{ number_format($avgCost, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">Kendaraan Unik</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $uniqueVehicles }}</p>
        </div>
    </div>

    {{-- Daftar Service dalam Kartu --}}
    <div class="space-y-4">
        @forelse($services as $service)
            @php
                $vehicle = $service->vehicle;
            @endphp
            <div class="bg-white rounded-lg shadow hover:shadow-md transition border border-gray-200">
                <div class="px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="font-semibold text-lg">{{ $vehicle->plate_number ?? 'Kendaraan Dihapus' }}</span>
                            <span class="text-sm text-gray-500">{{ $vehicle->type ?? '-' }}</span>
                            <span class="text-sm text-gray-400">|</span>
                            <span class="text-sm text-gray-600">{{ \Carbon\Carbon::parse($service->service_date)->format('d M Y') }}</span>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2 text-sm">
                            <div><span class="text-gray-500">📟 Odometer:</span> {{ $service->odometer_at_service ?? '-' }} km</div>
                            <div><span class="text-gray-500">💰 Biaya:</span> <span class="font-semibold text-red-600">Rp {{ number_format($service->cost, 0, ',', '.') }}</span></div>
                            <div class="col-span-2 md:col-span-1"><span class="text-gray-500">📝 Deskripsi:</span> {{ $service->description ?? '-' }}</div>
                        </div>
                        @if($service->photo_evidence)
                            <div class="mt-2">
                                <a href="{{ route('drms.private.image', $service->photo_evidence) }}" target="_blank" 
                                   class="inline-flex items-center text-blue-600 hover:underline text-sm">
                                    📷 Lihat Foto Bukti
                                </a>
                            </div>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <form action="{{ route('drms.admin.vehicle.services.delete', $service->id) }}" method="POST" 
                              onsubmit="return confirm('Yakin ingin menghapus data service ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition">
                                🗑️ Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                <div class="text-4xl mb-2">🔧</div>
                <p>Belum ada data service kendaraan.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $services->links() }}
    </div>
</div>

{{-- Modal Tambah Service --}}
<div id="serviceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-screen overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h2 class="text-lg font-bold">➕ Tambah Service Kendaraan</h2>
            <button onclick="document.getElementById('serviceModal').classList.add('hidden')" 
                    class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('drms.admin.vehicle.services.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kendaraan <span class="text-red-500">*</span></label>
                        <select name="vehicle_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Pilih Kendaraan</option>
                            @foreach($vehicles as $v)
                                <option value="{{ $v->id }}">{{ $v->plate_number }} - {{ $v->type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Service <span class="text-red-500">*</span></label>
                        <input type="date" name="service_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Odometer (km)</label>
                        <input type="number" name="odometer_at_service" step="1" min="0" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Misal: 15000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Biaya (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="cost" step="0.01" min="0" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Jelaskan jenis service..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Foto Bukti</label>
                        <input type="file" name="photo_evidence" accept="image/*" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-400 mt-1">Maksimal 5MB, format JPG/PNG</p>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">💾 Simpan</button>
                    <button type="button" onclick="document.getElementById('serviceModal').classList.add('hidden')" 
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection