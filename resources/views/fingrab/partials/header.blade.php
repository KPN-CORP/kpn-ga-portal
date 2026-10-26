{{-- Judul, badge akses, pilihan hitung, dan filter (urutan: Bisnis unit, Perusahaan, Unit, Office area, Bulan, Layanan, Service, Karyawan) --}}
<div class="sim" hidden></div>
<div class="hd">
  <div>
    <h1>{{ $title }}</h1>
    <div style="margin-top:4px"><span class="bd" id="bd"></span><span class="mu" id="hc"></span></div>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    @if($hitung ?? false)
      <span class="mu">Tampilkan berdasarkan:</span>
      <span class="tg"><button id="m1" class="on">Nilai (Rp)</button><button id="m2">Jumlah transaksi</button></span>
    @else
      <span hidden><button id="m1"></button><button id="m2"></button></span>
    @endif
    <button id="rs">Atur ulang filter</button>
  </div>
</div>
<div class="p">
  <div class="fb" id="fbox"></div>
  <div class="ch" id="ch" style="margin-top:10px"></div>
</div>