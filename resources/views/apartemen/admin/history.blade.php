{{-- Riwayat Penghuni Mess · diperbarui 13/11/2026 --}}
@extends('layouts.app_apartadmin_sidebar')
@section('content')
@php
    // URL chip: hapus filter tertentu (dan reset halaman). Rentang tanggal kembali ke bulan berjalan.
    $removeUrl = function (array $keys) {
        return route('apartemen.admin.history', request()->except(array_merge($keys, ['page'])));
    };
    // Rentang bulan berjalan (default) tidak dihitung sebagai filter aktif.
    $hasFilter = request()->filled('search') || request()->filled('bisnis_unit') || !$isDefaultRange;
    $nFilter   = (request()->filled('bisnis_unit') ? 1 : 0) + (!$isDefaultRange ? 1 : 0);

    // Istilah: Check-in / Pindah unit / Check-out.
    $stepLabel = ['CHECKIN' => 'Check-in', 'DIPINDAH' => 'Pindah unit', 'SELESAI' => 'Check-out', 'DIBATALKAN' => 'Dibatalkan', 'BOOKING' => 'Jadwal disetujui'];
    $stepTone  = ['CHECKIN' => 'blue', 'DIPINDAH' => 'indigo', 'SELESAI' => 'green', 'DIBATALKAN' => 'red', 'BOOKING' => 'gray'];
    $tagLabel  = ['SELESAI' => 'Sudah check-out', 'DIBATALKAN' => 'Dibatalkan'];

    // Catatan "Backfill ..." dibuat sistem: jamnya (21:00 / 19:00) bukan jam sebenarnya,
    // jadi jam tidak ditampilkan dan catatannya disembunyikan.
    $isBackfill = fn($e) => str_starts_with(trim((string) ($e->catatan ?? '')), 'Backfill');
    $showNote   = fn($c) => !empty($c) && !str_starts_with(trim((string) $c), 'Backfill');
    $fmtEv      = fn($e) => $e->created_at->format('d/m/Y') . ($isBackfill($e) ? '' : ' · ' . $e->created_at->format('H:i'));

    // Nomor HP: diformat sekali per baris.
    $formatHp = function ($raw) {
        $raw = trim((string) $raw);
        if ($raw === '' || $raw === '-') return null;
        $d = preg_replace('/\D/', '', $raw);
        if (strlen($d) < 10) return $raw;
        if (substr($d, 0, 2) === '62') return '+62 ' . substr($d, 2, 3) . '-' . substr($d, 5, 4) . '-' . substr($d, 9);
        if (substr($d, 0, 1) === '0')  return substr($d, 0, 4) . '-' . substr($d, 4, 4) . '-' . substr($d, 8);
        return $raw;
    };
    // Avatar inisial dengan warna stabil per nama.
    $initials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $out = '';
        foreach (array_slice($parts, 0, 2) as $w) { $out .= mb_strtoupper(mb_substr($w, 0, 1)); }
        return $out ?: '?';
    };
    $tint = function ($name) {
        $h = crc32((string) $name) % 360;
        return "background:hsl($h 70% 93%);color:hsl($h 50% 30%)";
    };
    // Tanggal mulai dari kolom periode ("12/11/2026 - 20/11/2026"), dipakai bila Check-in tidak tercatat.
    $periodeStart = function ($periode) {
        $first = trim(explode(' - ', (string) $periode)[0]);
        return preg_match('#^\d{2}/\d{2}/\d{4}$#', $first) ? $first : null;
    };
    $toDate = fn($s) => $s ? \Carbon\Carbon::createFromFormat('d/m/Y', $s)->startOfDay() : null;
    // Jadwal yang disetujui untuk satu penempatan: mulai dari catatan pertama, selesai dari catatan lain
    // (catatan "Dipindah" menyimpan tanggal pindah di bagian akhir periode, jadi tidak dipakai untuk tanggal selesai).
    $stayRange = function (array $events) {
        $re = '#^\d{2}/\d{2}/\d{4}$#';
        $mulai = null; $selesai = null;
        foreach ($events as $e) {
            $parts = array_map('trim', explode(' - ', (string) $e->periode));
            if ($mulai === null && isset($parts[0]) && preg_match($re, $parts[0])) $mulai = $parts[0];
            if ($e->status_selesai !== 'DIPINDAH' && isset($parts[1]) && preg_match($re, $parts[1])) $selesai = $parts[1];
        }
        return [$mulai, $selesai];
    };
    $malamText = fn($n) => $n === null ? null : ($n === 0 ? 'kurang dari 1 malam' : $n . ' malam');
    $hariText  = fn($n) => $n . ' hari';
    // Selisih hari bertanda (positif bila $b setelah $a).
    $selisih   = fn($a, $b) => (int) $a->copy()->startOfDay()->diffInDays($b->copy()->startOfDay(), false);
