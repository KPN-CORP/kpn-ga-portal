{{-- params: $tahun, $filter, opsional: $kategoriList, $buList, $pakaiKategori, $pakaiBu, $pakaiQ --}}
<form method="get" class="card row" style="padding:10px 14px">
    <input type="hidden" name="tahun" value="{{ $tahun }}">
    @if(!empty($pakaiKategori))
        <label class="row"><span class="lbl">Kategori</span>
            <select name="kategori_id" onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach($kategoriList as $k)
                    <option value="{{ $k->id }}" @selected((string) ($filter['kategori_id'] ?? '') === (string) $k->id)>{{ $k->nama }}</option>
                @endforeach
            </select>
        </label>
    @endif
    @if(!empty($pakaiBu))
        <label class="row"><span class="lbl">BU</span>
            <select name="bu" onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach($buList as $b)
                    <option @selected(($filter['bu'] ?? '') === $b)>{{ $b }}</option>
                @endforeach
            </select>
        </label>
    @endif
    @if(!empty($pakaiQ))
        <label class="row"><span class="lbl">Cari item</span>
            <input type="search" name="q" value="{{ $filter['q'] ?? '' }}" placeholder="Nama item">
        </label>
        <button class="btn sm">Cari</button>
    @endif
    @if(($filter['bu'] ?? null) || ($filter['kategori_id'] ?? null) || ($filter['q'] ?? null))
        <a class="btn sm" href="{{ url()->current() }}?tahun={{ $tahun }}">Hapus filter</a>
    @endif
</form>
