@extends('layouts.app_apartadmin_sidebar')
@section('content')
@php
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
@endphp

<div class="w-full">

    @if(session('success'))
        <div class="ap-flash ok" role="status"><i class="fas fa-check-circle mt-0.5"></i><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
        <div class="ap-flash err" role="alert"><i class="fas fa-exclamation-circle mt-0.5"></i><span>{{ session('error') }}</span></div>
    @endif

    <x-apartemen.page-header title="Monitoring Penghuni" subtitle="Penghuni yang sedang tinggal di semua apartemen: check-in, check-out, pindah unit, dan pembatalan" />

    @if(($lewatJadwal ?? 0) > 0)
        <div class="ap-alert amber mb-4" role="status">
            <b>{{ number_format($lewatJadwal) }} penghuni</b> sudah melewati jadwal tapi belum diselesaikan. Gunakan tombol <b>Selesaikan</b> pada barisnya untuk mengisi tanggal masuk/keluar yang sebenarnya.
        </div>
    @endif

    {{-- DAFTAR PENGHUNI --}}
    <div class="ap-card overflow-hidden">
        <div class="ap-listhead">
            <h3>Daftar penghuni</h3>
            <p><b>{{ number_format($penghuni->total()) }}</b> penghuni aktif</p>
        </div>

        @if($penghuni->count() > 0)
        <div class="md:overflow-x-auto">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th>Penghuni</th>
                        <th>Apartemen &amp; unit</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($penghuni as $p)
                    @php
                        $assign        = $p->assign;
                        $now           = now();
                        $today         = now()->startOfDay();
                        $sudahCheckin  = $assign && $assign->checkin_at;
                        $status        = null;
                        $canCheckin    = false;
                        $canCheckout   = false;
                        $daysUntilStart = null;
                        $rel = null; $relTone = '';

                        if ($assign) {
                            $startDate = \Carbon\Carbon::parse($assign->tanggal_mulai)->startOfDay();
                            $endDate   = \Carbon\Carbon::parse($assign->tanggal_selesai)->startOfDay();
                            if ($assign->tanggal_mulai > $now) {
                                $status         = 'belum_aktif';
                                $canCheckin     = $today->greaterThanOrEqualTo($startDate) && !$sudahCheckin;
                                $daysUntilStart = $today->diffInDays($startDate, false);
                                $d   = (int) $daysUntilStart;
                                $rel = $d > 0 ? "Mulai $d hari lagi" : 'Mulai hari ini';
                            } elseif ($assign->tanggal_selesai < $now) {
                                $status      = 'expired';
                                $canCheckout = $sudahCheckin;
                                $d   = (int) abs($today->diffInDays($endDate, false));
                                $rel = $d > 0 ? "Lewat $d hari" : 'Berakhir hari ini';
                                $relTone = 'late';
                            } else {
                                $status      = 'active';
                                $canCheckin  = !$sudahCheckin;
                                $canCheckout = $sudahCheckin;
                                $d   = (int) $today->diffInDays($endDate, false);
                                $rel = $d > 0 ? "Sisa $d hari" : 'Berakhir hari ini';
                                $relTone = $d <= 3 ? 'soon' : '';
                            }
                        }
                        // Label status sesuai kenyataan: "Belum check-out" hanya untuk yang memang sudah check-in.
                        if ($status === 'expired') {
                            $tagText = $sudahCheckin ? 'Belum check-out' : 'Lewat jadwal';
                            $tagTone = 'red';
                        } elseif ($status === 'active') {
                            $tagText = $sudahCheckin ? 'Menginap' : 'Belum check-in';
                            $tagTone = $sudahCheckin ? 'green' : 'yellow';
                        } else {
                            $tagText = $status ? 'Belum aktif' : '-';
                            $tagTone = 'yellow';
                        }
                        $statusText  = $tagText;
                        $apt         = $assign->unit->apartemen->nama_apartemen ?? null;
                        $unit        = $assign->unit->nomor_unit ?? null;
                        $activeCount = $assign->penghuni_aktif_count ?? 0;

                        // Daftar semua penghuni aktif di unit/penempatan yang sama (untuk badge "N penghuni").
                        $rekan = collect($assign ? $assign->penghuniAktif : [])->map(fn($r) => [
                            'nama'       => $r->nama,
                            'id'         => $r->id_karyawan,
                            'unit_kerja' => $r->unit_kerja,
                        ])->values()->all();

                        $detail = [
                            'nama'       => $p->nama,
                            'initials'   => $initials($p->nama),
                            'id_karyawan'=> $p->id_karyawan,
                            'no_hp'      => $p->no_hp ?: '-',
                            'unit_kerja' => $p->unit_kerja ?: '-',
                            'gol'        => $p->gol ?: '-',
                            'apartemen'  => $apt ?? '-',
                            'unit'       => $unit ?? '-',
                            'periode'    => $assign ? $assign->tanggal_mulai->format('d/m/Y') . ' – ' . $assign->tanggal_selesai->format('d/m/Y') : '-',
                            'checkin'    => ($assign && $assign->checkin_at) ? $assign->checkin_at->format('d/m/Y H:i') : 'Belum check-in',
                            'status'     => $statusText,
                            'rekan'      => $rekan,
                        ];
                    @endphp
                    <tr class="ap-row {{ $status === 'expired' ? 'ap-late' : '' }}">
                        {{-- Penghuni --}}
                        <td class="c-person">
                            <div class="ap-person">
                                <span class="ap-avatar" style="{{ $tint($p->nama) }}">{{ $initials($p->nama) }}</span>
                                <div class="min-w-0">
                                    <div class="ap-name">{{ $p->nama }}</div>
                                    <div class="ap-sub">{{ $p->id_karyawan }}@if($p->unit_kerja) · {{ $p->unit_kerja }}@endif</div>
                                </div>
                            </div>
                        </td>

                        {{-- Apartemen & Unit --}}
                        <td class="c-apt">
                            <div class="ap-cell-ico"><i class="fas fa-building ap-ico"></i><div>
                            @if($apt)
                                <div class="ap-strong">{{ $apt }}</div>
                                <div class="ap-sub">Unit {{ $unit }}@if($activeCount > 1)<button type="button" class="ap-pill" style="cursor:pointer" title="Lihat siapa saja yang tinggal di unit ini" data-detail="{{ json_encode($detail, JSON_UNESCAPED_UNICODE) }}" onclick="apDetail(this)"><i class="fas fa-user-friends text-[10px]"></i>{{ $activeCount }} penghuni</button>@endif</div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                            </div></div>
                        </td>

                        {{-- Periode --}}
                        <td class="c-period whitespace-nowrap">
                            <div class="ap-cell-ico"><i class="far fa-calendar ap-ico"></i><div>
                            @if($assign)
                                <span class="text-gray-800">{{ $assign->tanggal_mulai->format('d/m/Y') }} – {{ $assign->tanggal_selesai->format('d/m/Y') }}</span>
                                <span class="ap-rel {{ $relTone }} block md:block">{{ $rel }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                            </div></div>
                        </td>

                        {{-- Status --}}
                        <td class="c-status">
                            @if($status)
                                <span class="ap-tag t-{{ $tagTone }}">{{ $tagText }}</span>
                                <div class="ap-sub mt-1">
                                    @if($sudahCheckin)
                                        <i class="fas fa-check-circle text-green-500"></i>Check-in {{ $assign->checkin_at->format('d/m H:i') }}
                                    @else
                                        <i class="far fa-circle"></i>Belum check-in
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="c-act">
                            <div class="ap-actions">
                                @if($status === 'expired' && $assign)
                                    <button type="button" class="ap-btn ap-btn-red" title="Isi tanggal masuk/keluar yang sebenarnya"
                                            data-assign="{{ $assign->id }}" data-nama="{{ $p->nama }}" data-count="{{ $activeCount }}"
                                            data-mulai="{{ $assign->tanggal_mulai->format('Y-m-d') }}" data-selesai="{{ $assign->tanggal_selesai->format('Y-m-d') }}"
                                            data-checkin="{{ $assign->checkin_at ? $assign->checkin_at->format('d/m/Y H:i') : '' }}"
                                            data-checkin-date="{{ $assign->checkin_at ? $assign->checkin_at->format('Y-m-d') : '' }}"
                                            onclick="apSelesai(this)">
                                        <i class="fas fa-flag-checkered"></i>Selesaikan
                                    </button>
                                @elseif($canCheckin)
                                    <button type="button" class="ap-btn ap-btn-green" data-id="{{ $p->id }}" data-nama="{{ $p->nama }}" onclick="apCheck(this,'checkin')">
                                        <i class="fas fa-sign-in-alt"></i>Check-in
                                    </button>
                                @elseif($status === 'belum_aktif' && $daysUntilStart > 0)
                                    <button type="button" disabled class="ap-btn ap-btn-off"
                                            title="Belum waktunya check-in. Mulai {{ $assign->tanggal_mulai->format('d/m/Y') }} ({{ (int) $daysUntilStart }} hari lagi). Check-in hanya bisa H-0.">
                                        <i class="fas fa-sign-in-alt"></i>Check-in
                                    </button>
                                @elseif($canCheckout)
                                    <button type="button" class="ap-btn ap-btn-red" data-id="{{ $p->id }}" data-nama="{{ $p->nama }}" onclick="apCheck(this,'checkout')">
                                        <i class="fas fa-sign-out-alt"></i>Check-out
                                    </button>
                                @endif

                                @if($assign && $assign->status == 'AKTIF')
                                    @if($assign->checkin_at)
                                    <button type="button" class="ap-ibtn indigo" title="Pindah unit" aria-label="Pindah unit {{ $p->nama }}"
                                            data-assign="{{ $assign->id }}" data-nama="{{ $p->nama }}" data-unit="{{ $assign->unit->id ?? '' }}" data-count="{{ $activeCount }}"
                                            onclick="apPindah(this)"><i class="fas fa-exchange-alt"></i><span class="ap-lbl">Pindah</span></button>
                                    @endif
                                    @if(!$assign->checkin_at)
                                    <button type="button" class="ap-ibtn danger" title="Batalkan penempatan (tidak jadi menginap)" aria-label="Batalkan penempatan {{ $p->nama }}"
                                            data-assign="{{ $assign->id }}" data-nama="{{ $p->nama }}" data-count="{{ $activeCount }}"
                                            onclick="apBatalkan(this)"><i class="fas fa-ban"></i><span class="ap-lbl">Batalkan</span></button>
                                    @endif
                                @endif

                                <button type="button" class="ap-ibtn" title="Lihat detail" aria-label="Detail {{ $p->nama }}"
                                        data-detail="{{ json_encode($detail, JSON_UNESCAPED_UNICODE) }}"
                                        onclick="apDetail(this)"><i class="far fa-eye"></i><span class="ap-lbl">Detail</span></button>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <x-apartemen.pager :paginator="$penghuni" />
        @else
            <x-apartemen.empty-state
                title="Belum ada penghuni aktif"
                text="Penghuni akan muncul di sini setelah masa tinggalnya dimulai."
                :resetUrl="null" />
        @endif
    </div>
</div>

{{-- Form tersembunyi untuk check-in / check-out --}}
<form id="apActionForm" method="POST" style="display:none">@csrf</form>

{{-- MODAL KONFIRMASI (menggantikan confirm() bawaan browser) --}}
<div id="apConfirm" class="ap-modal" role="dialog" aria-modal="true" aria-labelledby="apConfirmTitle">
    <div class="ap-panel sm">
        <div class="ap-panel-h">
            <span id="apConfirmTile" class="ap-tile t-blue"><i id="apConfirmIcon" class="fas fa-question"></i></span>
            <h3 id="apConfirmTitle">Konfirmasi</h3>
        </div>
        <div class="ap-panel-b"><p id="apConfirmMsg" class="text-sm text-gray-600 leading-relaxed"></p></div>
        <div class="ap-panel-f">
            <button type="button" class="ap-btn ap-btn-lg ap-btn-ghost" onclick="apSettle(false)">Batal</button>
            <button type="button" id="apConfirmOk" class="ap-btn ap-btn-lg ap-btn-blue" onclick="apSettle(true)">Lanjutkan</button>
        </div>
    </div>
</div>

{{-- MODAL PINDAH UNIT --}}
<div id="apPindah" class="ap-modal" role="dialog" aria-modal="true" aria-labelledby="apPindahTitle">
    <div class="ap-panel">
        <form id="pindahUnitForm" method="POST" action="">
            @csrf
            <div class="ap-panel-h">
                <span class="ap-tile t-indigo"><i class="fas fa-exchange-alt"></i></span>
                <div>
                    <h3 id="apPindahTitle">Pindah unit</h3>
                    <p id="pindahNamaPenghuni">-</p>
                </div>
                <button type="button" class="ap-x" onclick="apClose('apPindah')" aria-label="Tutup"><i class="fas fa-times"></i></button>
            </div>
            <div class="ap-panel-b">
                <div id="pindahCoResidentNote" class="ap-alert amber hidden mb-3"></div>
                <div class="ap-field">
                    <label class="ap-label" for="pindahUnitSelect">Unit tujuan (kosong / READY)</label>
                    <select name="unit_id" id="pindahUnitSelect" required class="ap-input">
                        <option value="">Pilih unit</option>
                        @foreach($unitKosong as $u)
                            <option value="{{ $u->id }}" data-unit-id="{{ $u->id }}">{{ $u->apartemen->nama_apartemen ?? '-' }} - Unit {{ $u->nomor_unit }} (kapasitas {{ $u->kapasitas }})</option>
                        @endforeach
                    </select>
                    @if($unitKosong->isEmpty())
                        <p class="text-xs text-red-600 mt-1">Tidak ada unit kosong (READY) saat ini.</p>
                    @endif
                </div>
                <div class="ap-field">
                    <label class="ap-label" for="pindahTanggal">Tanggal pindah</label>
                    <input type="date" name="tanggal_transfer" id="pindahTanggal" required value="{{ now()->format('Y-m-d') }}" class="ap-input">
                </div>
                <div class="ap-field">
                    <label class="ap-label" for="pindahAlasan">Alasan pindah</label>
                    <textarea name="alasan" id="pindahAlasan" required rows="3" maxlength="500" placeholder="Contoh: unit lama bermasalah, permintaan penghuni" class="ap-input"></textarea>
                </div>
                <p class="mt-3 text-xs text-gray-400">Riwayat penempatan sebelumnya tetap tersimpan di halaman Riwayat.</p>
            </div>
            <div class="ap-panel-f">
                <button type="button" class="ap-btn ap-btn-lg ap-btn-ghost" onclick="apClose('apPindah')">Batal</button>
                <button type="submit" id="pindahSubmitBtn" class="ap-btn ap-btn-lg ap-btn-indigo">Pindahkan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL BATALKAN PENEMPATAN --}}
