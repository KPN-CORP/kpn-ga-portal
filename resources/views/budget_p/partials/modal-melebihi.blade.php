{{-- params: $melebihi (daftar item yang melebihi budget), $tahun, $peran. Dibuka dari teks "N melebihi budget" di kartu Pemakaian. --}}
@php $F = \App\Support\budget_s\BudgetFormat::class; @endphp
<dialog id="modal-melebihi" aria-labelledby="melebihi-judul" style="width:min(1100px,96vw)">
    <div class="dh"><b id="melebihi-judul">Item yang melebihi budget ({{ $tahun }})</b><button type="button" class="btn sm" onclick="tutup('modal-melebihi')">Tutup</button></div>
    <div class="db">
        @if(empty($melebihi) || count($melebihi) === 0)
            <p class="lbl">Tidak ada item yang melebihi budget.</p>
        @else
            <p class="lbl" style="margin-bottom:8px">{{ count($melebihi) }} item, diurutkan dari kelebihan terbesar. Total kelebihan {{ $F::rupiah(collect($melebihi)->sum('lebih')) }}. Mengikuti filter yang sedang dipakai.</p>
            <div class="card scroll tbl-beku" style="padding:0;max-height:62vh;min-height:0">
                <table>
                    <thead><tr>
                        <th class="beku" style="min-width:230px;max-width:280px">Item</th>
                        @if(($peran ?? null) === 'admin')<th>Dept</th>@endif
                        <th>PT</th><th class="n">Budget</th><th class="n">Realisasi</th><th class="n">Lebih</th><th class="n">%</th><th></th>
                    </tr></thead>
                    <tbody>
                    @foreach($melebihi as $m)
                        <tr>
                            <td class="beku" style="min-width:230px;max-width:280px"><b>{{ $m['nama'] }}</b><div class="lbl">{{ $m['kategori'] }} / {{ $m['sub'] }}</div></td>
                            @if(($peran ?? null) === 'admin')<td>{{ $m['dept'] }}</td>@endif
                            <td>{{ $m['pt'] ?: '-' }}</td>
                            <td class="n">{{ $F::rupiah($m['budget']) }}</td>
                            <td class="n">{{ $F::rupiah($m['realisasi']) }}</td>
                            <td class="n b-er"><b>{{ $F::rupiah($m['lebih']) }}</b></td>
                            <td class="n">{{ $m['persen'] === null ? 'budget 0' : $F::persen($m['persen']) }}</td>
                            <td class="n"><button type="button" class="btn sm" data-g="{{ json_encode(['tahun' => $tahun, 'item_id' => $m['id']]) }}" onclick="grafik(JSON.parse(this.dataset.g))">Grafik</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</dialog>
