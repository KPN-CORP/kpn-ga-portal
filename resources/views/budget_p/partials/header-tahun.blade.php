{{-- params: $judul, $tahun, $daftarTahun, $tahunRow (boleh null) --}}
<div class="top">
    <div class="row">
        <h2>{{ $judul }}</h2>
        @if($tahunRow?->terkunci)
            <span class="bd b-wn" title="Dept Head dan PIC tidak bisa mengubah budget bulanan. Admin tetap bisa.">Tahun {{ $tahun }} dikunci</span>
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
    <div class="msg wn">Tahun {{ $tahun }} belum terdaftar di Master. Minta Admin menambahkannya di menu Master sebelum mengisi budget atau tagihan.</div>
@endif
