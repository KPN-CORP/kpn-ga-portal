@extends('layouts.app-sidebar')

@section('content')
<style>.jne-orange{background:#2563eb}.jne-text{color:#2563eb}</style>

<div class="max-w-lg md:max-w-full mx-auto px-4 md:px-8 py-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-lg font-semibold">Kiriman saya</h1>
    <a href="{{ route('antaran.request') }}" class="jne-orange text-white text-sm rounded-lg px-3 py-1.5">+ New Request</a>
  </div>

  @php
    $tabAktif = $tab ?? 'semua';
    $tabList = [
      'semua'   => 'Semua',
      'proses'  => 'Diproses',
      'selesai' => 'Selesai',
      'batal'   => 'Dibatalkan',
    ];
  @endphp
  <div class="flex items-center justify-between gap-2 mb-4 border-b overflow-x-auto">
    <div class="flex gap-1">
      @foreach ($tabList as $key => $label)
        <a href="{{ route('antaran.index', array_merge(request()->except(['page', 'tab']), $key === 'semua' ? [] : ['tab' => $key])) }}"
           class="px-3 py-2 text-sm whitespace-nowrap border-b-2 -mb-px {{ $tabAktif === $key ? 'jne-text border-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
          {{ $label }}
        </a>
      @endforeach
    </div>

    <div class="flex gap-1.5 pb-2 shrink-0">
      <a href="{{ route('antaran.export', array_merge(request()->except('page'), ['range' => 'bulan'])) }}"
         class="flex items-center gap-1 text-xs border rounded-lg px-2.5 py-1.5 text-gray-600 hover:bg-gray-50 whitespace-nowrap">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
        Bulan Ini
      </a>
      <a href="{{ route('antaran.export', array_merge(request()->except('page'), ['range' => 'semua'])) }}"
         class="flex items-center gap-1 text-xs border rounded-lg px-2.5 py-1.5 text-gray-600 hover:bg-gray-50 whitespace-nowrap">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
        Semua
      </a>
    </div>
  </div>

  <form method="GET" class="mb-4 md:max-w-sm">
    @if ($tabAktif !== 'semua')
      <input type="hidden" name="tab" value="{{ $tabAktif }}">
    @endif
    <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari no. resi, pengirim, atau penerima..."
      class="w-full border rounded-lg px-3 py-2 text-sm">
  </form>

  {{-- HP: tampilan card --}}
  <div class="grid grid-cols-1 gap-3 md:hidden">
    @forelse ($transaksi as $item)
      <a href="{{ route('antaran.detail', $item->no_transaksi) }}"
         class="block border rounded-xl px-4 py-3 hover:border-blue-300">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-sm font-semibold">{{ $item->no_transaksi }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($item->deskripsi, 40) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Dari: {{ $item->nama_pengirim ?? '-' }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Ke: {{ $item->penerima ?? '-' }} &middot; {{ Str::limit($item->alamat_tujuan, 35) }}</p>
          </div>
          @include('antaran.partials.status-badge', ['status' => $item->status])
        </div>
      </a>
    @empty
      <p class="text-sm text-gray-400 text-center py-10">Belum ada kiriman.</p>
    @endforelse
  </div>

  {{-- Desktop: tampilan tabel --}}
  <div class="hidden md:block overflow-x-auto">
    @if ($transaksi->isEmpty())
      <p class="text-sm text-gray-400 text-center py-10">Belum ada kiriman.</p>
    @else
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b text-xs text-gray-400">
            <th class="py-2 pr-3 font-medium">No. Transaksi</th>
            <th class="py-2 pr-3 font-medium">Deskripsi</th>
            <th class="py-2 pr-3 font-medium">Pengirim</th>
            <th class="py-2 pr-3 font-medium">Penerima</th>
            <th class="py-2 pr-3 font-medium">Tujuan</th>
            <th class="py-2 pr-3 font-medium">Status</th>
            <th class="py-2 pr-3 font-medium"></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($transaksi as $item)
            <tr class="border-b last:border-0 hover:bg-gray-50">
              <td class="py-2 pr-3 text-sm font-semibold">{{ $item->no_transaksi }}</td>
              <td class="py-2 pr-3 text-xs text-gray-600">{{ Str::limit($item->deskripsi, 40) }}</td>
              <td class="py-2 pr-3 text-xs text-gray-600">{{ $item->nama_pengirim ?? '-' }}</td>
              <td class="py-2 pr-3 text-xs text-gray-600">{{ $item->penerima ?? '-' }}</td>
              <td class="py-2 pr-3 text-xs text-gray-600">{{ Str::limit($item->alamat_tujuan, 40) }}</td>
              <td class="py-2 pr-3">
                @include('antaran.partials.status-badge', ['status' => $item->status])
              </td>
              <td class="py-2 pr-3">
                <a href="{{ route('antaran.detail', $item->no_transaksi) }}" class="jne-text underline text-xs whitespace-nowrap">Lihat detail</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>

  <div class="mt-4">{{ $transaksi->links() ?? '' }}</div>
</div>
@endsection