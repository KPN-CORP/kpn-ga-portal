@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">Riwayat Cetak Laporan</h2>
        <a href="{{ route('stock-ctl.laporan.index') }}" class="text-blue-600 hover:underline">← Kembali ke Form Laporan</a>
    </div>

    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Waktu Cetak</th>
                    <th class="px-4 py-3 text-left">User</th>
                    <th class="px-4 py-3 text-left">Jenis Laporan</th>
                    <th class="px-4 py-3 text-left">Area</th>
                    <th class="px-4 py-3 text-left">Barang</th>
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-left">Nama File</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($histories as $h)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $h->dicetak_pada->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3">{{ $h->user->name ?? '-' }}</td>
                    <td class="px-4 py-3">{{ ucfirst($h->jenis) }}</td>
                    <td class="px-4 py-3">{{ $h->area->nama_area ?? 'Semua Area' }}</td>
                    <td class="px-4 py-3">{{ $h->barang->nama_barang ?? 'Semua Barang' }}</td>
                    <td class="px-4 py-3">
                        @if($h->tanggal_awal && $h->tanggal_akhir)
                            {{ \Carbon\Carbon::parse($h->tanggal_awal)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($h->tanggal_akhir)->format('d/m/Y') }}
                        @elseif($h->tanggal_awal)
                            Mulai {{ \Carbon\Carbon::parse($h->tanggal_awal)->format('d/m/Y') }}
                        @elseif($h->tanggal_akhir)
                            Sampai {{ \Carbon\Carbon::parse($h->tanggal_akhir)->format('d/m/Y') }}
                        @else
                            Semua Periode
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $h->nama_file ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-10 text-center text-gray-500">Belum ada riwayat cetak laporan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($histories->hasPages())
        <div class="mt-4">{{ $histories->links() }}</div>
    @endif
</div>
@endsection