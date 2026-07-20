@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <h1 class="text-2xl font-bold">🎫 Daftar Voucher</h1>
        <a href="{{ route('drms.vouchers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
            <span>+</span> Tambah Voucher
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    {{-- FILTER --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <form method="GET" action="{{ route('drms.vouchers.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🔍 Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Cari kode voucher..." 
                       class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-48">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📌 Status</label>
                <select name="status" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>✅ Available</option>
                    <option value="used" {{ request('status') == 'used' ? 'selected' : '' }}>🔒 Used</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🚗 Tipe</label>
                <select name="type" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="grab" {{ request('type') == 'grab' ? 'selected' : '' }}>Grab</option>
                    <option value="gojek" {{ request('type') == 'gojek' ? 'selected' : '' }}>Gojek</option>
                    <option value="taxi" {{ request('type') == 'taxi' ? 'selected' : '' }}>Bluebird</option>
                </select>
            </div>
            @if(auth()->user()->isDrmsSuperAdmin())
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🏢 Business Unit</label>
                <select name="business_unit_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua BU</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ request('business_unit_id') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🔍 Tampilkan
                </button>
                @if(request()->anyFilled(['search', 'status', 'type', 'business_unit_id']))
                    <a href="{{ route('drms.vouchers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- QUICK STATS --}}
    @php
        $total = $vouchers->total();
        $available = $vouchers->where('status', 'available')->count();
        $used = $vouchers->where('status', 'used')->count();
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Total Voucher</p>
            <p class="text-2xl font-bold">{{ $total }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">Available</p>
            <p class="text-2xl font-bold text-green-600">{{ $available }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-gray-500">
            <p class="text-xs text-gray-500 uppercase">Used</p>
            <p class="text-2xl font-bold text-gray-600">{{ $used }}</p>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nominal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Business Unit</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($vouchers as $v)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-mono text-sm">{{ $v->code }}</td>
                        <td class="px-6 py-4 font-semibold">Rp {{ number_format($v->nominal, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs 
                                @if($v->type == 'grab') bg-green-100 text-green-800
                                @elseif($v->type == 'gojek') bg-blue-100 text-blue-800
                                @else bg-yellow-100 text-yellow-800 @endif">
                                {{ ucfirst($v->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($v->status == 'available')
                                <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">✅ Available</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">🔒 Used</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $v->businessUnit->nama_bisnis_unit ?? $v->business_unit_id ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            <div class="text-4xl mb-2">🎫</div>
                            <p>Belum ada voucher.</p>
                            @if(request()->anyFilled(['search', 'status', 'type', 'business_unit_id']))
                                <p class="text-sm mt-1">Coba ubah filter pencarian.</p>
                            @endif
                            <a href="{{ route('drms.vouchers.create') }}" class="mt-2 inline-block text-blue-600 hover:underline">+ Tambah Voucher</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vouchers->hasPages())
        <div class="px-6 py-3 border-t">
            {{ $vouchers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection