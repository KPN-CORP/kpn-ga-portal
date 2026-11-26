@extends('layouts.app-budget-sidebar')
@php
    $admin = ($budgetPeran ?? null) === 'admin';
    $isPic = ($budgetPeran ?? null) === 'pic';
    $W = \App\Support\budget_s\BudgetWarna::class;
    $P = \App\Support\budget_s\BudgetPeran::class;
    // Tahun yang ditampilkan di keterangan: satu tahun bila difilter, selain itu semua tahun (lama ke baru)
    $tampilTahun = $tahunPilih ? [$tahunPilih] : collect($daftarTahun)->sort()->values()->all();
    $jumSub = $kategori->sum(fn ($k) => $k->subs->count());
    $jumTerkunci = $admin ? $tahunList->where('terkunci', true)->count() : 0;
@endphp
@section('title', 'Master Data')
@section('content')
@push('styles')
    @include('budget_p.partials.gaya-halaman')
@endpush

<div class="pg-head">
    <div>
        <h2>Master Data</h2>
        <p>
            @if($admin)
                Kelola tahun anggaran, kategori, dan subkategori seluruh departemen.
            @elseif($isPic)
                Anda bisa mengubah nama, urutan, dan status kategori atau subkategori yang ditugaskan kepada Anda. Menambah yang baru dilakukan Dept Head.
            @else
                Tambah dan atur kategori serta subkategori departemen Anda. Yang Anda buat hanya terlihat oleh Anda dan PIC yang ditugaskan, tidak bercampur dengan Dept Head lain.
            @endif
        </p>
    </div>
    @if($bolehTambah)
        <button type="button" class="btn pr" onclick="katModal()">+ Tambah kategori</button>
    @endif
</div>

<div class="pg-stats">
    <div class="pg-stat"><div class="ic"><i class="fas fa-folder-tree"></i></div><div><b>{{ $kategori->count() }}</b><span>Kategori{{ $tahunPilih ? ' di ' . $tahunPilih : '' }}</span></div></div>
    <div class="pg-stat"><div class="ic v"><i class="fas fa-sitemap"></i></div><div><b>{{ $jumSub }}</b><span>Subkategori</span></div></div>
    @if($admin)
        <div class="pg-stat"><div class="ic g"><i class="fas fa-calendar-check"></i></div><div><b>{{ $tahunList->count() }}</b><span>Tahun anggaran</span></div></div>
        <div class="pg-stat"><div class="ic a"><i class="fas fa-lock"></i></div><div><b>{{ $jumTerkunci }}</b><span>Tahun dikunci</span></div></div>
    @endif
</div>

@if($admin)
<div class="pg-tabs" role="tablist">
    <button type="button" class="pg-tab" data-tab="kategori" onclick="pgTab('kategori')">Kategori &amp; Subkategori<span class="n">{{ $kategori->count() }}</span></button>
    <button type="button" class="pg-tab" data-tab="tahun" onclick="pgTab('tahun')">Tahun Anggaran<span class="n">{{ $tahunList->count() }}</span></button>
</div>
@endif

