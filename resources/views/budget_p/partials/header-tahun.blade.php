{{-- params: $judul, $tahun, $daftarTahun, $tahunRow (boleh null) --}}
<div class="top">
    <div class="row">
        <h2>{{ $judul }}</h2>
        @if($tahunRow?->terkunci)
            <span class="bd b-wn" title="Dept Head dan PIC tidak bisa mengubah budget bulanan. Realisasi tetap bisa dicatat. Admin tetap bisa mengubah.">Budget bulanan {{ $tahun }} dikunci</span>
        @endif
    </div>
    <form method="get" class="row">
        @foreach(request()->except('tahun', 'page') as $k => $v)
            @if(is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
        <label class="lbl" for="pilih-tahun">Tahun anggaran</label>
        <select id="pilih-tahun" name="tahun" onchange="this.form.submit()">
            @foreach($daftarTahun as $t)
                <option value="{{ $t }}" @selected((int) $t === (int) $tahun)>{{ $t }}</option>
            @endforeach
        </select>
    </form>
</div>
@if(!$tahunRow)
    <div class="msg wn">Tahun {{ $tahun }} belum dibuat. Admin atau Dept Head bisa membuatnya lewat tombol "Tahun baru" di Rencana Budget (bisa langsung diisi dari tahun lain). Selama belum dibuat, budget dan realisasi belum bisa diisi.</div>
@endif