<div id="apBatalkan" class="ap-modal" role="dialog" aria-modal="true" aria-labelledby="apBatalkanTitle">
    <div class="ap-panel">
        <form id="batalkanForm" method="POST" action="">
            @csrf
            <div class="ap-panel-h">
                <span class="ap-tile t-red"><i class="fas fa-ban"></i></span>
                <div>
                    <h3 id="apBatalkanTitle">Batalkan penempatan</h3>
                    <p id="batalkanNamaPenghuni">-</p>
                </div>
                <button type="button" class="ap-x" onclick="apClose('apBatalkan')" aria-label="Tutup"><i class="fas fa-times"></i></button>
            </div>
            <div class="ap-panel-b">
                <div id="batalkanCoResidentNote" class="ap-alert amber hidden mb-3"></div>
                <div class="ap-alert red mb-3">Penempatan akan dihentikan dan unit kembali kosong (READY). Tindakan ini tercatat di halaman Riwayat.</div>
                <div class="ap-field">
                    <label class="ap-label" for="batalkanAlasan">Catatan / alasan pembatalan</label>
                    <textarea name="alasan" id="batalkanAlasan" required rows="3" maxlength="500" placeholder="Contoh: permintaan penghuni, salah input penempatan" class="ap-input"></textarea>
                </div>
            </div>
            <div class="ap-panel-f">
                <button type="button" class="ap-btn ap-btn-lg ap-btn-ghost" onclick="apClose('apBatalkan')">Kembali</button>
                <button type="submit" id="batalkanSubmitBtn" class="ap-btn ap-btn-lg ap-btn-red">Batalkan penempatan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL SELESAIKAN PENEMPATAN (lewat jadwal) --}}