{{-- ===================== KATEGORI & SUBKATEGORI ===================== --}}
<div class="pg-pane on" id="pane-kategori">
    <div class="pg-card">
        <div class="hd">
            <div class="pg-tool">
                <input type="search" placeholder="Cari kategori atau subkategori..." oninput="pgCari(this, '.pg-kat')" aria-label="Cari kategori atau subkategori">
                <form method="get" class="pg-tool">
                    <label class="lbl" for="mt-tahun">Tahun</label>
                    <select id="mt-tahun" name="tahun" data-cari onchange="this.form.submit()">
                        <option value="semua" @selected($tahunPilih === null)>Semua tahun</option>
                        @foreach($daftarTahun as $t)<option value="{{ $t }}" @selected((int) $tahunPilih === (int) $t)>{{ $t }}</option>@endforeach
                    </select>
                </form>
            </div>
            <span class="lbl">Label berwarna = ada item di tahun itu, abu-abu = belum ada. Pilih satu tahun untuk hanya melihat kategori dan subkategori di tahun tersebut.</span>
        </div>
        <div class="bd2">
            @forelse($kategori as $k)
                @php
                    $ubahK = $bolehUbahK[$k->id] ?? false;
                    $pemilik = $k->dept_head_user_id ? $P::namaDeptLengkap($k->dept_head_user_id) : 'Umum';
                @endphp
                <details class="pg-kat c{{ $loop->index % 5 }}" @if($loop->first && $kategori->count() <= 3) open @endif>
                    <summary>
                        <span class="pg-pill pc">{{ $pemilik }}</span>
                        <span class="nm">{{ $k->nama }}</span>
                        @if($k->aktif)<span class="pg-pill ok">Aktif</span>@else<span class="pg-pill off">Nonaktif</span>@endif
                        <span class="lbl">urut {{ $k->urut }} &middot; {{ $k->subs->count() }} subkategori</span>
                        @foreach($tampilTahun as $y)
                            @php $wy = $W::tahun((int) $y); $adaY = $k->subs->contains(fn ($x) => isset($tahunSub[$x->id][$y])); @endphp
                            <span class="pg-pill" style="{{ $adaY ? 'background:' . $wy['latar'] . ';color:' . $wy['warna'] : '' }}" title="{{ $adaY ? 'Ada item di ' . $y : 'Belum ada item di ' . $y }}">{{ $y }}</span>
                        @endforeach
                        @foreach(array_unique($picKat[$k->id] ?? []) as $np)<span class="pg-pill dept_head" title="PIC yang ditugaskan di kategori ini"><i class="fas fa-user-check"></i> {{ $np }}</span>@endforeach
                        <span class="sp"></span>
                        @if($ubahK)
                            <button type="button" class="btn sm" data-d="{{ json_encode(['id' => $k->id, 'nama' => $k->nama, 'urut' => $k->urut, 'aktif' => (bool) $k->aktif, 'dept' => $k->dept_head_user_id]) }}"
                                    onclick="event.preventDefault();event.stopPropagation();katModal(JSON.parse(this.dataset.d))">Ubah</button>
                        @endif
                        @if($admin)
                            <button type="button" class="btn sm dg" onclick="event.preventDefault();event.stopPropagation();konfirmasi({{ json_encode(route('budgeting.master.kategori.hapus', $k)) }}, {method:'DELETE', bahaya:true, tombol:'Hapus kategori', judul:'Hapus kategori', teks:{{ json_encode('Hapus kategori "' . $k->nama . '" secara permanen? Kategori yang masih punya subkategori tidak bisa dihapus.') }}})">Hapus</button>
                        @endif
                    </summary>

                    <div class="pg-sub hd"><span>Subkategori</span><span>Urut</span><span>Status</span><span>Item per tahun</span><span>PIC</span><span></span></div>
                    @forelse($k->subs as $s)
                        @php $ubahS = $bolehUbahS[$s->id] ?? false; @endphp
                        <div class="pg-sub">
                            <span><b>{{ $s->nama }}</b>@if(!empty($kepalaSub[$s->id]))<div class="lbl">{{ implode(', ', $kepalaSub[$s->id]) }}</div>@endif</span>
                            <span>{{ $s->urut }}</span>
                            <span>@if($s->aktif)<span class="pg-pill ok">Aktif</span>@else<span class="pg-pill off">Nonaktif</span>@endif</span>
                            <span>
                                @foreach($tampilTahun as $y)
                                    @php $wy = $W::tahun((int) $y); $jml = $tahunSub[$s->id][$y] ?? 0; @endphp
                                    <span class="pg-pill" style="{{ $jml > 0 ? 'background:' . $wy['latar'] . ';color:' . $wy['warna'] : '' }}">{{ $y }}: {{ $jml > 0 ? $jml . ' item' : '-' }}</span>
                                @endforeach
                            </span>
                            <span>
                                @forelse(array_unique($picSub[$s->id] ?? []) as $np)<span class="pg-pill dept_head"><i class="fas fa-user-check"></i> {{ $np }}</span>@empty<span class="lbl">{{ !empty($picKat[$k->id]) ? 'ikut kategori' : '-' }}</span>@endforelse
                            </span>
                            <span class="r">
                                @if($ubahS)
                                    <button type="button" class="btn sm" data-d="{{ json_encode(['id' => $s->id, 'kategori_id' => $k->id, 'nama' => $s->nama, 'urut' => $s->urut, 'aktif' => (bool) $s->aktif]) }}" onclick="subModal(JSON.parse(this.dataset.d))">Ubah</button>
                                @endif
                                @if($admin)
                                    <button type="button" class="btn sm dg" onclick="konfirmasi({{ json_encode(route('budgeting.master.sub.hapus', $s)) }}, {method:'DELETE', bahaya:true, tombol:'Hapus subkategori', judul:'Hapus subkategori', teks:{{ json_encode('Hapus subkategori "' . $s->nama . '" secara permanen? Yang masih dipakai item tidak bisa dihapus.') }}})">Hapus</button>
                                @endif
                            </span>
                        </div>
                    @empty
                        <div class="pg-sub"><span class="lbl" style="grid-column:1/-1">Belum ada subkategori{{ $tahunPilih ? ' di tahun ' . $tahunPilih : '' }}.</span></div>
                    @endforelse
                    @if($bolehTambah)
                        <div class="pg-foot">
                            <button type="button" class="btn sm pr" data-d="{{ json_encode(['kategori_id' => $k->id, 'urut' => $k->subs->count() + 1, 'kategori' => $k->nama]) }}" onclick="subModal(JSON.parse(this.dataset.d))">+ Tambah subkategori</button>
                        </div>
                    @endif
                </details>
            @empty
                <div class="pg-empty">
                    <b>Belum ada kategori{{ $tahunPilih ? ' di tahun ' . $tahunPilih : '' }}</b>
                    @if($isPic) Belum ada kategori yang ditugaskan kepada Anda. Hubungi Dept Head Anda.
                    @elseif($bolehTambah) Mulai dengan menekan "Tambah kategori".@endif
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ===================== TAHUN ANGGARAN (Admin) ===================== --}}
@if($admin)
<div class="pg-pane" id="pane-tahun">
    <div class="pg-card">
        <div class="hd">
            <h3>Tahun anggaran</h3>
            <form method="post" action="{{ route('budgeting.master.tahun') }}" class="pg-tool">
                @csrf
                <label class="lbl" for="th-baru">Tahun baru</label>
                <input id="th-baru" type="number" name="tahun" min="2000" max="2100" value="{{ now()->year + 1 }}" required style="width:100px">
                <button class="btn pr">Tambah tahun</button>
            </form>
        </div>
        <div class="bd2">
            <div class="pg-note">"Kunci" hanya menutup angka budget bulanan untuk Dept Head dan PIC. Realisasi (input manual dan Isi Form) tetap bisa dicatat, dan Admin tetap bisa mengubah budget. Dept Head dan Admin juga bisa membuat tahun lewat tombol "Tahun baru" di Rencana Budget, langsung diisi dari tahun lain.</div>
            @if($tahunList->isEmpty())
                <div class="pg-empty"><b>Belum ada tahun anggaran</b>Tambahkan tahun pertama lewat form di atas.</div>
            @else
            <div class="pg-years">
                @foreach($tahunList as $t)
                    @php $wy = $W::tahun((int) $t->tahun); @endphp
                    <div class="pg-year" style="--yc:{{ $wy['warna'] }}">
                        <div class="y">{{ $t->tahun }}</div>
                        <div class="st">
                            <span class="pg-pill" style="background:{{ $wy['latar'] }};color:{{ $wy['warna'] }}">{{ $wy['status'] }}</span>
                            @if($t->terkunci)<span class="pg-pill wn"><i class="fas fa-lock"></i> Budget bulanan dikunci</span>@else<span class="pg-pill ok">Terbuka</span>@endif
                            @if($t->terkunci)<div class="lbl" style="margin-top:6px">Oleh {{ $pengunci[$t->dikunci_oleh] ?? '-' }}, {{ $t->dikunci_pada?->format('d/m/Y H:i') }}</div>@endif
                        </div>
                        @if($t->terkunci)
                            <button type="button" class="btn sm" onclick="konfirmasi({{ json_encode(route('budgeting.master.buka', $t->tahun)) }}, {judul:'Buka kunci {{ $t->tahun }}', tombol:'Buka kunci', teks:'Buka kunci budget bulanan tahun {{ $t->tahun }}? Dept Head dan PIC bisa mengubah angka budget bulanan lagi.'})">Buka kunci</button>
                        @else
                            <button type="button" class="btn sm dg" onclick="konfirmasi({{ json_encode(route('budgeting.master.kunci', $t->tahun)) }}, {judul:'Kunci {{ $t->tahun }}', tombol:'Kunci budget bulanan', bahaya:true, teks:'Kunci budget bulanan tahun {{ $t->tahun }}? Dept Head dan PIC tidak bisa lagi mengubah angka budget bulanan. Pencatatan realisasi tetap bisa.'})">Kunci budget bulanan</button>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- ===================== DIALOG ===================== --}}
