{{-- params: $tahun, $filter, opsional: $kategoriList, $buList, $ptList, $pakaiKategori, $pakaiBu, $pakaiPt, $pakaiQ. Semua dropdown bisa diketik (data-cari). --}}
@php
    // Pengaman: kalau controller belum mengirim daftar, ambil sendiri supaya halaman tidak error
    $kategoriList = $kategoriList ?? collect();
    $buList = $buList ?? [];
    $ptList = $ptList ?? (method_exists(\App\Support\budget_s\BudgetRingkas::class, 'ptTersedia')
        ? \App\Support\budget_s\BudgetRingkas::ptTersedia(auth()->user()) : []);
    $namaKembar = $kategoriList->groupBy(fn ($x) => mb_strtolower($x->nama))->filter(fn ($g) => $g->count() > 1)->keys();
    $adaFilter = ($filter['bu'] ?? null) || ($filter['pt'] ?? null) || ($filter['kategori_id'] ?? null) || ($filter['sub_id'] ?? null) || (($filter['q'] ?? '') !== '' && ($filter['q'] ?? null) !== null);
@endphp
<form method="get" class="card fb">
    <input type="hidden" name="tahun" value="{{ $tahun }}">
    @if(!empty($pakaiKategori))
        <label><span class="lbl">Kategori</span>
            <select name="kategori_id" data-cari onchange="if(this.form.sub_id) this.form.sub_id.value=''; this.form.submit()">
                <option value="">Semua</option>
                @foreach($kategoriList as $k)
                    <option value="{{ $k->id }}" @selected((string) ($filter['kategori_id'] ?? '') === (string) $k->id)>{{ $k->nama }}@if($namaKembar->contains(mb_strtolower($k->nama))) - {{ \App\Support\budget_s\BudgetPeran::namaDept($k->dept_head_user_id) }}@endif</option>
                @endforeach
            </select>
        </label>
    @endif
    @if(!empty($pakaiSub))
        <label><span class="lbl">Sub</span>
            <select name="sub_id" data-cari onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach($subFilterList as $sb)
                    <option value="{{ $sb->id }}" @selected((string) ($filter['sub_id'] ?? '') === (string) $sb->id)>{{ $sb->nama }}@if(empty($filter['kategori_id'])) - {{ $sb->kategori->nama }}@endif</option>
                @endforeach
            </select>
        </label>
    @endif
    @if(!empty($pakaiBu))
        <label><span class="lbl">BU</span>
            <select name="bu" data-cari onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach($buList as $b)
                    <option value="{{ $b }}" @selected(($filter['bu'] ?? '') === $b)>{{ $b }}</option>
                @endforeach
            </select>
        </label>
    @endif
    @if(!empty($pakaiPt))
        <label><span class="lbl">PT</span>
            <select name="pt" data-cari onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach($ptList as $p)
                    <option value="{{ $p }}" @selected(($filter['pt'] ?? '') === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </label>
    @endif
    @if(!empty($pakaiQ))
        <label><span class="lbl">Cari item</span>
            <input type="search" name="q" value="{{ $filter['q'] ?? '' }}" placeholder="Nama item, lalu Enter">
        </label>
    @endif
    @if(!empty($pakaiQ) || $adaFilter)
        <div class="fb-act">
            @if(!empty($pakaiQ))<button class="btn pr">Cari</button>@endif
            @if($adaFilter)<a class="btn" href="{{ url()->current() }}?tahun={{ $tahun }}">Hapus filter</a>@endif
        </div>
    @endif
</form>
