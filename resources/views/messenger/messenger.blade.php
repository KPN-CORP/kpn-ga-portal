@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Messenger</h2>
            <p class="text-xs text-gray-500">Daftar pengiriman & status</p>
            @if($hasAccessAll)
                <span class="inline-block mt-1 px-2 py-1 bg-purple-100 text-purple-700 
                           rounded-full text-xs font-semibold">
                    <i class="fas fa-eye mr-1"></i> Akses Semua Data
                </span>
            @endif
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('messenger.request') }}"
               class="flex-1 sm:flex-none inline-flex items-center justify-center
                      px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold
                      hover:bg-blue-700 transition">
                + New Request
            </a>

            <button id="toggleFilterBtn"
                class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700
                       rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
        </div>
    </div>

    {{-- FILTER --}}
    <div id="filterSection"
         class="bg-white border rounded-xl p-4
                {{ request()->hasAny(['search','status','date','pengirim']) ? '' : 'hidden' }}">
        <form method="GET" action="{{ route('messenger.index') }}"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $hasAccessAll ? '5' : '4' }} gap-4">

            @if($hasAccessAll)
            <div>
                <label class="text-sm font-medium text-gray-600">Pengirim</label>
                <select name="pengirim"
                        class="mt-1 w-full border rounded-lg px-3 py-2
                               text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">Semua Pengirim</option>
                    @foreach($pelangganList as $p)
                        <option value="{{ $p->id_pelanggan }}" 
                                {{ request('pengirim') == $p->id_pelanggan ? 'selected' : '' }}>
                            {{ $p->nama_pelanggan }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="mt-1 w-full border rounded-lg px-3 py-2
                              text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status"
                        class="mt-1 w-full border rounded-lg px-3 py-2
                               text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach(['Belum Terkirim','Proses Pengiriman','Dokumen Belum Tersedia','Terkirim','Ditolak','Batal'] as $s)
                        <option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>
                            {{ $s }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Tanggal</label>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="mt-1 w-full border rounded-lg px-3 py-2
                              text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button class="w-full bg-blue-600 text-white py-2 rounded-lg
                               text-sm font-semibold hover:bg-blue-700">
                    Apply
                </button>
                <a href="{{ route('messenger.index') }}"
                   class="w-full bg-gray-200 py-2 rounded-lg
                          text-sm text-center font-medium">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- ================= MOBILE CARD ================= --}}
    <div class="block sm:hidden space-y-3">
        @foreach($transaksi as $item)

        @php
        $badge = match($item->status){
            'Belum Terkirim'=>'bg-blue-100 text-blue-700',
            'Proses Pengiriman'=>'bg-orange-100 text-orange-700',
            'Dokumen Belum Tersedia'=>'bg-purple-100 text-purple-700',
            'Terkirim'=>'bg-green-100 text-green-700',
            'Ditolak'=>'bg-red-100 text-red-700',
            default=>'bg-gray-100 text-gray-700'
        };
        $isOwner = $pelanggan && $item->pengirim == $pelanggan->id_pelanggan;
        @endphp

        <div class="bg-white rounded-xl border p-4 shadow-sm">

            {{-- HEADER HP (VISIBLE) --}}
            <div class="flex justify-between items-center cursor-pointer toggleCardHeader">

                {{-- LEFT --}}
                <div>
                    <div class="text-sm font-semibold text-gray-800">
                        {{ $item->no_transaksi }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ $item->penerima ?? '-' }}
                    </div>
                    @if($hasAccessAll)
                    <div class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-user mr-1"></i>{{ $item->nama_pengirim ?? '-' }}
                    </div>
                    @endif
                </div>

                {{-- RIGHT --}}
                <div class="flex items-center gap-2">
                    <div class="text-right">
                        <div class="text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }}
                        </div>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full
                                     text-xs font-semibold {{ $badge }}">
                            {{ $item->status }}
                        </span>
                    </div>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </div>
            </div>

            {{-- DROPDOWN DETAIL --}}
            <div class="mt-3 hidden toggleCardContent">

                <div class="bg-gray-50/70 border border-gray-200 rounded-lg p-3 space-y-3">

                    <div>
                        <div class="text-xs text-gray-500">Jenis Barang</div>
                        <div class="text-sm font-medium text-gray-800">
                            {{ $item->nama_barang ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500">Alamat Asal</div>
                        <div class="text-sm font-medium text-gray-800 leading-relaxed">
                            {{ $item->alamat_asal ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500">Alamat Tujuan</div>
                        <div class="text-sm font-medium text-gray-800 leading-relaxed">
                            {{ $item->alamat_tujuan ?? '-' }}
                        </div>
                    </div>

                </div>

                <div class="pt-3 flex items-center justify-between gap-2">
                    <a href="{{ route('messenger.detail',$item->no_transaksi) }}"
                    class="inline-block text-blue-600 font-semibold text-sm">
                    Lihat Detail →
                    </a>

                    @if($isOwner && $item->status=='Dokumen Belum Tersedia')
                    <form method="POST" action="{{ route('messenger.kirimUlang', $item->no_transaksi) }}"
                          onsubmit="return confirm('Kirim ulang pengiriman ini karena dokumen sudah tersedia?');">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 bg-purple-600 text-white rounded-lg text-xs font-semibold hover:bg-purple-700 transition">
                            Kirim Ulang
                        </button>
                    </form>
                    @endif
                </div>

            </div>


        </div>
        @endforeach
    </div>

    {{-- ================= DESKTOP TABLE ================= --}}
    <div class="hidden sm:block bg-white rounded-xl border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-bold">No. Transaksi</th>
                    @if($hasAccessAll)
                    <th class="px-4 py-3 text-left font-bold">Pengirim</th>
                    @endif
                    <th class="px-4 py-3 text-left font-bold">Jenis</th>
                    <th class="px-4 py-3 text-left font-bold">Status</th>
                    <th class="px-4 py-3 text-left font-bold">Penerima</th>
                    <th class="px-4 py-3 text-left font-bold">Alamat</th>
                    <th class="px-4 py-3 text-left font-bold">Tanggal</th>
                    <th class="px-4 py-3 text-left font-bold">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @foreach($transaksi as $item)

                @php
                $badge = match($item->status){
                    'Belum Terkirim'=>'bg-blue-100 text-blue-700',
                    'Proses Pengiriman'=>'bg-orange-100 text-orange-700',
                    'Dokumen Belum Tersedia'=>'bg-purple-100 text-purple-700',
                    'Terkirim'=>'bg-green-100 text-green-700',
                    'Ditolak'=>'bg-red-100 text-red-700',
                    default=>'bg-gray-100 text-gray-700'
                };
                $isOwner = $pelanggan && $item->pengirim == $pelanggan->id_pelanggan;
                @endphp

                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-medium">{{ $item->no_transaksi }}</td>
                    @if($hasAccessAll)
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $item->nama_pengirim }}</div>
                        <div class="text-xs text-gray-500">{{ $item->hp_pengirim ?? '-' }}</div>
                    </td>
                    @endif
                    <td class="px-4 py-3">{{ $item->nama_barang }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $badge }}">
                            {{ $item->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $item->penerima }}</td>
                    <td class="px-4 py-3">{{ $item->alamat_tujuan }}</td>
                    <td class="px-4 py-3">
                        {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('messenger.detail',$item->no_transaksi) }}"
                               class="text-blue-600 font-semibold hover:underline">
                                Detail
                            </a>
                            @if($isOwner && $item->status=='Dokumen Belum Tersedia')
                            <form method="POST" action="{{ route('messenger.kirimUlang', $item->no_transaksi) }}"
                                  onsubmit="return confirm('Kirim ulang pengiriman ini karena dokumen sudah tersedia?');">
                                @csrf
                                <button type="submit"
                                        class="px-2 py-1 bg-purple-600 text-white rounded text-xs font-semibold hover:bg-purple-700 transition">
                                    Kirim Ulang
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between
                gap-3 text-sm text-gray-600">
        <div>
            Showing {{ $transaksi->firstItem() }} – {{ $transaksi->lastItem() }}
            of {{ $transaksi->total() }}
        </div>
        {{ $transaksi->links() }}
    </div>

</div>

{{-- SCRIPT --}}
<script>
document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden')
})

document.querySelectorAll('.toggleCardHeader').forEach(header => {
    header.addEventListener('click', () => {
        const content = header.nextElementSibling
        const icon = header.querySelector('i')

        content.classList.toggle('hidden')
        icon.classList.toggle('rotate-180')
    })
})
</script>
@endsection