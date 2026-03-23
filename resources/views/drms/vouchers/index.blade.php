@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-4">Daftar Voucher</h1>
    <a href="{{ route('drms.vouchers.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded mb-4 inline-block">Tambah Voucher</a>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Kode</th>
                    <th class="px-4 py-2 text-left">Nominal</th>
                    <th class="px-4 py-2 text-left">Tipe</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vouchers as $v)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $v->code }}</td>
                    <td class="px-4 py-2">Rp {{ number_format($v->nominal,0,',','.') }}</td>
                    <td class="px-4 py-2">{{ ucfirst($v->type) }}</td>
                    <td class="px-4 py-2">
                        @php
                            $statusColor = $v->status == 'available' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs {{ $statusColor }}">{{ ucfirst($v->status) }}</span>
                    </td>
                    <td class="px-4 py-2">
                        <a href="{{ route('drms.vouchers.edit', $v) }}" class="text-blue-600 hover:underline">Edit</a>
                        <form action="{{ route('drms.vouchers.destroy', $v) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline ml-2">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada voucher.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
