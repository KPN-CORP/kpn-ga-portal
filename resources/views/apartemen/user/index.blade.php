@extends('layouts.app-sidebar')

@section('content')
<div class="p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Status Aktif</h1>
        <p class="text-gray-600 text-sm mt-1">Lihat status tinggal Anda saat ini</p>
    </div>

    @forelse($penghuniAktif as $penghuni)
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-4">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="font-semibold text-lg">{{ $penghuni->unit->apartemen->nama_apartemen }}</h3>
                <p class="text-gray-600">Unit {{ $penghuni->unit->nomor_unit }}</p>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">Nama</span>
                        <p class="font-medium">{{ $penghuni->nama }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">ID Karyawan</span>
                        <p class="font-medium">{{ $penghuni->id_karyawan }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">No HP</span>
                        <p class="font-medium">{{ $penghuni->no_hp }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Periode</span>
                        <p class="font-medium">{{ $penghuni->tanggal_mulai->format('d/m/Y') }} - {{ $penghuni->tanggal_selesai->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Aktif</span>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
        <p class="text-gray-500">Tidak ada status aktif</p>
        <a href="{{ route('apartemen.user.create') }}" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded-lg">Buat Pengajuan</a>
    </div>
    @endforelse
</div>
@endsection