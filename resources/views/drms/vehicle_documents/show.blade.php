@extends('layouts.app_car_sidebar')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">📄 Detail Dokumen Kendaraan</h1>
        <div class="space-x-2">
            <a href="{{ route('drms.vehicle-documents.edit', $document->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                ✏️ Edit
            </a>
            <a href="{{ route('drms.vehicle-documents.index') }}" class="text-gray-600 hover:text-gray-800">← Kembali</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Informasi Kendaraan --}}
        <div class="bg-gray-50 p-4 rounded-xl">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">🚗 Informasi Kendaraan</h3>
            <div class="space-y-2">
                <div>
                    <span class="text-xs text-gray-400">Plat Nomor</span>
                    <p class="font-semibold text-lg">{{ $document->vehicle->plate_number }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-400">Tipe</span>
                    <p class="font-medium">{{ $document->vehicle->type }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-400">Kapasitas</span>
                    <p class="font-medium">{{ $document->vehicle->capacity }} orang</p>
                </div>
                <div>
                    <span class="text-xs text-gray-400">Status</span>
                    <p class="font-medium capitalize">{{ $document->vehicle->status }}</p>
                </div>
            </div>
        </div>

        {{-- Ringkasan Dokumen --}}
        <div class="bg-gray-50 p-4 rounded-xl">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">📋 Ringkasan Dokumen</h3>
            <div class="space-y-2">
                <div>
                    <span class="text-xs text-gray-400">STNK</span>
                    <p class="font-medium">
                        {{ $document->stnk_expiry ? \Carbon\Carbon::parse($document->stnk_expiry)->format('d M Y') : '-' }}
                        @if($document->stnk_file)
                            <a href="{{ route('drms.private.image', $document->stnk_file) }}" target="_blank" class="text-blue-600 text-sm ml-2">📎 Lihat</a>
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-xs text-gray-400">Pajak Tahunan</span>
                    <p class="font-medium">
                        {{ $document->tax_yearly_expiry ? \Carbon\Carbon::parse($document->tax_yearly_expiry)->format('d M Y') : '-' }}
                        @if($document->tax_file)
                            <a href="{{ route('drms.private.image', $document->tax_file) }}" target="_blank" class="text-blue-600 text-sm ml-2">📎 Lihat</a>
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-xs text-gray-400">Pajak 5 Tahunan</span>
                    <p class="font-medium">{{ $document->tax_5year_expiry ? \Carbon\Carbon::parse($document->tax_5year_expiry)->format('d M Y') : '-' }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-400">Asuransi</span>
                    <p class="font-medium">
                        {{ $document->insurance_expiry ? \Carbon\Carbon::parse($document->insurance_expiry)->format('d M Y') : '-' }}
                        @if($document->insurance_file)
                            <a href="{{ route('drms.private.image', $document->insurance_file) }}" target="_blank" class="text-blue-600 text-sm ml-2">📎 Lihat</a>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Kelengkapan --}}
    <div class="mt-6 bg-gray-50 p-4 rounded-xl">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">✅ Status Kelengkapan</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full {{ $document->stnk_file && $document->stnk_expiry ? 'bg-green-500' : 'bg-red-500' }}"></span>
                <span class="text-sm">STNK</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full {{ $document->tax_file && $document->tax_yearly_expiry ? 'bg-green-500' : 'bg-red-500' }}"></span>
                <span class="text-sm">Pajak Tahunan</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full {{ $document->tax_5year_expiry ? 'bg-green-500' : 'bg-red-500' }}"></span>
                <span class="text-sm">Pajak 5 Tahun</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full {{ $document->insurance_file && $document->insurance_expiry ? 'bg-green-500' : 'bg-red-500' }}"></span>
                <span class="text-sm">Asuransi</span>
            </div>
        </div>
    </div>

    {{-- Catatan --}}
    @if($document->notes)
    <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
        <p class="text-sm font-medium text-gray-700">📝 Catatan</p>
        <p class="text-sm text-gray-600">{{ $document->notes }}</p>
    </div>
    @endif

    {{-- Tombol Aksi --}}
    <div class="mt-8 flex justify-between items-center border-t pt-4">
        <span class="text-xs text-gray-400">
            Dibuat: {{ $document->created_at ? $document->created_at->format('d M Y H:i') : '-' }}
            @if($document->updated_at && $document->updated_at != $document->created_at)
                | Diperbarui: {{ $document->updated_at->format('d M Y H:i') }}
            @endif
        </span>
        <form action="{{ route('drms.vehicle-documents.destroy', $document->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus dokumen ini?')">
            @csrf @method('DELETE')
            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                🗑️ Hapus Dokumen
            </button>
        </form>
    </div>
</div>
@endsection