{{-- params: $daftarKategori (hasil BudgetRingkas::perKategori), $ns (nama grup akordeon); opsional: $deptId (grafik dibatasi ke Dept itu). $tahun dan $filter diwarisi dari halaman. --}}
@php
    $dasarGrafik = array_filter(['tahun' => $tahun, 'bu' => $filter['bu'] ?? null, 'pt' => $filter['pt'] ?? null], fn ($v) => $v !== null && $v !== '');
    if (isset($deptId)) { $dasarGrafik['dept'] = $deptId; }
@endphp
    @foreach($daftarKategori as $k)
        @php $perSub = $k['items']->groupBy(fn ($i) => $i->sub->id)->sortBy(fn ($g) => [(int) $g->first()->sub->urut, mb_strtolower($g->first()->sub->nama)]); @endphp
        <details class="kat lv1 c{{ $loop->index % 5 }}" name="grup-kategori-{{ $ns }}">
            <summary>
                @include('budget_p.partials.baris-progres', [
                    'tag' => 'kategori', 'nama' => $k['nama'],
                    'sub' => $perSub->count() . ' sub kategori, ' . $k['items']->count() . ' item',
                    'budget' => $k['budget'], 'realisasi' => $k['realisasi'], 'persen' => $k['persen'], 'status' => $k['status'],
                    'grafik' => $dasarGrafik + ['kategori_id' => $k['id']],
                ])
            </summary>
            <div class="isi">
                @foreach($perSub as $grup)
                    @php
                        $sb = (float) $grup->sum('budget');
                        $sr = (float) $grup->sum('realisasi');
                    @endphp
                    <details class="sub" open>
                        <summary>
                            @include('budget_p.partials.baris-progres', [
                                'tag' => 'sub', 'nama' => $grup->first()->sub->nama,
                                'sub' => $grup->count() . ' item',
                                'budget' => $sb, 'realisasi' => $sr,
                                'persen' => $sb > 0 ? round($sr / $sb * 100, 1) : null,
                                'status' => \App\Support\budget_s\BudgetStatus::dari($sb, $sr),
                                'grafik' => $dasarGrafik + ['sub_id' => $grup->first()->sub->id],
                            ])
                        </summary>
                        <div class="isi-sub">
                            @foreach($grup->sortBy('nama') as $i)
                                <div class="itm">
                                    @include('budget_p.partials.baris-progres', [
                                        'nama' => $i->nama,
                                        'sub' => ($i->bu ?: '-') . ' / ' . ($i->pt ?: '-') . ' - PIC: ' . ($i->pic->name ?? 'belum ditunjuk'),
                                        'budget' => $i->budget, 'realisasi' => $i->realisasi, 'persen' => $i->persen, 'status' => $i->status,
                                        'itemId' => $i->id,
                                    ])
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        </details>
    @endforeach