<div id="apSelesai" class="ap-modal" role="dialog" aria-modal="true" aria-labelledby="apSelesaiTitle" data-hari-ini="{{ now()->toDateString() }}">
    <div class="ap-panel">
        <form id="selesaiForm" method="POST" action="">
            @csrf
            <div class="ap-panel-h">
                <span class="ap-tile t-red"><i class="fas fa-flag-checkered"></i></span>
                <div>
                    <h3 id="apSelesaiTitle">Selesaikan penempatan</h3>
                    <p id="selesaiNama">-</p>
                </div>
                <button type="button" class="ap-x" onclick="apClose('apSelesai')" aria-label="Tutup"><i class="fas fa-times"></i></button>
            </div>
            <div class="ap-panel-b">
                <div id="selesaiCoNote" class="ap-alert amber hidden mb-3"></div>
                <div class="ap-alert amber mb-3">Penempatan ini sudah lewat jadwal. Isi tanggal masuk dan keluar yang sebenarnya; riwayat dicatat sesuai tanggal tersebut.</div>
                <div class="ap-field" id="selesaiMasukWrap">
                    <label class="ap-label" for="selesaiMasuk">Tanggal masuk sebenarnya</label>
                    <input type="date" name="tanggal_masuk" id="selesaiMasuk" class="ap-input">
                </div>
                <p id="selesaiMasukInfo" class="text-xs text-gray-500 mb-3 hidden"></p>
                <div class="ap-field">
                    <label class="ap-label" for="selesaiKeluar">Tanggal keluar sebenarnya</label>
                    <input type="date" name="tanggal_keluar" id="selesaiKeluar" required class="ap-input">
                </div>
                <div class="ap-field">
                    <label class="ap-label" for="selesaiCatatan">Catatan (opsional)</label>
                    <textarea name="catatan" id="selesaiCatatan" rows="2" maxlength="500" placeholder="Contoh: lupa check-out, sudah pulang sesuai jadwal" class="ap-input"></textarea>
                </div>
                <p class="mt-3 text-xs text-gray-400">Bila penghuni tidak jadi menginap, gunakan <b>Batalkan</b> (tersedia untuk yang belum check-in).</p>
            </div>
            <div class="ap-panel-f">
                <button type="button" class="ap-btn ap-btn-lg ap-btn-ghost" onclick="apClose('apSelesai')">Kembali</button>
                <button type="submit" id="selesaiSubmitBtn" class="ap-btn ap-btn-lg ap-btn-red">Selesaikan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL DETAIL PENGHUNI --}}
<div id="apDetail" class="ap-modal" role="dialog" aria-modal="true" aria-labelledby="detailNama">
    <div class="ap-panel">
        <div class="ap-panel-h">
            <span id="detailAvatar" class="ap-avatar" style="width:2.6rem;height:2.6rem"></span>
            <div class="min-w-0">
                <h3 id="detailNama">-</h3>
                <p data-f="id_karyawan">-</p>
            </div>
            <button type="button" class="ap-x" onclick="apClose('apDetail')" aria-label="Tutup"><i class="fas fa-times"></i></button>
        </div>
        <div class="ap-panel-b">
            <p class="ap-dl-title">Data penghuni</p>
            <dl class="ap-dl">
                <dt>No. HP</dt><dd data-f="no_hp">-</dd>
                <dt>Unit kerja</dt><dd data-f="unit_kerja">-</dd>
                <dt>Golongan</dt><dd data-f="gol">-</dd>
            </dl>
            <p class="ap-dl-title">Penempatan</p>
            <dl class="ap-dl">
                <dt>Apartemen</dt><dd data-f="apartemen">-</dd>
                <dt>Unit</dt><dd data-f="unit">-</dd>
                <dt>Periode</dt><dd data-f="periode">-</dd>
                <dt>Check-in</dt><dd data-f="checkin">-</dd>
                <dt>Status</dt><dd data-f="status">-</dd>
            </dl>
            <p class="ap-dl-title" id="detailRekanTitle">Penghuni di unit ini</p>
            <ul id="detailRekan" class="text-sm text-gray-700 space-y-1"></ul>
        </div>
        <div class="ap-panel-f">
            <button type="button" class="ap-btn ap-btn-lg ap-btn-ghost" onclick="apClose('apDetail')">Tutup</button>
        </div>
    </div>