@endphp

<div class="w-full">

    @if(session('success'))
        <div class="ap-flash ok" role="status"><i class="fas fa-check-circle mt-0.5"></i><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
        <div class="ap-flash err" role="alert"><i class="fas fa-exclamation-circle mt-0.5"></i><span>{{ session('error') }}</span></div>
    @endif
    @if($errors->any())
        <div class="ap-flash err" role="alert"><i class="fas fa-exclamation-circle mt-0.5"></i>
            <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <x-apartemen.page-header title="Riwayat Penghuni Mess" subtitle="Riwayat tinggal tiap penghuni, termasuk penghuni yang masih aktif: jadwal yang disetujui, check-in, pindah unit, dan check-out" />

    {{-- RINGKASAN: dua kartu sama lebar. Angka mengikuti periode (default: bulan berjalan) dan filter. --}}
    <div class="ap-kpis" style="--n:2">
        <x-apartemen.stat-card :label="'Total history pemesanan · ' . $periodeLabel" :value="$stats['total']" color="blue" :href="null" :active="false"><i class="fas fa-history"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card :label="'Dibatalkan · ' . $periodeLabel" :value="$stats['batal']" color="red" :href="null" :active="false"><i class="fas fa-ban"></i></x-apartemen.stat-card>
    </div>

    {{-- FILTER --}}
    <form action="{{ route('apartemen.admin.history') }}" method="GET" class="ap-card ap-filters p-3 md:p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2.5">
            <div class="sm:col-span-2 lg:col-span-5 flex gap-2">
                <div class="ap-search flex-1 min-w-0">
                    <i class="fas fa-search"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIK, mess, unit" class="ap-input" aria-label="Cari">
                    <button type="submit" class="ap-search-go">Cari</button>
                </div>
                <button type="button" class="ap-fbtn" aria-expanded="false" onclick="const f=this.closest('form');f.classList.toggle('open');this.setAttribute('aria-expanded',f.classList.contains('open'))">
                    <i class="fas fa-sliders-h"></i>Filter @if($nFilter)<span class="n">{{ $nFilter }}</span>@endif
                </button>
            </div>
            <div class="ap-fld lg:col-span-3">
                <select name="bisnis_unit" class="ap-input" onchange="this.form.submit()" aria-label="Bisnis unit">
                    <option value="">Semua bisnis unit</option>
                    @foreach($bisnisUnits as $bu)
                        <option value="{{ $bu->nama_bisnis_unit }}" {{ request('bisnis_unit') === $bu->nama_bisnis_unit ? 'selected' : '' }}>{{ $bu->nama_bisnis_unit }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Tanggal: kirim otomatis hanya bila tahun sudah wajar (>= 2000). Saat tahun masih diketik
                 ("0002", "0020", ...) browser memicu change, dan form tidak boleh terkirim dulu. --}}
            <div class="ap-fld sm:col-span-2 lg:col-span-4"><div class="grid grid-cols-2 gap-2">
                <input type="date" name="tanggal_mulai" value="{{ $rangeMulai }}" class="ap-input" onchange="if(!this.value||this.value.slice(0,4)>=2000)this.form.submit()" aria-label="Dari tanggal" title="Dari tanggal">
                <input type="date" name="tanggal_selesai" value="{{ $rangeSelesai }}" class="ap-input" onchange="if(!this.value||this.value.slice(0,4)>=2000)this.form.submit()" aria-label="Sampai tanggal" title="Sampai tanggal">
            </div></div>
        </div>

        @if($hasFilter)
            <div class="ap-filter-row">
                <span class="text-xs text-gray-500 mr-1">Filter aktif</span>
                @if(request('search'))
                    <a href="{{ $removeUrl(['search']) }}" class="chip">“{{ request('search') }}” <i class="fas fa-times"></i></a>
                @endif
                @if(request('bisnis_unit'))
                    <a href="{{ $removeUrl(['bisnis_unit']) }}" class="chip">{{ request('bisnis_unit') }} <i class="fas fa-times"></i></a>
                @endif
                @if(!$isDefaultRange)
                    <a href="{{ $removeUrl(['tanggal_mulai', 'tanggal_selesai']) }}" class="chip">
                        {{ $rangeMulai ? \Carbon\Carbon::parse($rangeMulai)->format('d/m/Y') : '…' }} – {{ $rangeSelesai ? \Carbon\Carbon::parse($rangeSelesai)->format('d/m/Y') : '…' }}
                        <i class="fas fa-times"></i>
                    </a>
                @endif
                <a href="{{ route('apartemen.admin.history') }}" class="ap-link ml-auto">Reset semua</a>
            </div>
        @endif
    </form>

    {{-- DAFTAR PENGHUNI: satu baris per orang --}}
    <div class="ap-card overflow-hidden">
        <div class="ap-listhead">
            <h3>Daftar penghuni</h3>
            <p><b>{{ number_format($histories->total()) }}</b> penghuni · {{ $periodeLabel }}{{ (request()->filled('search') || request()->filled('bisnis_unit')) ? ' · sesuai filter' : '' }}</p>
        </div>

        @if($histories->count() > 0)
        <div class="md:overflow-x-auto">
            <table class="ap-table ap-hist">
                <thead>
                    <tr>
                        <th>Penghuni</th>
                        <th>Mess &amp; Unit</th>
                        <th>Jadwal disetujui</th>
                        <th>Realisasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($histories as $h)
                    @php
                        $hp     = $formatHp($h->no_hp);
                        $stays  = $journeys[$h->id_karyawan] ?? [[$h]];
                        $stay   = $stays[0];
                        $older  = array_slice($stays, 1);

                        // Ringkasan penempatan terbaru.
                        $evs    = collect($stay);
                        $in     = $evs->firstWhere('status_selesai', 'CHECKIN');
                        $out    = $evs->firstWhere('status_selesai', 'SELESAI');
                        $cancel = $evs->firstWhere('status_selesai', 'DIBATALKAN');
                        $moves  = $evs->where('status_selesai', 'DIPINDAH')->count();
                        $lastEv = end($stay);
                        $lastSt = $lastEv->status_selesai;
                        $open   = !in_array($lastSt, ['SELESAI', 'DIBATALKAN'], true);   // belum check-out / belum ditutup
                        // Penempatan yang sedang berjalan (jadwal disetujui, unit saat ini, waktu check-in).
                        $bk       = $open ? ($bookings[$h->id_karyawan] ?? null) : null;
                        $inAt     = $in ? $in->created_at : ($bk['checkin_at'] ?? null);
                        $inFromBk = !$in && $inAt;                        // check-in ada di data penempatan, tidak di riwayat
                        $notYetIn = $open && $bk && !$bk['checkin_at'];   // jadwal sudah disetujui, belum check-in
                        if (!$open)          { $tagText = $tagLabel[$lastSt];        $tagTone = $stepTone[$lastSt]; }
                        elseif ($notYetIn)   { $tagText = 'Belum check-in';          $tagTone = 'yellow'; }
                        elseif ($inAt)       { $tagText = 'Belum check-out';         $tagTone = 'blue'; }
                        else                 { $tagText = 'Check-in belum tercatat'; $tagTone = 'yellow'; }

                        // Lama menginap (malam).
                        $days = null;
                        if ($inAt) {
                            $end  = $out ? $out->created_at : now();
                            $days = (int) abs($inAt->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()));
                        }

                        // Tanggal check-in / check-out. Jam hanya dipakai bila asli (bukan backfill).
                        $masukJadwal = false;
                        if ($inAt)   { $masuk = $inAt->format('d/m/Y'); }
                        elseif ($bk) { $masuk = $bk['mulai']->format('d/m/Y'); $masukJadwal = true; }
                        else         { $masuk = $periodeStart($stay[0]->periode) ?? '-'; $masukJadwal = $masuk !== '-'; }
                        $masukJam = null;
                        if ($in && !$isBackfill($in)) { $masukJam = $in->created_at->format('H:i'); }
                        elseif ($inFromBk)            { $masukJam = $inAt->format('H:i'); }
                        $keluar       = $out ? $out->created_at->format('d/m/Y') : null;
                        $keluarJam    = ($out && !$isBackfill($out)) ? $out->created_at->format('H:i') : null;
                        $keluarJadwal = (!$out && $bk && $bk['selesai']) ? $bk['selesai']->format('d/m/Y') : null;

                        // Jadwal disetujui + lama jadwal (malam).
                        // (Nama $stMulai/$stSelesai sengaja berbeda dari $rangeMulai/$rangeSelesai milik filter tanggal.)
                        [$stMulai, $stSelesai] = $stayRange($stay);
                        if ($bk) {
                            $jm = $bk['mulai']->copy()->startOfDay();
                            $js = $bk['selesai'] ? $bk['selesai']->copy()->startOfDay() : null;
                        } else {
                            $jm = $toDate($stMulai);
                            $js = $toDate($stSelesai);
                        }
                        $jadwal       = $jm ? $jm->format('d/m/Y') . ' – ' . ($js ? $js->format('d/m/Y') : '…') : null;
                        $jadwalMalam  = ($jm && $js) ? (int) abs($jm->diffInDays($js)) : null;

                        // Keterlambatan dibanding jadwal yang disetujui (hari).
                        //  - Check-in telat   : tanggal check-in tercatat setelah jadwal mulai.
                        //  - Check-out telat  : tanggal check-out (atau hari ini, bila belum check-out) setelah jadwal selesai.
                        $telatIn  = ($inAt && $jm) ? max(0, $selisih($jm, $inAt)) : 0;
                        $telatOut = 0;
                        if ($js) {
                            if ($out)       { $telatOut = max(0, $selisih($js, $out->created_at)); }
                            elseif ($inAt)  { $telatOut = max(0, $selisih($js, now())); }
                        }
                        // Sudah check-in, belum check-out, dan jadwal keluar sudah lewat -> merah.
                        if ($open && $inAt && $telatOut > 0) { $tagTone = 'red'; }

                        // Unit: unit awal -> pindah -> unit sekarang.
                        $messName = $bk ? $bk['apartemen'] : $lastEv->apartemen;
                        $unitPath = [];
                        foreach ($stay as $e) { if ($e->status_selesai === 'DIPINDAH') $unitPath[] = $e->unit; }
                        $unitFinal = $bk ? $bk['unit'] : ($lastSt === 'DIPINDAH' ? null : $lastEv->unit);
                        if ($unitFinal !== null) { $unitPath[] = $unitFinal; }
                        $unitText = $unitPath ? implode(' → ', $unitPath) . ($unitFinal === null ? ' → …' : '') : '-';
                        $cancelNoIn = $lastSt === 'DIBATALKAN' && !$in;

                        // Rincian hanya ditampilkan bila ada tambahan yang tidak ada di ringkasan:
                        // pindah unit, atau catatan. Check-in / Check-out polos sudah ada di ringkasan.
                        $detailEvents = $evs->filter(fn($e) => $e->status_selesai === 'DIPINDAH' || $showNote($e->catatan))->values();
                    @endphp
                    <tr class="ap-row">
                        {{-- PENGHUNI --}}
                        <td class="c-person">
                            <div class="ap-person">
                                <span class="ap-avatar" style="{{ $tint($h->nama) }}">{{ $initials($h->nama) }}</span>
                                <div class="min-w-0">
                                    <div class="ap-name">{{ $h->nama }}</div>
                                    <div class="ap-sub">{{ $h->id_karyawan }}@if($h->unit_kerja && $h->unit_kerja !== '-') · {{ $h->unit_kerja }}@endif</div>
                                    @if($hp || ($h->gol && $h->gol !== '-'))
                                    <div class="ap-sub">
                                        @if($hp)<a href="tel:{{ preg_replace('/[^\d+]/', '', $hp) }}" class="text-blue-600 hover:underline"><i class="fas fa-phone"></i>{{ $hp }}</a>@endif
                                        @if($h->gol && $h->gol !== '-')<span class="{{ $hp ? 'ml-2' : '' }}">Gol. {{ $h->gol }}</span>@endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- MESS & UNIT --}}
                        <td class="c-mess">
                            <div class="ap-name">{{ $messName }}</div>
                            <div class="ap-sub"><i class="fas fa-door-open"></i>Unit {{ $unitText }}</div>
                        </td>

                        {{-- JADWAL DISETUJUI --}}
                        <td class="c-jadwal">
                            @if($jadwal)
                                <div class="ap-date"><i class="far fa-calendar-check"></i>{{ $jadwal }}</div>
                                @if($jadwalMalam !== null)<div class="ap-sub">{{ $jadwalMalam }} malam</div>@endif
                            @else
                                <div class="ap-sub">Tidak tercatat</div>
                            @endif
                        </td>

                        {{-- REALISASI: Check-in, Check-out, lama menginap --}}
                        <td class="c-real">
                            @if($cancelNoIn)
                                <div class="ap-sum-line"><i class="fas fa-ban"></i>Tidak jadi check-in</div>
                                <div class="ap-sub">Dibatalkan {{ $cancel->created_at->format('d/m/Y') }}</div>
                                @if($showNote($cancel->catatan))
                                    <div class="ap-note ap-note-cancel"><i class="far fa-comment-dots"></i><span>{{ $cancel->catatan }}</span></div>
                                @endif
                            @else
                                <div class="ap-sum-line"><i class="fas fa-sign-in-alt"></i><span class="lbl">Check-in</span><b>{{ $masuk }}</b>@if($masukJam)<span class="jam">{{ $masukJam }}</span>@endif @if($masukJadwal)<em>(jadwal)</em>@endif</div>
                                <div class="ap-sum-line"><i class="fas fa-sign-out-alt"></i><span class="lbl">Check-out</span><b>{{ $keluar ?? 'belum' }}</b>@if($keluarJam)<span class="jam">{{ $keluarJam }}</span>@endif @if($keluarJadwal)<em>(jadwal {{ $keluarJadwal }})</em>@endif</div>
                                @if($days !== null)
                                    <div class="ap-sum-line"><i class="far fa-clock"></i><span class="lbl">{{ $out ? 'Menginap' : 'Sudah menginap' }}</span><b>{{ $malamText($days) }}</b></div>
                                @endif
                                @if($lastSt === 'DIBATALKAN' && $cancel && $showNote($cancel->catatan))
                                    <div class="ap-note ap-note-cancel"><i class="far fa-comment-dots"></i><span>{{ $cancel->catatan }}</span></div>
                                @endif
                            @endif

                            {{-- Rincian: hanya yang tidak ada di ringkasan (pindah unit / catatan) --}}
                            @if($detailEvents->count())
                                <details class="ap-more">
                                    <summary>Rincian ({{ $detailEvents->count() }})</summary>
                                    <ol class="ap-trail">
                                        @foreach($detailEvents as $e)
                                            <li class="t-{{ $stepTone[$e->status_selesai] ?? 'gray' }}">
                                                <span class="ap-trail-dot"></span>
                                                <div class="min-w-0">
                                                    <div class="ap-trail-top">
                                                        <b>{{ $stepLabel[$e->status_selesai] ?? $e->status_selesai }}</b>
                                                        <time title="{{ $isBackfill($e) ? '' : $e->created_at->format('d/m/Y H:i:s') }}">{{ $fmtEv($e) }}</time>
                                                    </div>
                                                    @if($e->status_selesai === 'DIPINDAH')
                                                        <div class="ap-sub">Dari Unit {{ $e->unit }}</div>
                                                    @endif
                                                    @if($showNote($e->catatan))
                                                        <div class="ap-note"><i class="far fa-comment-dots"></i><span>{{ $e->catatan }}</span></div>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ol>
                                </details>
                            @endif

                            {{-- Riwayat sebelumnya: satu baris ringkas per periode tinggal --}}
                            @if(count($older))
                                <details class="ap-prev">
                                    <summary>Riwayat sebelumnya ({{ count($older) }})</summary>
                                    @foreach($older as $oldStay)
                                        @php
                                            $oe   = collect($oldStay);
                                            $oIn  = $oe->firstWhere('status_selesai', 'CHECKIN');
                                            $oOut = $oe->firstWhere('status_selesai', 'SELESAI');
                                            $oCan = $oe->firstWhere('status_selesai', 'DIBATALKAN');
                                            [$om, $os] = $stayRange($oldStay);
                                            $oJs  = $toDate($os);
                                            $oLate = ($oOut && $oJs) ? max(0, $selisih($oJs, $oOut->created_at)) : 0;
                                            $oPath = [];
                                            foreach ($oldStay as $e) { if ($e->status_selesai === 'DIPINDAH') $oPath[] = $e->unit; }
                                            $oPath[] = end($oldStay)->status_selesai === 'DIPINDAH' ? '…' : end($oldStay)->unit;
                                        @endphp
                                        <div class="ap-prevline">
                                            <div class="ap-prevtop">
                                                <b>{{ $om ? $om . ' – ' . ($os ?? '…') : 'Jadwal tidak tercatat' }}</b>
                                                <span>Unit {{ implode(' → ', $oPath) }}</span>
                                            </div>
                                            <div class="ap-sub">
                                                @if($oCan && !$oIn)
                                                    Dibatalkan {{ $oCan->created_at->format('d/m/Y') }}
                                                @else
                                                    Check-in {{ $oIn ? $oIn->created_at->format('d/m/Y') : '-' }} · Check-out {{ $oOut ? $oOut->created_at->format('d/m/Y') : '-' }}
                                                    @if($oLate > 0)<span class="ap-over"> · telat {{ $hariText($oLate) }}</span>@endif
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </details>
                            @endif
                        </td>

                        {{-- STATUS --}}
                        <td class="c-status">
                            <span class="ap-tag t-{{ $tagTone }}">{{ $tagText }}</span>
                            @if($telatIn > 0)
                                <div class="ap-sub ap-over"><i class="fas fa-hourglass-end"></i>Check-in telat {{ $hariText($telatIn) }}</div>
                            @endif
                            @if($telatOut > 0)
                                <div class="ap-sub ap-over"><i class="fas fa-hourglass-end"></i>{{ $out ? 'Check-out telat ' : 'Lewat jadwal keluar ' }}{{ $hariText($telatOut) }}</div>
                            @endif
                            @if($moves > 0)
                                <div class="ap-sub"><i class="fas fa-exchange-alt"></i>Pindah unit {{ $moves }}×</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <x-apartemen.pager :paginator="$histories" />
        @else
            <x-apartemen.empty-state
                :title="$hasFilter ? 'Tidak ada riwayat yang cocok' : 'Belum ada riwayat pada ' . $periodeLabel"
                :text="$hasFilter ? 'Coba ubah atau reset filter yang dipakai.' : 'Riwayat akan muncul setelah ada penghuni yang check-in, pindah unit, check-out, atau dibatalkan.'"
                :resetUrl="$hasFilter ? route('apartemen.admin.history') : null" />
        @endif
    </div>
