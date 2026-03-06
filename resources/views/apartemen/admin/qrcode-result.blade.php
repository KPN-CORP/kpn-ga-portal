{{-- resources/views/apartemen/admin/qrcode-result.blade.php --}}
@extends('layouts.app-sidebar')

@section('content')
<div class="p-4 md:p-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">QR Code Generated</h1>
                <p class="text-gray-600">Kode akses: <span class="font-mono bg-gray-100 px-3 py-1 rounded">{{ $kodeAkses }}</span></p>
            </div>
            
            <div class="flex flex-col md:flex-row gap-6">
                {{-- QR Code --}}
                <div class="flex-1 bg-gray-50 p-6 rounded-lg flex justify-center items-center">
                    {!! $qrCode !!}
                </div>
                
                {{-- Info --}}
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Detail Kode Akses</h2>
                    
                    <table class="w-full">
                        <tr>
                            <td class="py-2 text-gray-600">Kode</td>
                            <td class="py-2 font-mono">{{ $accessCode->kode_akses }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600">Nama</td>
                            <td class="py-2">{{ $accessCode->nama_akses ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600">Tipe</td>
                            <td class="py-2">
                                @if($accessCode->tipe == 'BOTH')
                                    Check-in & Check-out
                                @elseif($accessCode->tipe == 'CHECKIN')
                                    Hanya Check-in
                                @else
                                    Hanya Check-out
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600">Maksimal Penggunaan</td>
                            <td class="py-2">{{ $accessCode->max_uses ?? 'Unlimited' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600">Kadaluarsa</td>
                            <td class="py-2">{{ $accessCode->expired_at ? $accessCode->expired_at->format('d/m/Y H:i') : 'Tidak ada' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600">URL</td>
                            <td class="py-2">
                                <a href="{{ route('apartemen.public.index') }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm break-all">
                                    {{ route('apartemen.public.index') }}
                                </a>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('apartemen.admin.access-codes') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-4 py-2 rounded-lg">
                            Kembali
                        </a>
                        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg">
                            Cetak QR
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection