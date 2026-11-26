{{-- params: $judul, $tahun, $daftarTahun, $tahunRow (boleh null). Tahun diberi warna tetap + label status supaya user langsung ngeh sedang membuka tahun berapa. --}}
@php $w = \App\Support\budget_s\BudgetWarna::tahun((int) $tahun); @endphp
@once
@push('styles')
<style>
    .bud .thn-top{--thn:#1d4ed8;--thn-bg:#dbeafe;background:linear-gradient(90deg,var(--thn-bg),rgba(255,255,255,0) 70%);border:1px solid var(--thn-bg);border-left:6px solid var(--thn);border-radius:10px;padding:10px 16px}
    .bud .thn-top h2{margin:0}
    .bud .thn-chip{display:inline-flex;align-items:center;gap:6px;background:var(--thn);color:#fff;font-weight:700;font-size:15px;letter-spacing:.02em;padding:3px 12px;border-radius:999px;box-shadow:0 1px 3px rgba(15,23,42,.25)}
    .bud .thn-status{font-size:12px;font-weight:600;color:var(--thn);background:#fff;border:1px solid var(--thn);padding:1px 9px;border-radius:999px}
    .bud .thn-top select{border:2px solid var(--thn);color:var(--thn);font-weight:700;border-radius:8px;background:#fff}
    .bud .thn-top .lbl{color:var(--thn);font-weight:600}
</style>
@endpush
@endonce
<div class="top thn-top" style="--thn:{{ $w['warna'] }};--thn-bg:{{ $w['latar'] }}">
    <div class="row">
        <h2>{{ $judul }}</h2>
        <span class="thn-chip" title="Anda sedang membuka tahun anggaran {{ $tahun }}">TA {{ $tahun }}</span>
        <span class="thn-status">{{ $w['status'] }}</span>
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
