@extends('layouts.app-sidebar')

@section('content')
<style>.jne-orange{background:#f36f21}.jne-text{color:#f36f21}</style>

<div class="max-w-lg md:max-w-3xl lg:max-w-5xl mx-auto px-4 py-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-lg font-semibold">Kiriman saya</h1>
    <a href="{{ route('antaran.request') }}" class="jne-orange text-white text-sm rounded-lg px-3 py-1.5">+ Kirim</a>
  </div>

  <form method="GET" class="mb-4 md:max-w-sm">
    <input type="text" name="resi" value="{{ request('resi') }}" placeholder="Cari no. resi..."
      class="w-full border rounded-lg px-3 py-2 text-sm">
  </form>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
    @forelse ($transaksi as $item)
      <a href="{{ route('antaran.detail', $item->no_transaksi) }}"
         class="block border rounded-xl px-4 py-3 hover:border-orange-300">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-sm font-semibold">{{ $item->no_transaksi }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($item->deskripsi, 40) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Ke: {{ Str::limit($item->alamat_tujuan, 35) }}</p>
          </div>
          @include('antaran.partials.status-badge', ['status' => $item->status])
        </div>
      </a>
    @empty
      <p class="text-sm text-gray-400 text-center py-10 col-span-full">Belum ada kiriman.</p>
    @endforelse
  </div>

  <div class="mt-4">{{ $transaksi->links() ?? '' }}</div>
</div>
@endsection
