{{-- resources/views/apartemen/admin/access-codes.blade.php --}}
@extends('layouts.app-sidebar')

@section('content')
<div class="p-4 md:p-6">

    @if(session('success'))
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <ul>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Kode Akses QR</h1>
        <p class="text-gray-600">Generate dan kelola kode akses untuk check-in/out mandiri</p>
    </div>
    
    {{-- Form Generate QR --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Generate Kode Akses Baru</h2>
        
        <form action="{{ route('apartemen.admin.generate-qr') }}" method="POST" class="space-y-4">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Akses (Opsional)</label>
                    <input type="text" name="nama_akses" value="{{ old('nama_akses') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2" placeholder="Contoh: QR Code Lantai 1">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Akses</label>
                    <select name="tipe" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="BOTH" {{ old('tipe') == 'BOTH' ? 'selected' : '' }}>Check-in & Check-out</option>
                        <option value="CHECKIN" {{ old('tipe') == 'CHECKIN' ? 'selected' : '' }}>Hanya Check-in</option>
                        <option value="CHECKOUT" {{ old('tipe') == 'CHECKOUT' ? 'selected' : '' }}>Hanya Check-out</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Maksimal Penggunaan</label>
                    <input type="number" name="max_uses" value="{{ old('max_uses') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2" placeholder="Kosongkan untuk unlimited" min="1">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kadaluarsa</label>
                    <input type="datetime-local" name="expired_at" value="{{ old('expired_at') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
            </div>
            
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-lg">
                Generate QR Code
            </button>
        </form>
    </div>
    
    {{-- Daftar Kode Akses --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Daftar Kode Akses</h2>
        </div>
        
        <div class="p-6">
            @if($accessCodes->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Penggunaan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kadaluarsa</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($accessCodes as $code)
                    <tr>
                        <td class="px-4 py-3 font-mono">{{ $code->kode_akses }}</td>
                        <td class="px-4 py-3">{{ $code->nama_akses ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($code->tipe == 'BOTH')
                                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs">Check-in/out</span>
                            @elseif($code->tipe == 'CHECKIN')
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Check-in</span>
                            @else
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">Check-out</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            {{ $code->used_count }} / {{ $code->max_uses ?? '∞' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($code->is_active && $code->isValid())
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Aktif</span>
                            @else
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">Tidak Aktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            {{ $code->expired_at ? $code->expired_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex space-x-2">
                                @if($code->is_active)
                                <form action="{{ route('apartemen.admin.deactivate-code', $code->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 text-sm">Nonaktifkan</button>
                                </form>
                                @else
                                <form action="{{ route('apartemen.admin.activate-code', $code->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800 text-sm">Aktifkan</button>
                                </form>
                                @endif
                                
                                <form action="{{ route('apartemen.admin.delete-code', $code->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kode akses?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="mt-4">
                {{ $accessCodes->links() }}
            </div>
            @else
            <div class="text-center py-8">
                <p class="text-gray-500">Belum ada kode akses</p>
                <p class="text-sm text-gray-400 mt-1">Generate kode akses pertama Anda</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection