@extends('layouts.app-budget-sidebar')
@php
    $admin = ($budgetPeran ?? null) === 'admin';
    // Tahun yang ditampilkan di keterangan: satu tahun bila difilter, selain itu semua tahun (lama ke baru)
    $tampilTahun = $tahunPilih ? [$tahunPilih] : collect($daftarTahun)->sort()->values()->all();
@endphp
@section('title', $admin ? 'Master' : 'Kategori baru')
@section('content')
<div class="top"><h2>{{ $admin ? 'Master budget' : 'Tambah kategori dan subkategori' }}</h2></div>

@if($admin)
<div class="card">
    <h3>Tahun anggaran</h3>
    <div class="scroll">
    <table>
        <tr><th>Tahun</th><th>Status</th><th>Dikunci oleh</th><th></th></tr>
        @forelse($tahunList as $t)
            <tr>
                <td><b>{{ $t->tahun }}</b></td>
                <td>@if($t->terkunci)<span class="bd b-wn">Dikunci</span>@else<span class="bd b-ok">Terbuka</span>@endif</td>
                <td>@if($t->terkunci){{ $pengunci[$t->dikunci_oleh] ?? '-' }}, {{ $t->dikunci_pada?->format('d/m/Y H:i') }}@else - @endif</td>
                <td class="n">
                    @if($t->terkunci)
                        <form method="post" action="{{ route('budgeting.master.buka', $t->tahun) }}" style="display:inline">@csrf<button class="btn sm">Buka kunci</button></form>
                    @else
                        <form method="post" action="{{ route('budgeting.master.kunci', $t->tahun) }}" style="display:inline" onsubmit="return confirm('Kunci tahun {{ $t->tahun }}? Dept Head dan PIC tidak bisa lagi mengubah budget bulanan.')">@csrf<button class="btn sm dg">Kunci tahun</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="lbl">Belum ada tahun anggaran. Tambahkan di bawah.</td></tr>
        @endforelse
    </table>
    </div>
    <form method="post" action="{{ route('budgeting.master.tahun') }}" class="row" style="margin-top:12px">
        @csrf
        <label class="row"><span class="lbl">Tahun baru</span><input type="number" name="tahun" min="2000" max="2100" value="{{ now()->year + 1 }}" required style="width:100px"></label>
        <button class="btn pr">Tambah tahun</button>
    </form>
    <p class="lbl">Kunci hanya menutup perubahan budget bulanan untuk Dept Head dan PIC. Tagihan tetap bisa diinput, dan Admin tetap bisa mengubah.</p>
</div>
@else
<p class="lbl" style="margin-bottom:14px">Anda bisa menambah kategori dan subkategori baru. Kategori yang Anda buat hanya terlihat oleh Anda dan PIC Anda, tidak bercampur dengan Dept Head lain. Mengubah nama, urutan, status, atau menghapus dilakukan Admin.</p>
@endif

<div class="card">
    <h3>Kategori dan subkategori</h3>
    <form method="get" class="fb" style="padding:0 0 12px">
        <label><span class="lbl">Tahun</span>
            <select name="tahun" data-cari onchange="this.form.submit()">
                <option value="semua" @selected($tahunPilih === null)>Semua tahun</option>
                @foreach($daftarTahun as $t)<option value="{{ $t }}" @selected((int) $tahunPilih === (int) $t)>{{ $t }}</option>@endforeach
            </select>
        </label>
    </form>
    <p class="lbl" style="margin-bottom:10px">Label hijau = ada item di tahun itu, abu-abu = belum ada. Pilih satu tahun untuk hanya melihat kategori dan subkategori di tahun tersebut.</p>
    @foreach($kategori as $k)
        <details class="kat">
            <summary><span class="tag t-sub" style="margin-right:6px">{{ $k->dept_head_user_id ? \App\Support\budget_s\BudgetPeran::namaDeptLengkap($k->dept_head_user_id) : 'Umum' }}</span> <b>{{ $k->nama }}</b> <span class="lbl">(urut {{ $k->urut }}, {{ $k->aktif ? 'aktif' : 'nonaktif' }}, {{ $k->subs->count() }} subkategori)</span>
                @foreach($tampilTahun as $y)
                    @if($k->subs->contains(fn ($x) => isset($tahunSub[$x->id][$y])))<span class="bd b-ok" style="margin-left:4px">{{ $y }}</span>@else<span class="bd b-mu" style="margin-left:4px">{{ $y }}</span>@endif
                @endforeach
            </summary>
            <div class="isi">
                @if($admin)
                <form method="post" action="{{ route('budgeting.master.simpan') }}" class="row" style="margin:6px 0 12px">
                    @csrf <input type="hidden" name="jenis" value="kategori"><input type="hidden" name="id" value="{{ $k->id }}">
                    <input name="nama" value="{{ $k->nama }}" required maxlength="255" aria-label="Nama kategori">
                    <input type="number" name="urut" value="{{ $k->urut }}" min="0" style="width:80px" aria-label="Urutan">
                    <select name="dept_head_user_id" aria-label="Pemilik kategori">
                        <option value="">Umum (semua)</option>
                        @foreach($pilihanPemilik as $id => $nm)<option value="{{ $id }}" @selected((int) $k->dept_head_user_id === (int) $id)>{{ $nm }}</option>@endforeach
                    </select>
                    <label><input type="hidden" name="aktif" value="0"><input type="checkbox" name="aktif" value="1" @checked($k->aktif)> Aktif</label>
                    <button class="btn sm pr">Simpan kategori</button>
                </form>
                <form method="post" action="{{ route('budgeting.master.kategori.hapus', $k) }}" onsubmit="return confirm('Hapus kategori ini?')" style="margin-bottom:10px">@csrf @method('DELETE')<button class="btn sm dg">Hapus kategori</button></form>
                @endif

                @foreach($k->subs as $s)
                    @if(!$admin)
                    <div class="row" style="border-top:1px solid var(--ln);padding:6px 0">
                        <span>{{ $s->nama }}</span> <span class="lbl">({{ $s->aktif ? 'aktif' : 'nonaktif' }})</span>
                    </div>
                    @else
                    <div class="row" style="border-top:1px solid var(--ln);padding:6px 0">
                        <form method="post" action="{{ route('budgeting.master.simpan') }}" class="row">
                            @csrf <input type="hidden" name="jenis" value="sub"><input type="hidden" name="id" value="{{ $s->id }}"><input type="hidden" name="kategori_id" value="{{ $k->id }}">
                            <input name="nama" value="{{ $s->nama }}" required maxlength="255" aria-label="Nama subkategori">
                            <input type="number" name="urut" value="{{ $s->urut }}" min="0" style="width:80px" aria-label="Urutan">
                            <label><input type="hidden" name="aktif" value="0"><input type="checkbox" name="aktif" value="1" @checked($s->aktif)> Aktif</label>
                            <button class="btn sm">Simpan</button>
                        </form>
                        <form method="post" action="{{ route('budgeting.master.sub.hapus', $s) }}" onsubmit="return confirm('Hapus subkategori ini?')">@csrf @method('DELETE')<button class="btn sm dg">Hapus</button></form>
                    </div>
                    @endif
                    <div class="lbl" style="margin:0 0 8px;display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                        @foreach($tampilTahun as $y)
                            @if(($tahunSub[$s->id][$y] ?? 0) > 0)<span class="bd b-ok">{{ $y }}: {{ $tahunSub[$s->id][$y] }} item</span>@else<span class="bd b-mu">{{ $y }}: belum ada</span>@endif
                        @endforeach
                        @if(!empty($kepalaSub[$s->id]))<span>Dept: {{ implode(', ', $kepalaSub[$s->id]) }}</span>@endif
                    </div>
                @endforeach

                <form method="post" action="{{ route('budgeting.master.simpan') }}" class="row" style="border-top:1px solid var(--ln);padding-top:10px">
                    @csrf <input type="hidden" name="jenis" value="sub"><input type="hidden" name="kategori_id" value="{{ $k->id }}"><input type="hidden" name="aktif" value="1">
                    <input name="nama" placeholder="Subkategori baru" required maxlength="255">
                    <input type="number" name="urut" value="{{ $k->subs->count() + 1 }}" min="0" style="width:80px" aria-label="Urutan">
                    <button class="btn sm pr">Tambah subkategori</button>
                </form>
            </div>
        </details>
    @endforeach

    <form method="post" action="{{ route('budgeting.master.simpan') }}" class="row" style="margin-top:14px">
        @csrf <input type="hidden" name="jenis" value="kategori"><input type="hidden" name="aktif" value="1">
        <input name="nama" placeholder="Kategori baru" required maxlength="255">
        <input type="number" name="urut" value="{{ $kategori->count() + 1 }}" min="0" style="width:80px" aria-label="Urutan">
        @if($admin || $pilihanPemilik->count() > 1)
            <select name="dept_head_user_id" aria-label="Untuk Dept Head" @if(!$admin) required @endif>
                @if($admin)<option value="">Umum (semua)</option>@else<option value="">Untuk Dept Head...</option>@endif
                @foreach($pilihanPemilik as $id => $nm)<option value="{{ $id }}">{{ $nm }}</option>@endforeach
            </select>
        @elseif($pilihanPemilik->count() === 1)
            <input type="hidden" name="dept_head_user_id" value="{{ $pilihanPemilik->keys()->first() }}">
        @endif
        <button class="btn pr">Tambah kategori</button>
    </form>
</div>
@endsection