</div>

<script>
(function () {
    const $ = (id) => document.getElementById(id);
    let busy = false;
    let confirmResolve = null;

    // ---- modal dasar ----
    function openModal(id) {
        $(id).classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        if (id === 'apConfirm' && confirmResolve) { confirmResolve(false); confirmResolve = null; }
        $(id).classList.remove('is-open');
        if (!document.querySelector('.ap-modal.is-open')) document.body.style.overflow = '';
    }
    window.apClose = closeModal;

    document.querySelectorAll('.ap-modal').forEach((m) => {
        m.addEventListener('mousedown', (e) => { if (e.target === m) closeModal(m.id); });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        // Tutup modal paling atas (konfirmasi dulu bila terbuka)
        const open = $('apConfirm').classList.contains('is-open') ? $('apConfirm') : document.querySelector('.ap-modal.is-open');
        if (open) closeModal(open.id);
    });
    // Kembali dari halaman sebelumnya (bfcache) jangan menyisakan tombol "Memproses"
    window.addEventListener('pageshow', (e) => { if (e.persisted) location.reload(); });

    // ---- konfirmasi ----
    function ask({ title, message, okText = 'Lanjutkan', tone = 'blue', icon = 'fa-question' }) {
        $('apConfirmTitle').textContent = title;
        $('apConfirmMsg').textContent = message;
        $('apConfirmIcon').className = 'fas ' + icon;
        $('apConfirmTile').className = 'ap-tile t-' + tone;
        const ok = $('apConfirmOk');
        ok.textContent = okText;
        ok.className = 'ap-btn ap-btn-lg ap-btn-' + tone;
        openModal('apConfirm');
        return new Promise((resolve) => { confirmResolve = resolve; });
    }
    window.apSettle = function (value) {
        if (confirmResolve) { confirmResolve(value); confirmResolve = null; }
        closeModal('apConfirm');
    };

    // BARU: tutup modal form dulu, baru tampilkan konfirmasi.
    // Bila dibatalkan, modal form dibuka kembali dengan isian yang sama.
    async function confirmFrom(modalId, opts) {
        const m = $(modalId);
        m.classList.remove('is-open');
        const ok = await ask(opts);
        if (!ok) {
            m.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
        return ok;
    }

    function lockButton(btn, label) {
        if (!btn) return;
        btn.classList.add('is-loading');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>' + label;
    }

    // ---- check-in / check-out ----
    window.apCheck = async function (btn, kind) {
        if (busy) return;
        const { id, nama } = btn.dataset;
        const isIn = kind === 'checkin';
        const ok = await ask({
            title: isIn ? 'Check-in penghuni?' : 'Check-out penghuni?',
            message: (isIn ? 'Catat check-in untuk ' : 'Catat check-out untuk ') + nama + '.',
            okText: isIn ? 'Ya, check-in' : 'Ya, check-out',
            tone: isIn ? 'green' : 'red',
            icon: isIn ? 'fa-sign-in-alt' : 'fa-sign-out-alt',
        });
        if (!ok) return;
        busy = true;
        lockButton(btn, 'Memproses');
        const form = $('apActionForm');
        form.action = '/apartemen/admin/penghuni/' + id + '/' + kind;
        form.submit();
    };

    // ---- pindah unit ----
    window.apPindah = function (btn) {
        const { assign, nama, unit, count } = btn.dataset;
        const select = $('pindahUnitSelect');
        const note = $('pindahCoResidentNote');
        $('pindahNamaPenghuni').textContent = nama;
        $('pindahUnitForm').action = '/apartemen/admin/transfer/' + assign;

        if (parseInt(count) > 1) {
            note.textContent = 'Unit ini dihuni ' + count + ' orang. Semuanya ikut dipindahkan bersama.';
            note.classList.remove('hidden');
        } else {
            note.classList.add('hidden');
        }
        select.value = '';
        Array.from(select.options).forEach((opt) => {
            opt.disabled = !!(opt.dataset.unitId && unit && parseInt(opt.dataset.unitId) === parseInt(unit));
        });
        $('pindahAlasan').value = '';
        openModal('apPindah');
    };

    $('pindahUnitForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (busy) return;
        const nama = $('pindahNamaPenghuni').textContent;
        const unitText = $('pindahUnitSelect').selectedOptions[0]?.text || '';
        const ok = await confirmFrom('apPindah', {
            title: 'Pindahkan penghuni?',
            message: nama + ' akan dipindahkan ke ' + unitText + '.',
            okText: 'Ya, pindahkan', tone: 'indigo', icon: 'fa-exchange-alt',
        });
        if (!ok) return;
        busy = true;
        lockButton($('pindahSubmitBtn'), 'Memproses');
        this.submit();
    });

    // ---- batalkan penempatan ----
    window.apBatalkan = function (btn) {
        const { assign, nama, count } = btn.dataset;
        const note = $('batalkanCoResidentNote');
        $('batalkanNamaPenghuni').textContent = nama;
        $('batalkanForm').action = '/apartemen/admin/assign/' + assign + '/cancel';

        if (parseInt(count) > 1) {
            note.textContent = 'Unit ini dihuni ' + count + ' orang. Semuanya ikut dibatalkan.';
            note.classList.remove('hidden');
        } else {
            note.classList.add('hidden');
        }
        $('batalkanAlasan').value = '';
        openModal('apBatalkan');
    };

    $('batalkanForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (busy) return;
        const nama = $('batalkanNamaPenghuni').textContent;
        const ok = await confirmFrom('apBatalkan', {
            title: 'Batalkan penempatan?',
            message: 'Penempatan ' + nama + ' akan dihentikan. Tindakan ini tidak dapat dibatalkan kembali.',
            okText: 'Ya, batalkan', tone: 'red', icon: 'fa-ban',
        });
        if (!ok) return;
        busy = true;
        lockButton($('batalkanSubmitBtn'), 'Memproses');
        this.submit();
    });

    // ---- selesaikan (lewat jadwal) ----
    window.apSelesai = function (btn) {
        const { assign, nama, count, mulai, selesai, checkin, checkinDate } = btn.dataset;
        const hariIni = $('apSelesai').dataset.hariIni;
        const note = $('selesaiCoNote');
        const masuk = $('selesaiMasuk');
        const keluar = $('selesaiKeluar');

        $('selesaiNama').textContent = nama;
        $('selesaiForm').action = '/apartemen/admin/assign/' + assign + '/selesaikan';
        $('selesaiCatatan').value = '';

        if (parseInt(count) > 1) {
            note.textContent = 'Unit ini dihuni ' + count + ' orang. Semuanya ikut diselesaikan bersama.';
            note.classList.remove('hidden');
        } else {
            note.classList.add('hidden');
        }

        if (checkin) {
            // Sudah check-in: tanggal masuk tidak diubah.
            masuk.value = '';
            masuk.disabled = true;
            $('selesaiMasukWrap').classList.add('hidden');
            $('selesaiMasukInfo').textContent = 'Sudah check-in pada ' + checkin + '.';
            $('selesaiMasukInfo').classList.remove('hidden');
            keluar.min = checkinDate;
        } else {
            masuk.disabled = false;
            masuk.min = mulai;
            masuk.max = hariIni;
            masuk.value = mulai;
            $('selesaiMasukWrap').classList.remove('hidden');
            $('selesaiMasukInfo').classList.add('hidden');
            keluar.min = mulai;
        }
        keluar.max = hariIni;
        keluar.value = selesai > hariIni ? hariIni : selesai;

        openModal('apSelesai');
    };

    $('selesaiForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (busy) return;
        const nama = $('selesaiNama').textContent;
        const ok = await confirmFrom('apSelesai', {
            title: 'Selesaikan penempatan?',
            message: 'Penempatan ' + nama + ' akan ditutup dan riwayat dicatat sesuai tanggal yang diisi. Tindakan ini tidak dapat dibatalkan.',
            okText: 'Ya, selesaikan', tone: 'red', icon: 'fa-flag-checkered',
        });
        if (!ok) return;
        busy = true;
        lockButton($('selesaiSubmitBtn'), 'Memproses');
        this.submit();
    });

    // ---- detail ----
    window.apDetail = function (btn) {
        const d = JSON.parse(btn.dataset.detail);
        $('detailNama').textContent = d.nama;
        const av = $('detailAvatar');
        av.textContent = d.initials;
        const hue = Array.from(d.nama).reduce((a, c) => (a * 31 + c.charCodeAt(0)) % 360, 7);
        av.style.background = 'hsl(' + hue + ' 70% 93%)';
        av.style.color = 'hsl(' + hue + ' 50% 30%)';
        document.querySelectorAll('#apDetail [data-f]').forEach((el) => { el.textContent = d[el.dataset.f] ?? '-'; });

        // Daftar penghuni lain di unit yang sama (hanya tampil bila lebih dari 1 orang)
        const ul = $('detailRekan');
        const rekan = d.rekan || [];
        ul.innerHTML = '';
        rekan.forEach((r) => {
            const li = document.createElement('li');
            li.textContent = r.nama + ' · ' + r.id + (r.unit_kerja ? ' · ' + r.unit_kerja : '') + (r.nama === d.nama ? ' (ini)' : '');
            ul.appendChild(li);
        });
        const tampil = rekan.length > 1;
        $('detailRekanTitle').style.display = tampil ? '' : 'none';
        ul.style.display = tampil ? '' : 'none';

        openModal('apDetail');
    };
})();
</script>
@endsection