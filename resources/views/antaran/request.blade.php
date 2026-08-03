@extends('layouts.app-sidebar')

@section('content')
<style>
  .jne-orange { background:#2563eb; }
  .jne-text { color:#2563eb; }
  .jne-border:focus { border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.15); }
</style>

<div class="max-w-lg md:max-w-2xl mx-auto px-4 py-6">
  <div class="jne-orange rounded-t-xl px-5 py-4 text-white">
    <h1 class="text-lg font-semibold">Buat kiriman baru</h1>
    <p class="text-sm opacity-90">Isi sekali, kurir kami yang urus sisanya</p>
  </div>

  <form action="{{ route('antaran.store') }}" method="POST" enctype="multipart/form-data"
        class="bg-white border border-t-0 rounded-b-xl px-5 py-5 space-y-4">
    @csrf

    <div>
      <label class="text-sm font-medium text-gray-700">Jenis kiriman</label>
      <div class="mt-2 grid grid-cols-2 gap-2">
        <label class="border rounded-lg px-3 py-2 flex items-center gap-2 cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
          <input type="radio" name="jenis_barang" value="dokumen" checked class="accent-blue-500">
          <span class="text-sm">Dokumen</span>
        </label>
        <label class="border rounded-lg px-3 py-2 flex items-center gap-2 cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
          <input type="radio" name="jenis_barang" value="paket" class="accent-blue-500">
          <span class="text-sm">Paket</span>
        </label>
      </div>
    </div>

    <div>
      <label class="text-sm font-medium text-gray-700">Deskripsi barang</label>
      <textarea name="deskripsi" rows="2" required maxlength="500"
        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm jne-border" placeholder="Contoh: dokumen kontrak 3 lembar"></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="text-sm font-medium text-gray-700">Alamat jemput</label>
        <input type="text" name="alamat_asal" required maxlength="255"
          class="mt-1 w-full border rounded-lg px-3 py-2 text-sm jne-border" placeholder="Gedung, lantai, ruangan">
        <input type="url" name="maps_asal_input" maxlength="500"
          class="mt-1.5 w-full border rounded-lg px-3 py-1.5 text-xs jne-border" placeholder="Link Google Maps (opsional)">
        <p class="text-[11px] text-gray-400 mt-1">Kosongkan kalau mau otomatis dicari dari alamat di atas.</p>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Alamat tujuan</label>
        <input type="text" name="alamat_tujuan" required maxlength="255"
          class="mt-1 w-full border rounded-lg px-3 py-2 text-sm jne-border" placeholder="Gedung, lantai, ruangan">
        <input type="url" name="maps_tujuan_input" maxlength="500"
          class="mt-1.5 w-full border rounded-lg px-3 py-1.5 text-xs jne-border" placeholder="Link Google Maps (opsional)">
        <p class="text-[11px] text-gray-400 mt-1">Kosongkan kalau mau otomatis dicari dari alamat di atas.</p>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="text-sm font-medium text-gray-700">Nama penerima</label>
        <input type="text" name="penerima" required maxlength="100"
          class="mt-1 w-full border rounded-lg px-3 py-2 text-sm jne-border">
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">No. HP penerima</label>
        <input type="text" name="no_hp_penerima" required pattern="[0-9]{10,13}"
          class="mt-1 w-full border rounded-lg px-3 py-2 text-sm jne-border" placeholder="08xxxxxxxxxx">
      </div>
    </div>

    <div>
      <label class="text-sm font-medium text-gray-700">Foto/dokumen barang</label>
      <input type="file" name="foto_barang" required accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
        class="mt-1 w-full text-sm">
      <p class="text-xs text-gray-400 mt-1">Maks 20MB. Foto akan otomatis dikompres.</p>
    </div>

    @if ($errors->any())
      <div class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">
        <ul class="list-disc list-inside">
          @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    @endif

    <button type="submit" class="jne-orange w-full text-white rounded-lg py-2.5 text-sm font-medium">
      Kirim sekarang
    </button>
  </form>
</div>
@endsection