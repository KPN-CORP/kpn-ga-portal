@extends('layouts.app_apartadmin_sidebar')
@section('content')
<div class="p-4 md:p-6">

    @if(session('success'))
    <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">{{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- HEADER --}}
    <div class="mb-6 md:mb-8">
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-800">Detail Apartemen</h1>
            <p class="text-gray-600 text-xs mt-1">Informasi detail apartemen dan unit</p>
        </div>
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                <div><h1 class="text-2xl font-bold text-gray-800">Detail Apartemen</h1><p class="text-gray-600 text-sm mt-1">Informasi detail apartemen dan unit</p></div>
            </div>

            {{-- Input pencarian diperlebar --}}
            <div class="w-full lg:w-1/2 xl:w-2/3 order-first lg:order-none">
                <div class="relative">
                    <form action="{{ route('apartemen.admin.apartemen.detail', $apartemen->id) }}" method="GET">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full"
                               placeholder="Cari nomor unit...">
                    </form>
                </div>
            </div>
        </div>

        {{-- Breadcrumb --}}
        <div class="flex items-center text-sm text-gray-600 mb-4">
            <a href="{{ route('apartemen.admin.apartemen') }}" class="hover:text-blue-600">Apartemen</a>
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-800">{{ $apartemen->nama_apartemen }}</span>
        </div>
    </div>

    {{-- APARTEMEN INFO --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-800">{{ $apartemen->nama_apartemen }}</h2>
                    <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div><label class="text-sm text-gray-500">Alamat</label><p class="font-medium">{{ $apartemen->alamat ?? '-' }}</p></div>
                        <div><label class="text-sm text-gray-500">Penanggung Jawab</label><p class="font-medium">{{ $apartemen->penanggung_jawab ?? '-' }}</p></div>
                        <div><label class="text-sm text-gray-500">Telepon</label><p class="font-medium">{{ $apartemen->telepon ?? '-' }}</p></div>
                        <div><label class="text-sm text-gray-500">Email</label><p class="font-medium">{{ $apartemen->email ?? '-' }}</p></div>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 min-w-[200px]">
                    <div class="text-center mb-2"><label class="text-sm text-gray-500">Status Unit</label></div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="text-center"><div class="text-lg font-bold text-gray-900">{{ $apartemen->units_count ?? 0 }}</div><div class="text-xs text-gray-500">Total</div></div>
                        <div class="text-center"><div class="text-lg font-bold text-green-600">{{ $apartemen->units_ready ?? 0 }}</div><div class="text-xs text-gray-500">Tersedia</div></div>
                        <div class="text-center"><div class="text-lg font-bold text-blue-600">{{ $apartemen->units_terisi ?? 0 }}</div><div class="text-xs text-gray-500">Terisi</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- UNIT LIST --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h3 class="text-lg font-semibold text-gray-800">Daftar Unit</h3>
                <div class="flex items-center gap-3">
                    <div class="text-sm text-gray-500">Total: <span class="font-medium">{{ $units->total() }}</span> unit</div>
                    <a href="{{ route('unit.create', $apartemen->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium">+ Tambah Unit</a>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="p-6">
            @if($units->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomor Unit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bisnis Unit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kapasitas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Penghuni Aktif</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">360 View</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($units as $unit)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><div class="font-medium text-gray-900">Unit {{ $unit->nomor_unit }}</div></td>
                            <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-gray-900">{{ $unit->bisnisUnit->nama_bisnis_unit ?? '-' }}</div></td>
                            <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-gray-900">{{ $unit->kapasitas }} orang</div></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @switch($unit->status)
                                    @case('READY') <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Tersedia</span> @break
                                    @case('TERISI') <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Terisi</span> @break
                                    @case('MAINTENANCE') <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Maintenance</span> @break
                                @endswitch
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-gray-900">{{ $unit->active_assignments ?? 0 }} orang</div></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($unit->gambar_360)
                                    <button onclick="open360Modal('{{ Storage::url($unit->gambar_360) }}', '{{ $unit->nomor_unit }}')" class="text-blue-600 hover:text-blue-800 text-sm">Lihat 360</button>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('unit.edit', $unit->id) }}" class="text-indigo-600 hover:text-indigo-800">Edit</a>
                                    @if($unit->status == 'READY' && ($unit->active_assignments ?? 0) == 0)
                                    <button onclick="deleteUnit({{ $unit->id }}, '{{ $unit->nomor_unit }}')" class="text-red-600 hover:text-red-800">Hapus</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $units->links() }}</div>
            @else
            <div class="text-center py-12">Belum ada unit</div>
            @endif
        </div>
    </div>
</div>

<script>
function deleteUnit(unitId, unitName) {
    if(confirm(`Hapus Unit ${unitName}?`)){
        let btn = event.target;
        let orig = btn.innerHTML;
        btn.innerHTML = 'Menghapus...';
        btn.disabled = true;
        fetch("{{ route('apartemen.admin.unit.delete') }}", { method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' }, body:JSON.stringify({ unit_id:unitId }) })
        .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message); btn.innerHTML=orig; btn.disabled=false; })
        .catch(e=>{ alert('Error'); btn.innerHTML=orig; btn.disabled=false; });
    }
}
</script>
<style>
.overflow-x-auto::-webkit-scrollbar { height: 4px; }
</style>
@endsection