<dialog id="modal-kat" class="pg-dlg" aria-labelledby="mk-judul">
    <div class="dh"><b id="mk-judul">Kategori</b><button type="button" class="btn sm" onclick="tutup('modal-kat')">Tutup</button></div>
    <form method="post" action="{{ route('budgeting.master.simpan') }}" id="f-kat" class="db pg-f">
        @csrf
        <input type="hidden" name="jenis" value="kategori"><input type="hidden" name="id" value="">
        <label><span>Nama kategori</span><input type="text" name="nama" required maxlength="255"></label>
        <div class="two">
            <label><span>Pemilik (Dept Head)</span>
                @if($admin)
                    <select name="dept_head_user_id">
                        <option value="">Umum (semua)</option>
                        @foreach($pilihanPemilik as $id => $nm)<option value="{{ $id }}">{{ $nm }}</option>@endforeach
                    </select>
                @elseif($pilihanPemilik->count() > 1)
                    <select name="dept_head_user_id" required>
                        <option value="">Pilih Dept Head...</option>
                        @foreach($pilihanPemilik as $id => $nm)<option value="{{ $id }}">{{ $nm }}</option>@endforeach
                    </select>
                @else
                    <input type="text" value="{{ $pilihanPemilik->first() }}" disabled>
                    <input type="hidden" name="dept_head_user_id" value="{{ $pilihanPemilik->keys()->first() }}">
                @endif
            </label>
            <label><span>Urutan</span><input type="number" name="urut" min="0" value="{{ $kategori->count() + 1 }}"></label>
        </div>
        <label style="display:flex;align-items:center;gap:10px"><input type="hidden" name="aktif" value="0"><span class="pg-sw"><input type="checkbox" name="aktif" value="1" checked><i></i></span><b style="font-weight:600">Aktif</b></label>
        <div class="pg-acts"><button type="button" class="btn" onclick="tutup('modal-kat')">Batal</button><button class="btn pr">Simpan kategori</button></div>
    </form>
