@extends('layouts.app-sidebar')

@section('content')
<div class="p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Detail Unit {{ $unit->nomor_unit }}</h1>
        <p class="text-gray-500">{{ $unit->apartemen->nama_apartemen }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- ASET UNIT --}}
        <div class="bg-white rounded-xl border shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Aset Unit</h2>
            
            @if($unit->unitAsets->count() > 0)
            <div class="space-y-3">
                @foreach($unit->unitAsets as $unitAset)
                <div class="flex items-center justify-between p-3 border rounded">
                    <div>
                        <h3 class="font-medium">{{ $unitAset->aset->nama_aset }}</h3>
                        <p class="text-sm text-gray-500">Jumlah: {{ $unitAset->jumlah }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full 
                        @if($unitAset->kondisi == 'BAIK') bg-green-100 text-green-800
                        @elseif($unitAset->kondisi == 'RUSAK') bg-red-100 text-red-800
                        @else bg-yellow-100 text-yellow-800 @endif">
                        {{ $unitAset->kondisi_text }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-400 text-center py-4">Belum ada data aset</p>
            @endif
        </div>

        {{-- PERATURAN UNIT --}}
        <div class="bg-white rounded-xl border shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Peraturan Unit</h2>
            
            @if($unit->peraturan->count() > 0)
            <div class="space-y-4">
                @foreach($unit->peraturan as $peraturan)
                <div class="p-3 bg-gray-50 rounded-lg border">
                    <p class="text-gray-700">{{ $peraturan->isi_peraturan }}</p>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-400 text-center py-4">Belum ada peraturan</p>
            @endif
        </div>
    </div>
</div>
@endsection