</div>

<style>
    /* Riwayat penghuni mess */
    .ap-hist td.c-mess .ap-sub i,.ap-hist td.c-status .ap-sub i{margin-right:.35rem;color:#94a3b8}
    .ap-date{font-size:.85rem;font-weight:600;color:#0f172a;white-space:nowrap}
    .ap-date i{margin-right:.4rem;color:#2563eb}
    .ap-over{color:#dc2626;font-weight:600}
    .ap-over i{color:#dc2626 !important}

    /* Realisasi: Check-in / Check-out / Menginap */
    .ap-sum-line{display:flex;flex-wrap:wrap;align-items:baseline;gap:.1rem .4rem;font-size:.82rem;color:#475569;margin-bottom:.2rem}
    .ap-sum-line i{width:1rem;color:#94a3b8}
    .ap-sum-line .lbl{min-width:5.2rem}
    .ap-sum-line b{font-weight:600;color:#0f172a}
    .ap-sum-line .jam{font-size:.72rem;color:#94a3b8;font-variant-numeric:tabular-nums}
    .ap-sum-line em{font-style:normal;font-size:.72rem;color:#94a3b8}

    /* Rincian dilipat (hanya pindah unit / catatan) */
    .ap-more{margin-top:.45rem}
    .ap-more summary{cursor:pointer;font-size:.75rem;font-weight:600;color:#2563eb;list-style:none;user-select:none}
    .ap-more summary::-webkit-details-marker{display:none}
    .ap-more summary::before{content:"▸ ";color:#94a3b8}
    .ap-more[open] summary::before{content:"▾ "}
    .ap-more .ap-trail{margin-top:.6rem}
    .ap-note-cancel{margin-top:.3rem}

    /* Timeline rincian */
    .ap-trail{position:relative;margin:0;padding:0;list-style:none}
    .ap-trail li{position:relative;display:flex;gap:.6rem;padding-bottom:.6rem}
    .ap-trail li:last-child{padding-bottom:0}
    .ap-trail li:not(:last-child)::before{content:"";position:absolute;left:calc(.325rem - 1px);top:.95rem;bottom:-.05rem;width:2px;background:#e6eaf0}
    .ap-trail-dot{flex:none;width:.65rem;height:.65rem;margin-top:.32rem;border-radius:50%;background:var(--c);box-shadow:0 0 0 3px var(--soft)}
    .ap-trail-top{display:flex;flex-wrap:wrap;align-items:baseline;gap:.1rem .6rem;font-size:.82rem}
    .ap-trail-top b{font-weight:600;color:var(--c)}
    .ap-trail-top time{font-size:.75rem;color:#64748b;font-variant-numeric:tabular-nums}

    /* Riwayat sebelumnya: satu baris ringkas per periode */
    .ap-prev{margin-top:.6rem;padding-top:.5rem;border-top:1px dashed #e6eaf0}
    .ap-prev summary{cursor:pointer;font-size:.75rem;font-weight:600;color:#2563eb}
    .ap-prevline{margin-top:.55rem}
    .ap-prevtop{display:flex;flex-wrap:wrap;align-items:baseline;gap:.1rem .6rem;font-size:.8rem;color:#475569}
    .ap-prevtop b{font-weight:600;color:#0f172a}

    .ap-hist td.c-real{min-width:15rem}
    @media (max-width:767px){
        .ap-hist td.c-mess,.ap-hist td.c-jadwal,.ap-hist td.c-real{grid-column:1/-1;margin-top:.6rem;padding-top:.6rem;border-top:1px solid #eef1f5;min-width:0}
    }
</style>
@endsection