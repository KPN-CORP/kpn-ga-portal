@extends('layouts.app-budget-sidebar')
@section('title', 'Tugas PIC')
@php
    $P = \App\Support\budget_s\BudgetPeran::class;
    $inisial = fn ($n) => mb_strtoupper(mb_substr(trim((string) $n) ?: '?', 0, 1));
    $warna = ['#2563eb', '#7c3aed', '#059669', '#d97706', '#be185d', '#0e7490', '#c2410c', '#4338ca'];
    $picAktif = $picHead->first(fn ($p) => (int) $p->pic_user_id === (int) $picId);
    $paramDept = $admin ? ['dept' => $head] : [];
    $totalNode = $kategori->count() + $kategori->sum(fn ($k) => $k->subs->count()) + $kategori->sum(fn ($k) => $k->subs->sum(fn ($s) => $s->items->count()));
@endphp
@section('content')
@push('styles')
    @include('budget_p.partials.gaya-halaman')
    <style>
        .bud .tg-grid{display:grid;grid-template-columns:280px 1fr;gap:16px;align-items:start}
        @media(max-width:900px){.bud .tg-grid{grid-template-columns:1fr}}
        .bud .tg-pic{display:flex;gap:10px;align-items:center;padding:10px 12px;border:1px solid var(--ln);border-radius:10px;margin-bottom:8px;color:inherit;text-decoration:none;background:#fff}
        .bud .tg-pic:hover{border-color:var(--ac)}
        .bud .tg-pic.on{border-color:var(--ac);background:#eff6ff;box-shadow:0 0 0 2px rgba(37,99,235,.12)}
        .bud .tg-pic b{display:block;line-height:1.2}.bud .tg-pic small{color:var(--mu);font-size:12px}
        .bud .tg-kat{border:1px solid var(--ln);border-radius:10px;margin-bottom:8px;background:#fff;overflow:hidden}
        .bud .tg-kat>summary{list-style:none;cursor:pointer;display:flex;flex-wrap:wrap;gap:6px 10px;align-items:center;padding:9px 12px;background:#f1f5f9}
        .bud .tg-kat>summary::-webkit-details-marker{display:none}
        .bud .tg-kat>summary:before{content:"\25B8";color:#64748b;transition:.12s}
        .bud .tg-kat[open]>summary:before{transform:rotate(90deg)}
        .bud .tg-sub{padding:7px 12px 7px 34px;border-top:1px solid #f1f5f9}
        .bud .tg-sub>.sb{display:flex;flex-wrap:wrap;gap:6px 10px;align-items:center;font-weight:600}
        .bud .tg-item{display:flex;flex-wrap:wrap;gap:6px 10px;align-items:center;padding:3px 0 3px 26px;font-weight:400;color:#334155}
        .bud .tg-kat label{display:inline-flex;gap:8px;align-items:center;cursor:pointer;margin:0}
        .bud .tg-kat input[type=checkbox]{width:16px;height:16px;accent-color:var(--ac)}
        .bud .tg-bar{position:sticky;bottom:0;background:rgba(255,255,255,.96);border-top:1px solid var(--ln);padding:10px 14px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;border-radius:0 0 12px 12px;backdrop-filter:blur(4px)}
    </style>
@endpush

<div class="pg-head">
    <div>
        <h2>Tugas PIC</h2>
        <p>Tentukan PIC mana yang memproses kategori, subkategori, dan item mana. Penugasan di kategori berlaku ke semua subkategori dan item di bawahnya; di subkategori berlaku ke semua itemnya. Dept Head selalu melihat seluruh data timnya.</p>
    </div>
    @if($admin && $kepalaList->isNotEmpty())
        <form method="get" class="pg-tool">
            <label class="lbl" for="tg-dept">Departemen</label>
            <select id="tg-dept" name="dept" data-cari onchange="this.form.submit()">
                @foreach($kepalaList as $id => $nm)<option value="{{ $id }}" @selected((int) $id === (int) $head)>{{ $nm }}</option>@endforeach
            </select>
        </form>
    @endif
</div>

@if(!$siap)
    <div class="pg-note dg"><b>Database belum disiapkan.</b> Jalankan <code>database/sql/2026_11_26_budget_pic_tugas.sql</code> sekali di database GA Portal. Sampai itu dilakukan, fitur ini nonaktif dan PIC tetap memakai aturan lama (melihat semua data departemennya).</div>
@endif

@if(!$head)
    <div class="pg-card"><div class="pg-empty"><b>Belum ada Dept Head aktif</b>Tambahkan Dept Head di menu Hak Akses terlebih dahulu.</div></div>
@else

{{-- Saklar pembatasan --}}
<div class="pg-card">
    <div class="bd2">
        <form method="post" action="{{ route('budgeting.tugas.mode') }}" style="display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:space-between">
            @csrf
            @if($admin)<input type="hidden" name="dept" value="{{ $head }}">@endif
            <input type="hidden" name="pic" value="{{ $picId }}">
            <div style="flex:1;min-width:260px">
                <b>Batasi PIC hanya pada bagian yang ditugaskan</b>
                <span class="pg-pill {{ $batasi ? 'ok' : 'off' }}" style="margin-left:8px">{{ $batasi ? 'Aktif' : 'Nonaktif' }}</span>
                <div class="lbl" style="margin-top:3px">
                    @if($batasi)
                        PIC hanya melihat dan mengerjakan kategori, subkategori, dan item yang ditugaskan kepadanya. Kategori Umum juga tidak terlihat kecuali ditugaskan.
                    @else
                        Saat ini PIC di departemen ini melihat dan mengelola semua data departemen. Penugasan di bawah tetap bisa disiapkan dulu dan baru berlaku setelah saklar ini dihidupkan.
                    @endif
                </div>
            </div>
            <label class="pg-sw" title="Hidupkan / matikan pembatasan PIC">
                <input type="hidden" name="batasi" value="0">
                <input type="checkbox" name="batasi" value="1" @checked($batasi) @disabled(!$siap) onchange="this.form.submit()">
                <i></i>
            </label>
        </form>
    </div>
</div>

@if($picHead->isEmpty())
    <div class="pg-card"><div class="pg-empty"><b>Belum ada PIC di departemen ini</b>
        PIC ditambahkan oleh Dept Head di menu <a href="{{ route('budgeting.akses') }}" style="color:var(--ac)">{{ $admin ? 'Hak Akses' : 'PIC Saya' }}</a>, lalu bisa ditugaskan di sini.</div></div>
@else
<div class="tg-grid">
    {{-- Daftar PIC --}}
    <div>
        <div class="lbl" style="margin-bottom:8px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">PIC ({{ $picHead->count() }})</div>
        @foreach($picHead as $ph)
            @php
                $pid = (int) $ph->pic_user_id; $r = $ringkas[$pid] ?? [];
                $nm = $ph->pic->name ?? ('#' . $pid);
                $ada = ($r['kat'] ?? 0) + ($r['sub'] ?? 0) + ($r['item'] ?? 0);
            @endphp
            <a class="tg-pic {{ $pid === (int) $picId ? 'on' : '' }}" href="{{ route('budgeting.tugas', $paramDept + ['pic' => $pid]) }}">
                <span class="pg-av" style="background:{{ $warna[$pid % count($warna)] }}">{{ $inisial($nm) }}</span>
                <span style="min-width:0">
                    <b>{{ $nm }}</b>
                    @if($ada)
                        <small>{{ $r['kat'] ?? 0 }} kategori &middot; {{ $r['sub'] ?? 0 }} sub &middot; {{ $r['item'] ?? 0 }} item</small>
                    @else
                        <small style="color:var(--wn)">Belum ada tugas</small>
                    @endif
                </span>
            </a>
        @endforeach
    </div>

    {{-- Pohon penugasan --}}
    <form method="post" action="{{ route('budgeting.tugas.simpan') }}" id="f-tugas" class="pg-card" style="margin:0">
        @csrf
        @if($admin)<input type="hidden" name="dept" value="{{ $head }}">@endif
        <input type="hidden" name="pic" value="{{ $picId }}">
        <div class="hd">
            <div class="pg-who">
                <span class="pg-av" style="background:{{ $warna[((int) $picId) % count($warna)] }}">{{ $inisial($picAktif->pic->name ?? '') }}</span>
                <div><b>{{ $picAktif->pic->name ?? '-' }}</b><small>{{ $picAktif->pic->email ?? '' }}</small></div>
            </div>
            <div class="pg-tool">
                <input type="search" placeholder="Cari kategori, subkategori, item..." oninput="pgCari(this, '.tg-kat')" aria-label="Cari">
                <button type="button" class="btn sm" onclick="tgSemua(true)">Pilih semua</button>
                <button type="button" class="btn sm" onclick="tgSemua(false)">Kosongkan</button>
            </div>
        </div>
        <div class="bd2" style="max-height:62vh;overflow:auto">
            @forelse($kategori as $k)
                @php $pk = $lain['kat'][$k->id] ?? []; $kCek = isset($terpilih['kat'][$k->id]); @endphp
                <details class="tg-kat" open>
                    <summary>
                        <label><input type="checkbox" name="kategori[]" value="{{ $k->id }}" data-l="k" data-k="{{ $k->id }}" @checked($kCek)><b>{{ $k->nama }}</b></label>
                        <span class="pg-pill pc">{{ $k->dept_head_user_id ? $P::namaDept($k->dept_head_user_id) : 'Umum' }}</span>
                        @foreach($pk as $np)<span class="pg-pill dept_head" title="PIC lain di kategori ini">{{ $np }}</span>@endforeach
                    </summary>
                    @foreach($k->subs as $s)
                        @php $ps = $lain['sub'][$s->id] ?? []; $sCek = $kCek || isset($terpilih['sub'][$s->id]); @endphp
                        <div class="tg-sub">
                            <div class="sb">
                                <label><input type="checkbox" name="sub[]" value="{{ $s->id }}" data-l="s" data-k="{{ $k->id }}" data-s="{{ $s->id }}" @checked($sCek)>{{ $s->nama }}</label>
                                <span class="lbl">{{ $s->items->count() }} item</span>
                                @foreach($ps as $np)<span class="pg-pill dept_head" title="PIC lain di subkategori ini">{{ $np }}</span>@endforeach
                            </div>
                            @foreach($s->items as $i)
                                @php $pi = $lain['item'][$i->id] ?? []; $iCek = $sCek || isset($terpilih['item'][$i->id]); @endphp
                                <label class="tg-item"><input type="checkbox" name="item[]" value="{{ $i->id }}" data-l="i" data-k="{{ $k->id }}" data-s="{{ $s->id }}" @checked($iCek)>
                                    <span>{{ $i->nama }}</span>
                                    @if($i->pt)<span class="lbl">{{ $i->pt }}</span>@endif
                                    @foreach($pi as $np)<span class="pg-pill dept_head" title="PIC lain di item ini">{{ $np }}</span>@endforeach
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </details>
            @empty
                <div class="pg-empty"><b>Belum ada kategori atau item</b>Tambahkan kategori di Master Data dan item di Rencana Budget terlebih dahulu.</div>
            @endforelse
        </div>
        <div class="tg-bar">
            <span class="lbl" id="tg-hitung">&nbsp;</span>
            <button class="btn pr" @disabled(!$siap || $kategori->isEmpty())>Simpan tugas {{ $picAktif->pic->name ?? 'PIC' }}</button>
        </div>
    </form>
</div>
@endif
@endif
@endsection

@push('scripts')
<script>
(function(){
    var f = document.getElementById('f-tugas');
    if(!f) return;
    function semua(sel){ return Array.prototype.slice.call(f.querySelectorAll(sel)); }
    function hitung(){
        var k = semua('input[data-l="k"]:checked').length, s = semua('input[data-l="s"]:checked').length, i = semua('input[data-l="i"]:checked').length;
        document.getElementById('tg-hitung').textContent = 'Dipilih: ' + k + ' kategori, ' + s + ' subkategori, ' + i + ' item';
    }
    function sinkronAtas(k, s){
        // Sub dicentang hanya bila semua itemnya dicentang; kategori dicentang hanya bila semua subnya dicentang
        var sub = f.querySelector('input[data-l="s"][data-s="'+s+'"]');
        var items = semua('input[data-l="i"][data-s="'+s+'"]');
        if(sub && items.length){ sub.checked = items.every(function(c){return c.checked}); }
        var kat = f.querySelector('input[data-l="k"][data-k="'+k+'"]');
        var subs = semua('input[data-l="s"][data-k="'+k+'"]');
        if(kat && subs.length){ kat.checked = subs.every(function(c){return c.checked}); }
    }
    f.addEventListener('change', function(e){
        var el = e.target, l = el.dataset.l;
        if(!l) return;
        if(l === 'k'){
            semua('input[data-k="'+el.dataset.k+'"]').forEach(function(c){ c.checked = el.checked; });
        } else if(l === 's'){
            semua('input[data-l="i"][data-s="'+el.dataset.s+'"]').forEach(function(c){ c.checked = el.checked; });
            sinkronAtas(el.dataset.k, el.dataset.s);
        } else {
            sinkronAtas(el.dataset.k, el.dataset.s);
        }
        hitung();
    });
    window.tgSemua = function(v){ semua('input[type=checkbox][data-l]').forEach(function(c){ c.checked = v; }); hitung(); };
    hitung();
})();
</script>
@endpush
