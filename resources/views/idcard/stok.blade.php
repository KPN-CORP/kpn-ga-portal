@extends('layouts.app-sidebar-card')
@section('content')
<style>[x-cloak]{display:none !important}</style>
@php
    $badge = [
        'aman'    => ['bg-green-100 text-green-800', 'Aman'],
        'menipis' => ['bg-orange-100 text-orange-800', 'Perlu beli'],
        'habis'   => ['bg-red-100 text-red-800', 'Habis'],
    ];
@endphp
<div class="space-y-6 text-sm text-gray-800 font-sans"
     x-data="{
        m: null,
        item: { id: '', nama: '', stok: 0, min: 0 },
        open(tipe, data) { this.item = data; this.m = tipe; },
        close() { this.m = null; }
     }"
     @keydown.escape.window="close()">

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">
                {{ (int) $owner->id === (int) auth()->id() ? 'Stok Anda' : 'Stok ' . $owner->name }}
            </h2>
            <p class="text-xs text-gray-500 mt-1">
                Tiap admin punya stok sendiri. Saat admin menyetujui request <b>Karyawan Baru, Karyawan Mutasi, dan Ganti Kartu</b>,
                yang berkurang adalah stok admin yang menyetujui.
                Magang dan visitor tidak dihitung di sini, lihat menu
                <a href="{{ route('idcard.kartu-pinjam') }}" class="text-blue-600 hover:underline">Kartu Pinjam</a>.
            </p>
        </div>
        @if($isSuper && $admins->isNotEmpty())
            <form method="GET" action="{{ route('idcard.stok') }}" class="flex items-center gap-2">
                <label for="owner_id" class="text-xs font-medium text-gray-600 whitespace-nowrap">Stok milik</label>
                <select id="owner_id" name="owner_id" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
                    @foreach($admins as $a)
                        <option value="{{ $a->id }}" {{ (int) $owner->id === (int) $a->id ? 'selected' : '' }}>{{ $a->name }}{{ (int) $a->id === (int) auth()->id() ? ' (Anda)' : '' }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Ringkasan per item --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($ringkasan as $r)
            @php [$cls, $lbl] = $badge[$r->status]; @endphp
            <div class="bg-white border rounded-xl p-4 flex flex-col gap-3">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-semibold text-gray-800">{{ $r->nama }}</p>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $cls }}">{{ $lbl }}</span>
                </div>
                <div class="flex items-end gap-2">
                    <span class="text-3xl font-semibold leading-none">{{ number_format($r->stok) }}</span>
                    <span class="text-xs text-gray-500 pb-1">pcs di stok</span>
                </div>
                <p class="text-xs text-gray-600">
                    Minimum: <b>{{ number_format($r->min_stok) }}</b>
                    @if($r->min_stok > 0 && $r->kurang > 0)
                        <span class="text-red-600">| kurang {{ number_format($r->kurang) }} dari minimum</span>
                    @elseif($r->min_stok == 0)
                        <span class="text-gray-400">| belum diatur</span>
                    @endif
                </p>
                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="button" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700"
                        x-on:click="open('masuk', @js(['id' => $r->id, 'nama' => $r->nama, 'stok' => $r->stok, 'min' => $r->min_stok]))">Stok Masuk</button>
                    <button type="button" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold hover:bg-gray-200"
                        x-on:click="open('opname', @js(['id' => $r->id, 'nama' => $r->nama, 'stok' => $r->stok, 'min' => $r->min_stok]))">Opname</button>
                    <button type="button" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold hover:bg-gray-200"
                        x-on:click="open('minimum', @js(['id' => $r->id, 'nama' => $r->nama, 'stok' => $r->stok, 'min' => $r->min_stok]))">Atur Minimum</button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Superadmin: stok semua admin --}}
    @if($isSuper && $matriks->isNotEmpty())
        <div class="bg-white border rounded-xl p-4">
            <h3 class="font-semibold text-gray-800 mb-3">Stok Semua Admin</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-3 py-2 text-left">Admin</th>
                            @foreach($items as $i)
                                <th class="px-3 py-2 text-right">{{ $i->nama }}</th>
                            @endforeach
                            <th class="px-3 py-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($matriks as $m)
                            <tr>
                                <td class="px-3 py-2">
                                    <a href="{{ route('idcard.stok', ['owner_id' => $m->user->id]) }}" class="text-blue-600 hover:underline font-medium">{{ $m->user->name }}</a>
                                </td>
                                @foreach($items as $i)
                                    @php
                                        $c = $m->stok[$i->id];
                                        $warna = ['aman' => 'text-gray-800', 'menipis' => 'text-orange-600', 'habis' => 'text-red-600'][$c->status];
                                    @endphp
                                    <td class="px-3 py-2 text-right {{ $warna }}">
                                        <span class="font-semibold">{{ number_format($c->stok) }}</span>
                                        <span class="text-xs text-gray-400">/ min {{ number_format($c->min_stok) }}</span>
                                    </td>
                                @endforeach
                                <td class="px-3 py-2">
                                    @if($m->perlu_beli)
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">Perlu beli</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Aman</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Riwayat mutasi --}}
    <div class="bg-white border rounded-xl p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Riwayat Mutasi Stok{{ (int) $owner->id === (int) auth()->id() ? '' : ' ' . $owner->name }}</h3>
        <form method="GET" action="{{ route('idcard.stok') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            @if($isSuper)<input type="hidden" name="owner_id" value="{{ $owner->id }}">@endif
            <div>
                <label class="text-xs font-medium text-gray-600">Item</label>
                <select name="item_id" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    @foreach($items as $i)
                        <option value="{{ $i->id }}" {{ (string) request('item_id') === (string) $i->id ? 'selected' : '' }}>{{ $i->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600">Jenis</label>
                <select name="tipe" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="all">Semua</option>
                    @foreach(\App\Models\IDCard\IdCardStokLog::TIPE_LABELS as $k => $l)
                        <option value="{{ $k }}" {{ request('tipe') === $k ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600">Dari tanggal</label>
                <input type="date" name="periode_awal" value="{{ request('periode_awal') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600">Sampai tanggal</label>
                <input type="date" name="periode_akhir" value="{{ request('periode_akhir') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Terapkan</button>
                <a href="{{ route('idcard.stok', $isSuper ? ['owner_id' => $owner->id] : []) }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto mt-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">Waktu</th>
                        <th class="px-3 py-2 text-left">Item</th>
                        <th class="px-3 py-2 text-left">Jenis</th>
                        <th class="px-3 py-2 text-right">Jumlah</th>
                        <th class="px-3 py-2 text-right">Saldo</th>
                        <th class="px-3 py-2 text-left">Keterangan</th>
                        <th class="px-3 py-2 text-left hidden lg:table-cell">Oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($riwayat as $log)
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $log->created_at?->format('d-m-Y H:i') }}</td>
                            <td class="px-3 py-2">{{ optional($log->item)->nama ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $log->tipe_text }}</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $log->qty < 0 ? 'text-red-600' : 'text-green-700' }}">{{ $log->qty > 0 ? '+' : '' }}{{ $log->qty }}</td>
                            <td class="px-3 py-2 text-right">{{ $log->saldo_setelah }}</td>
                            <td class="px-3 py-2">
                                {{ $log->catatan ?? '-' }}
                                @if($log->request_id)
                                    <a href="{{ route('idcard.detail', $log->request_id) }}" class="text-blue-600 hover:underline whitespace-nowrap">(#{{ $log->request_id }})</a>
                                @endif
                            </td>
                            <td class="px-3 py-2 hidden lg:table-cell">{{ optional($log->creator)->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-gray-500">Belum ada mutasi stok</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($riwayat->hasPages())
            <div class="mt-4">{{ $riwayat->links() }}</div>
        @endif
    </div>

    {{-- Modal: stok masuk --}}
    <div x-show="m === 'masuk'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="close()">
        <form method="POST" action="{{ route('idcard.stok.masuk') }}" class="bg-white rounded-xl shadow-xl w-full max-w-md p-5 space-y-4">
            @csrf
            <input type="hidden" name="owner_id" value="{{ $owner->id }}">
            <input type="hidden" name="item_id" :value="item.id">
            <h3 class="text-lg font-semibold">Stok Masuk: <span x-text="item.nama"></span></h3>
            <p class="text-xs text-gray-500">Stok sekarang <b x-text="item.stok"></b>. Isi jumlah yang baru diterima dari pembelian.</p>
            <div>
                <label class="block text-sm font-medium mb-1">Jumlah *</label>
                <input type="number" name="qty" min="1" required class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Catatan</label>
                <input type="text" name="catatan" maxlength="200" placeholder="Contoh: PO 123 / vendor" class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="close()" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold">Batal</button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Simpan</button>
            </div>
        </form>
    </div>

    {{-- Modal: opname --}}
    <div x-show="m === 'opname'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="close()">
        <form method="POST" action="{{ route('idcard.stok.opname') }}" class="bg-white rounded-xl shadow-xl w-full max-w-md p-5 space-y-4">
            @csrf
            <input type="hidden" name="owner_id" value="{{ $owner->id }}">
            <input type="hidden" name="item_id" :value="item.id">
            <h3 class="text-lg font-semibold">Opname: <span x-text="item.nama"></span></h3>
            <p class="text-xs text-gray-500">Isi jumlah hasil hitung fisik. Selisih dengan stok sistem (<b x-text="item.stok"></b>) dicatat sebagai penyesuaian. Gunakan ini juga untuk mengisi stok awal.</p>
            <div>
                <label class="block text-sm font-medium mb-1">Stok fisik *</label>
                <input type="number" name="stok_fisik" min="0" required class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Catatan</label>
                <input type="text" name="catatan" maxlength="150" placeholder="Contoh: stok awal / hasil opname bulanan" class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="close()" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold">Batal</button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Simpan</button>
            </div>
        </form>
    </div>

    {{-- Modal: stok minimum --}}
    <div x-show="m === 'minimum'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="close()">
        <form method="POST" action="{{ route('idcard.stok.minimum') }}" class="bg-white rounded-xl shadow-xl w-full max-w-md p-5 space-y-4">
            @csrf
            <input type="hidden" name="owner_id" value="{{ $owner->id }}">
            <input type="hidden" name="item_id" :value="item.id">
            <h3 class="text-lg font-semibold">Stok Minimum: <span x-text="item.nama"></span></h3>
            <p class="text-xs text-gray-500">Saat stok sama dengan atau di bawah angka ini, item ditandai "Perlu beli".</p>
            <div>
                <label class="block text-sm font-medium mb-1">Stok minimum *</label>
                <input type="number" name="min_stok" min="0" required :value="item.min" class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="close()" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold">Batal</button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection