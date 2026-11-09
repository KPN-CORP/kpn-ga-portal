@extends('layouts.app-budget-sidebar')
@section('title', 'Kategori')
@section('content')
@php $F = \App\Support\budget_s\BudgetFormat::class; @endphp

@include('budget_p.partials.header-tahun', ['judul' => 'Kategori budget'])

<div class="grid2 grid-kat">
    <div class="kat-nav">
        @forelse($kategori as $k)
            <details class="kat" @if($subAktif && $subAktif->kategori_id === $k->id) open @endif>
                <summary><b>{{ $k->nama }}</b></summary>
                <div class="isi">
                    @forelse($k->subs as $s)
                        <a class="li" style="{{ $subAktif && $subAktif->id === $s->id ? 'font-weight:700;background:var(--bar)' : '' }}"
                           href="{{ route('budgeting.kategori', ['tahun' => $tahun, 'sub' => $s->id]) }}">
                            {{ $s->nama }} <span class="lbl">({{ $jumlah[$s->id] ?? 0 }} item)</span>
                        </a>
                    @empty
                        <div class="lbl">Belum ada subkategori.</div>
                    @endforelse
                </div>
            </details>
        @empty
            @include('budget_p.partials.kosong', ['pesan' => 'Belum ada kategori dan item di tahun ' . $tahun . '. Ambil dari tahun lain lewat "Salin dari tahun lain" di halaman Budget bulanan.'])
        @endforelse
    </div>

    <div class="kat-det">
        @if(!$subAktif)
            @include('budget_p.partials.kosong', ['pesan' => 'Pilih subkategori di sebelah kiri untuk melihat item-itemnya.'])
        @else
            <div class="card kat-card">
                <h3>{{ $subAktif->kategori->nama }} / {{ $subAktif->nama }}</h3>
                @if($items->isEmpty())
                    <p class="lbl">Tidak ada item yang bisa Anda lihat di subkategori ini.</p>
                @else
                    <div class="tbl-beku">
                    <table>
                        <thead><tr><th class="beku">Item</th><th>BU / PT</th><th>PIC</th><th class="n">Budget</th><th class="n">Realisasi</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($items as $i)
                            <tr style="cursor:pointer" tabindex="0" onclick="rincian({{ $i->id }}, {{ $tahun }})" onkeydown="if(event.key==='Enter')rincian({{ $i->id }}, {{ $tahun }})">
                                <td class="beku"><b>{{ $i->nama }}</b></td>
                                <td>{{ $i->bu ?: '-' }} / {{ $i->pt ?: '-' }}</td>
                                <td>{{ $i->pic->name ?? '-' }}</td>
                                <td class="n">{{ $F::rupiah($i->budget) }}</td>
                                <td class="n">{{ $F::rupiah($i->realisasi) }}</td>
                                <td><span class="bd {{ $i->status['kelas'] }}">{{ $F::persen($i->persen) }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                        @php
                            $tb = (float) $items->sum('budget'); $tr = (float) $items->sum('realisasi');
                            $st = \App\Support\budget_s\BudgetStatus::dari($tb, $tr);
                        @endphp
                        <tfoot><tr>
                            <th class="beku">Total {{ $items->count() }} item</th><th></th><th></th>
                            <th class="n">{{ $F::rupiah($tb) }}</th><th class="n">{{ $F::rupiah($tr) }}</th>
                            <th><span class="bd {{ $st['kelas'] }}">{{ $F::persen($tb > 0 ? round($tr / $tb * 100, 1) : null) }}</span></th>
                        </tr></tfoot>
                    </table>
                    </div>
                    <p class="lbl">Klik baris untuk membuka rincian bulanan item.</p>
                @endif
            </div>
        @endif
    </div>
</div>

@include('budget_p.partials.modal-rincian')
@endsection