</dialog>

<dialog id="modal-sub" class="pg-dlg" aria-labelledby="ms-judul">
    <div class="dh"><b id="ms-judul">Subkategori</b><button type="button" class="btn sm" onclick="tutup('modal-sub')">Tutup</button></div>
    <form method="post" action="{{ route('budgeting.master.simpan') }}" id="f-sub" class="db pg-f">
        @csrf
        <input type="hidden" name="jenis" value="sub"><input type="hidden" name="id" value=""><input type="hidden" name="kategori_id" value="">
        <div class="lbl" id="ms-induk"></div>
        <div class="two">
            <label><span>Nama subkategori</span><input type="text" name="nama" required maxlength="255"></label>
            <label><span>Urutan</span><input type="number" name="urut" min="0" value="1"></label>
        </div>
        <label style="display:flex;align-items:center;gap:10px"><input type="hidden" name="aktif" value="0"><span class="pg-sw"><input type="checkbox" name="aktif" value="1" checked><i></i></span><b style="font-weight:600">Aktif</b></label>
        <div class="pg-acts"><button type="button" class="btn" onclick="tutup('modal-sub')">Batal</button><button class="btn pr">Simpan subkategori</button></div>
    </form>
</dialog>

@include('budget_p.partials.modal-konfirmasi')
@endsection

@push('scripts')
<script>
function isiForm(f, d){
    f.reset();
    Object.keys(d).forEach(function(k){
        var el = f.querySelector('[name="'+k+'"]:not([type=hidden][value="0"])');
        if(!el) return;
        if(el.type === 'checkbox'){ el.checked = !!d[k]; } else { el.value = d[k] === null || d[k] === undefined ? '' : d[k]; }
    });
}
function katModal(d){
    var f = document.getElementById('f-kat');
    var baru = !d;
    isiForm(f, baru ? {id:'', aktif:true} : {id:d.id, nama:d.nama, urut:d.urut, aktif:d.aktif, dept_head_user_id:d.dept});
    document.getElementById('mk-judul').textContent = baru ? 'Tambah kategori' : 'Ubah kategori';
    var pemilik = f.querySelector('select[name="dept_head_user_id"]');
    if(pemilik){ pemilik.disabled = ({{ $admin ? 'false' : 'true' }} && !baru); }
    buka('modal-kat');
}
function subModal(d){
    var f = document.getElementById('f-sub');
    var baru = !d.id;
    isiForm(f, {id:d.id||'', kategori_id:d.kategori_id, nama:d.nama||'', urut:d.urut||1, aktif: baru ? true : d.aktif});
    document.getElementById('ms-judul').textContent = baru ? 'Tambah subkategori' : 'Ubah subkategori';
    document.getElementById('ms-induk').textContent = d.kategori ? 'Di kategori: ' + d.kategori : '';
    buka('modal-sub');
}
</script>
@endpush
