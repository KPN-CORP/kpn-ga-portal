@extends('layouts.app-sidebar')

@section('content')
@php
    $isSuper       = auth()->user()->isSuperadminTrack();
    $activeTab     = $tab ?? 'semua';
    $hasDateFilter = request()->filled('from') || request()->filled('to');
    $hasFilter     = request()->filled('search') || $activeTab !== 'semua' || $hasDateFilter;

    // Tab + warna pill angka (gaya: teks, pill jumlah, garis bawah biru untuk tab aktif)
    $tabs = [
        'semua'          => ['Semua',          'bg-slate-100 text-slate-600'],
        'perlu_tindakan' => ['Perlu Tindakan', 'bg-amber-100 text-amber-700'],
        'menunggu'       => ['Menunggu',       'bg-blue-100 text-blue-700'],
        'diterima'       => ['Diterima',       'bg-emerald-100 text-emerald-700'],
        'ditolak'        => ['Ditolak',        'bg-rose-100 text-rose-700'],
    ];

    // Hitung label status SEKALI per dokumen (dipakai tabel & kartu)
    $statusLabels = [];
    foreach ($documents as $d) {
        $statusLabels[$d->id] = $d->statusForUser(auth()->user())['label'] ?? 'Unknown';
    }

    $field = 'h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 sm:h-10 sm:text-sm';

    // Arah daftar: masuk = dokumen yang saya terima, keluar = dokumen yang saya kirim
    $arah    = $arah ?? 'masuk';
    $tabKeys = [
        'masuk'  => ['semua', 'perlu_tindakan', 'diterima', 'ditolak'],
        'keluar' => ['semua', 'menunggu', 'diterima', 'ditolak'],
        'semua'  => ['semua', 'perlu_tindakan', 'menunggu', 'diterima', 'ditolak'],
    ];
    // Penjelasan singkat tiap tab, per arah
    $tabHelp = [
        'masuk' => [
            'semua'          => 'Semua dokumen yang dikirim kepada Anda.',
            'perlu_tindakan' => 'Belum Anda proses. Klik "Tindak lanjuti", lalu pilih Terima, Tolak, atau Teruskan.',
            'diterima'       => 'Dokumen yang sudah Anda terima.',
            'ditolak'        => 'Dokumen yang Anda tolak. Dokumen sudah dikembalikan ke pengirim.',
        ],
        'keluar' => [
            'semua'    => 'Semua dokumen yang Anda kirim.',
            'menunggu' => 'Sudah Anda kirim, belum direspon penerima. Salah tujuan? Buka dokumennya lalu pilih Alihkan.',
            'diterima' => 'Dokumen Anda yang sudah diterima penerima.',
            'ditolak'  => 'Ditolak penerima. Buka dokumennya, perbaiki, lalu klik Kirim Ulang.',
        ],
        'semua' => [
            'semua'          => 'Semua dokumen dari seluruh pengguna.',
            'perlu_tindakan' => 'Dokumen yang menunggu tindakan Anda sendiri.',
            'menunggu'       => 'Dokumen yang Anda kirim dan belum direspon.',
            'diterima'       => 'Dokumen yang sudah diterima.',
            'ditolak'        => 'Dokumen yang ditolak penerima.',
        ],
    ];

    // Pilihan arah + angka perhatian (Masuk = perlu tindakan, Keluar = ditolak & perlu kirim ulang)
    $segments = [
        'masuk'  => ['Dokumen Masuk',  'Yang saya terima', 'inbox',        (int) ($dirCounts['masuk']['perlu_tindakan'] ?? 0), 'tr-badge-amber', 'Perlu tindakan Anda'],
        'keluar' => ['Dokumen Keluar', 'Yang saya kirim',  'paper-plane',  (int) ($dirCounts['keluar']['ditolak'] ?? 0),       'tr-badge-rose',  'Ditolak, perlu dikirim ulang'],
    ];
    if ($isSuper) {
        $segments['semua'] = ['Semua Dokumen', 'Seluruh pengguna', 'layer-group', 0, '', ''];
    }

    // Bantuan terbuka otomatis hanya untuk user yang belum punya dokumen sama sekali
    $showHelp = !$isSuper
        && ((int) ($dirCounts['masuk']['semua'] ?? 0) + (int) ($dirCounts['keluar']['semua'] ?? 0)) === 0;
@endphp

<div class="tr-page space-y-5 text-slate-800">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Track R – Dokumen</h1>
                <button type="button" id="toggleHelp" aria-expanded="{{ $showHelp ? 'true' : 'false' }}" aria-controls="helpPanel"
                        class="inline-flex h-8 items-center gap-1.5 rounded-full border border-slate-300 bg-white px-3 text-xs font-medium text-slate-600 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    <i class="far fa-question-circle text-blue-500" aria-hidden="true"></i> Bantuan
                </button>
            </div>
            <p class="mt-1 text-sm text-slate-500">Pantau dokumen yang Anda kirim dan terima.</p>
        </div>
        <div class="grid grid-cols-2 gap-2 sm:flex sm:gap-3">
            <a href="{{ route('track-r.export', array_merge(request()->query(), ['arah' => $arah])) }}"
               class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 sm:h-10">
                <i class="fas fa-download text-xs text-slate-500" aria-hidden="true"></i> Ekspor CSV
            </a>
            <a href="{{ route('track-r.create') }}"
               class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 sm:h-10">
                <i class="fas fa-plus text-xs" aria-hidden="true"></i> Kirim dokumen
            </a>
        </div>
    </div>

    {{-- BANTUAN (tombol kecil di samping judul) --}}
    <section id="helpPanel" class="rounded-xl border border-blue-100 bg-blue-50/60 {{ $showHelp ? '' : 'hidden' }}" aria-label="Bantuan Track R">
        <div class="flex items-center justify-between gap-3 px-4 pt-3">
            <h2 class="inline-flex items-center gap-2 text-sm font-semibold text-blue-900">
                <i class="far fa-question-circle text-blue-500" aria-hidden="true"></i> Cara kirim &amp; terima dokumen
            </h2>
            <button type="button" id="closeHelp" aria-label="Tutup bantuan"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-blue-700 hover:bg-blue-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                <i class="fas fa-times text-sm" aria-hidden="true"></i>
            </button>
        </div>
        <ol class="tr-steps p-4">
            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white" aria-hidden="true">1</span>
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900">Kirim</p>
                    <p class="text-sm text-slate-600">Klik tombol biru <b>Kirim dokumen</b> di kanan atas. Isi nomor dan judul, pilih penerima, lampirkan file. Dokumen yang Anda kirim tercatat di <b>Dokumen Keluar &rsaquo; Menunggu</b>.</p>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white" aria-hidden="true">2</span>
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900">Terima</p>
                    <p class="text-sm text-slate-600">Dokumen untuk Anda muncul di <b>Dokumen Masuk &rsaquo; Perlu Tindakan</b>. Klik <b>Tindak lanjuti</b>, periksa lampiran, lalu pilih <b>Terima</b>, <b>Tolak</b> (wajib beri alasan), atau <b>Teruskan</b> ke orang lain.</p>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white" aria-hidden="true">3</span>
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900">Pantau</p>
                    <p class="text-sm text-slate-600">Pengirim memantau di <b>Dokumen Keluar</b>. Jika <b>Ditolak</b>, buka dokumennya, perbaiki, lalu klik <b>Kirim Ulang</b>.</p>
                </div>
            </li>
        </ol>
    </section>

    {{-- PEMISAH: dokumen yang saya TERIMA vs yang saya KIRIM --}}
    <nav aria-label="Jenis dokumen" class="tr-seg {{ $isSuper ? 'has-3' : '' }}">
        @foreach($segments as $key => [$segTitle, $segSub, $segIcon, $segBadge, $segTone, $segBadgeTitle])
            @php
                $segActive = $arah === $key;
                $segQuery  = array_filter(
                    array_merge(request()->except(['page', 'tab', 'arah']), ['arah' => $key]),
                    fn ($v) => $v !== null && $v !== ''
                );
            @endphp
            <a href="{{ route('track-r.index', $segQuery) }}"
               @if($segActive) aria-current="page" @endif
               class="{{ $segActive ? 'is-active' : '' }}">
                <span class="tr-seg-ico" aria-hidden="true"><i class="fas fa-{{ $segIcon }}"></i></span>
                <span class="min-w-0 flex-1">
                    <span class="block tr-wrap text-sm font-semibold leading-snug">{{ $segTitle }}</span>
                    <span class="block tr-wrap text-xs opacity-75">{{ $segSub }}</span>
                </span>
                @if($segBadge > 0)
                    <span class="tr-badge {{ $segTone }}" title="{{ $segBadgeTitle }}">{{ number_format($segBadge) }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    {{-- TAB STATUS: di HP berbaris ke bawah (grid 2 kolom), di layar lebar berbentuk garis bawah --}}
    <div class="space-y-2">
        <nav aria-label="Filter status dokumen" class="tr-tabs">
            @foreach($tabKeys[$arah] as $key)
                @php
                    [$text, $pill] = $tabs[$key];
                    $isActive = $activeTab === $key;
                    $tabQuery = array_filter(
                        array_merge(request()->except(['page', 'tab', 'arah']), ['arah' => $arah, 'tab' => $key === 'semua' ? null : $key]),
                        fn ($v) => $v !== null && $v !== ''
                    );
                @endphp
                <a href="{{ route('track-r.index', $tabQuery) }}" title="{{ $tabHelp[$arah][$key] }}"
                   @if($isActive) aria-current="page" @endif
                   class="tr-tab {{ $isActive ? 'is-active' : '' }}">
                    <span>{{ $text }}</span>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums {{ $pill }}">{{ number_format($counts[$key] ?? 0) }}</span>
                </a>
            @endforeach
        </nav>
        <p class="flex items-start gap-2 px-1 text-sm text-slate-600">
            <i class="fas fa-info-circle mt-0.5 text-slate-400" aria-hidden="true"></i>
            <span>{{ $tabHelp[$arah][$activeTab] ?? '' }}</span>
        </p>
    </div>

    {{-- FILTER --}}
    <section class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4" aria-label="Filter dokumen">
        <form method="GET" action="{{ route('track-r.index') }}">
            <input type="hidden" name="arah" value="{{ $arah }}">
            @if($activeTab !== 'semua')
                <input type="hidden" name="tab" value="{{ $activeTab }}">
            @endif

            <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="flex-1">
                    <label for="f-search" class="sr-only">Cari dokumen</label>
                    <div class="relative">
                        <i class="fas fa-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
                        <input id="f-search" type="search" name="search" value="{{ request('search') }}"
                               placeholder="Cari nomor atau judul dokumen"
                               class="{{ $field }} pl-10">
                    </div>
                </div>

                {{-- Tombol filter tanggal (hanya di bawah lg) --}}
                <button type="button" id="toggleDates" aria-expanded="{{ $hasDateFilter ? 'true' : 'false' }}" aria-controls="dateFilters"
                        class="inline-flex h-11 items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-slate-50 sm:h-10 lg:hidden">
                    <span class="inline-flex items-center gap-2">
                        <i class="far fa-calendar-alt text-slate-400" aria-hidden="true"></i> Filter tanggal
                        @if($hasDateFilter)
                            <span class="h-2 w-2 rounded-full bg-blue-600" title="Filter tanggal aktif"></span>
                        @endif
                    </span>
                    <i class="fas fa-chevron-down text-[10px] text-slate-400" aria-hidden="true"></i>
                </button>

                {{-- Kelas "hidden" ditambah/dihapus oleh JS di mobile; di lg selalu tampil --}}
                <div id="dateFilters" class="flex flex-col gap-3 sm:flex-row lg:flex {{ $hasDateFilter ? '' : 'hidden' }}">
                    <div class="flex-1 lg:w-40 lg:flex-none">
                        <label for="f-from" class="mb-1 block text-xs font-medium text-slate-500">Dari tanggal</label>
                        <input id="f-from" type="date" name="from" value="{{ request('from') }}" class="{{ $field }}">
                    </div>
                    <div class="flex-1 lg:w-40 lg:flex-none">
                        <label for="f-to" class="mb-1 block text-xs font-medium text-slate-500">Sampai tanggal</label>
                        <input id="f-to" type="date" name="to" value="{{ request('to') }}" class="{{ $field }}">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="inline-flex h-11 flex-1 items-center justify-center rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2 sm:h-10 lg:flex-none">
                        Cari
                    </button>
                    @if($hasFilter)
                        <a href="{{ route('track-r.index', ['arah' => $arah]) }}"
                           class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50 sm:h-10">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>

    </section>

    {{-- RINGKASAN HASIL --}}
    @if($documents->count() > 0)
        <p class="px-1 text-sm text-slate-500" aria-live="polite">
            Menampilkan {{ $documents->firstItem() }}–{{ $documents->lastItem() }} dari {{ $documents->total() }} dokumen
            <span class="hidden lg:inline">· Klik baris atau tombol di kolom Aksi untuk membuka detail.</span>
        </p>
    @endif

    @if($documents->isEmpty())
        {{-- KOSONG --}}
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
            <i class="fas fa-folder-open text-4xl text-slate-300" aria-hidden="true"></i>
            <p class="mt-3 font-medium text-slate-700">
                {{ $hasFilter ? 'Tidak ada dokumen yang cocok' : ($arah === 'masuk' ? 'Belum ada dokumen masuk' : ($arah === 'keluar' ? 'Belum ada dokumen keluar' : 'Belum ada dokumen')) }}
            </p>
            <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                {{ $hasFilter ? 'Ubah kata kunci atau filter, atau reset untuk melihat semua dokumen.' : ($arah === 'masuk' ? 'Dokumen yang dikirim orang lain kepada Anda akan muncul di sini.' : 'Kirim dokumen pertama Anda untuk mulai melacaknya di sini.') }}
            </p>
            <div class="mt-5">
                @if($hasFilter)
                    <a href="{{ route('track-r.index', ['arah' => $arah]) }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset filter</a>
                @else
                    <a href="{{ route('track-r.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">
                        <i class="fas fa-plus text-xs" aria-hidden="true"></i> Kirim dokumen
                    </a>
                @endif
            </div>
        </div>
    @else

        {{-- TABEL (laptop & desktop, mulai 1024px) --}}
        <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white lg:block">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-medium text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3">Dokumen</th>
                        <th scope="col" class="px-4 py-3">Pengirim &amp; penerima</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3">Waktu</th>
                        <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($documents as $doc)
                        @php
                            $otherRecipients = $doc->recipients->where('id', '!=', $doc->penerima_id);
                            $detailUrl = route('track-r.show', $doc->id);
                            $needsAction = ($doc->penerima_id === auth()->id() && in_array($doc->status, ['dikirim', 'diteruskan'], true))
                                       || ($doc->pengirim_id === auth()->id() && $doc->status === 'ditolak');
                            $actionLabel = $doc->status === 'ditolak' ? 'Kirim ulang' : 'Tindak lanjuti';
                        @endphp
                        <tr class="cursor-pointer transition hover:bg-slate-50"
                            onclick="if(!event.target.closest('a,button')) window.location.href='{{ $detailUrl }}'">
                            <td class="max-w-xs px-4 py-3 align-top">
                                <a href="{{ $detailUrl }}" class="tr-wrap font-mono text-xs font-semibold text-blue-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                    {{ $doc->nomor_dokumen }}
                                </a>
                                <p class="tr-wrap mt-0.5 font-medium text-slate-900">{{ $doc->judul }}</p>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="space-y-1">
                                    <div class="flex items-baseline gap-2">
                                        <span class="w-7 shrink-0 text-xs text-slate-400">Dari</span>
                                        <span class="tr-wrap text-slate-700">{{ $doc->pengirim->name ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                        <span class="w-7 shrink-0 text-xs text-slate-400">Ke</span>
                                        <span class="tr-wrap text-slate-700">{{ $doc->penerima->name ?? '-' }}</span>
                                        @if($otherRecipients->count() > 0)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500"
                                                  title="{{ $otherRecipients->pluck('name')->implode(', ') }}">+{{ $otherRecipients->count() }}</span>
                                        @endif
                                    </div>
                                    @if($isSuper)
                                        <div class="flex items-baseline gap-2">
                                            <span class="w-7 shrink-0 text-xs text-slate-400">Unit</span>
                                            <span class="text-xs text-slate-500">{{ $doc->pengirim->company_name ?? '-' }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                @include('track_r._status-badge', ['status' => $doc->status, 'label' => $statusLabels[$doc->id]])
                            </td>
                            <td class="px-4 py-3 align-top tabular-nums text-slate-600">
                                <div>{{ $doc->created_at->format('d/m/Y') }} <span class="text-slate-400">{{ $doc->created_at->format('H:i') }}</span></div>
                                <div class="mt-0.5 text-xs text-slate-400">Diperbarui {{ $doc->updated_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-4 py-3 text-right align-top">
                                <a href="{{ $detailUrl }}"
                                   class="inline-flex h-9 items-center gap-2 whitespace-nowrap rounded-lg px-3 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 {{ $needsAction ? 'bg-blue-600 text-white hover:bg-blue-700' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                                    {{ $needsAction ? $actionLabel : 'Lihat' }}
                                    <i class="fas fa-chevron-right text-[10px]" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- KARTU (HP & tablet, di bawah 1024px) --}}
        <div class="tr-cards">
            @foreach($documents as $doc)
                @php
                    $otherRecipients = $doc->recipients->where('id', '!=', $doc->penerima_id);
                    $needsAction = ($doc->penerima_id === auth()->id() && in_array($doc->status, ['dikirim', 'diteruskan'], true))
                                       || ($doc->pengirim_id === auth()->id() && $doc->status === 'ditolak');
                            $actionLabel = $doc->status === 'ditolak' ? 'Kirim ulang' : 'Tindak lanjuti';
                @endphp
                <a href="{{ route('track-r.show', $doc->id) }}"
                   class="flex flex-col rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 active:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    <div class="flex items-start justify-between gap-3">
                        <span class="tr-wrap min-w-0 font-mono text-xs font-semibold text-slate-500">{{ $doc->nomor_dokumen }}</span>
                        <span class="shrink-0">
                            @include('track_r._status-badge', ['status' => $doc->status, 'label' => $statusLabels[$doc->id]])
                        </span>
                    </div>

                    <h3 class="tr-wrap mt-2 text-base font-semibold leading-snug text-slate-900">{{ $doc->judul }}</h3>

                    <div class="mt-3 space-y-1.5 text-sm">
                        <div class="flex items-baseline gap-3">
                            <span class="w-8 shrink-0 text-xs text-slate-400">Dari</span>
                            <span class="tr-wrap text-slate-700">{{ $doc->pengirim->name ?? '-' }}</span>
                        </div>
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <span class="w-8 shrink-0 text-xs text-slate-400">Ke</span>
                            <span class="tr-wrap text-slate-700">{{ $doc->penerima->name ?? '-' }}</span>
                            @if($otherRecipients->count() > 0)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">+{{ $otherRecipients->count() }} lainnya</span>
                            @endif
                        </div>
                        @if($isSuper)
                            <div class="flex items-baseline gap-3">
                                <span class="w-8 shrink-0 text-xs text-slate-400">Unit</span>
                                <span class="tr-wrap text-xs text-slate-500">{{ $doc->pengirim->company_name ?? '-' }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs tabular-nums text-slate-500">
                        <span>Dikirim {{ $doc->created_at->format('d/m/Y H:i') }}</span>
                        <span class="text-slate-400">Update {{ $doc->updated_at->format('d/m H:i') }}</span>
                    </div>

                    <span class="mt-3 inline-flex h-10 items-center justify-center gap-2 rounded-lg text-sm font-semibold {{ $needsAction ? 'bg-blue-600 text-white' : 'border border-slate-300 bg-white text-slate-700' }}">
                        {{ $needsAction ? $actionLabel : 'Lihat detail' }}
                        <i class="fas fa-chevron-right text-[10px]" aria-hidden="true"></i>
                    </span>
                </a>
            @endforeach
        </div>

        {{-- PAGINATION (buatan sendiri: selalu muat di HP, tidak bergantung markup bawaan Laravel) --}}
        @if($documents->hasPages())
            @php
                $pgCur  = $documents->currentPage();
                $pgLast = $documents->lastPage();
                $pgFrom = max(1, $pgCur - 2);
                $pgTo   = min($pgLast, $pgCur + 2);
            @endphp
            <nav class="tr-pager" aria-label="Halaman daftar dokumen">
                @if($documents->onFirstPage())
                    <span class="tr-pg is-disabled" aria-disabled="true" aria-label="Halaman sebelumnya">&lsaquo;</span>
                @else
                    <a class="tr-pg" href="{{ $documents->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">&lsaquo;</a>
                @endif

                @if($pgFrom > 1)
                    <a class="tr-pg tr-opt" href="{{ $documents->url(1) }}">1</a>
                    @if($pgFrom > 2)<span class="tr-dots tr-opt" aria-hidden="true">&hellip;</span>@endif
                @endif

                @for($p = $pgFrom; $p <= $pgTo; $p++)
                    @if($p === $pgCur)
                        <span class="tr-pg is-current" aria-current="page">{{ $p }}</span>
                    @else
                        <a class="tr-pg" href="{{ $documents->url($p) }}">{{ $p }}</a>
                    @endif
                @endfor

                @if($pgTo < $pgLast)
                    @if($pgTo < $pgLast - 1)<span class="tr-dots tr-opt" aria-hidden="true">&hellip;</span>@endif
                    <a class="tr-pg tr-opt" href="{{ $documents->url($pgLast) }}">{{ $pgLast }}</a>
                @endif

                @if($documents->hasMorePages())
                    <a class="tr-pg" href="{{ $documents->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">&rsaquo;</a>
                @else
                    <span class="tr-pg is-disabled" aria-disabled="true" aria-label="Halaman berikutnya">&rsaquo;</span>
                @endif
            </nav>
            <p class="text-center text-xs text-slate-500">Halaman {{ $pgCur }} dari {{ $pgLast }}</p>
        @endif
    @endif
</div>

<script>
    // Tombol Bantuan: buka/tutup panel petunjuk
    (function () {
        const btn = document.getElementById('toggleHelp');
        const box = document.getElementById('helpPanel');
        const close = document.getElementById('closeHelp');
        if (!btn || !box) return;
        function setOpen(open) {
            box.classList.toggle('hidden', !open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        btn.addEventListener('click', function () { setOpen(box.classList.contains('hidden')); });
        if (close) close.addEventListener('click', function () { setOpen(false); btn.focus(); });
    })();

    // Buka/tutup filter tanggal di layar sempit
    (function () {
        const btn = document.getElementById('toggleDates');
        const box = document.getElementById('dateFilters');
        if (!btn || !box) return;
        btn.addEventListener('click', function () {
            const nowHidden = box.classList.toggle('hidden');
            btn.setAttribute('aria-expanded', nowHidden ? 'false' : 'true');
        });
    })();
</script>

<style>
    /* Teks panjang turun ke baris berikutnya (tidak melebarkan layar HP) */
    .tr-wrap { overflow-wrap: anywhere; word-break: break-word; min-width: 0; }

    /* Kartu HP/tablet: kolom grid dikunci (minmax(0,1fr)) supaya teks panjang tidak melebarkan halaman */
    .tr-cards { display: grid; gap: .75rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 768px) { .tr-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .tr-cards { display: none; } }
    /* Tab status: HP = grid 2 kolom (turun ke bawah, tanpa geser kiri-kanan); >=640px = garis bawah */
    .tr-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    .tr-tabs .tr-tab { display: flex; align-items: center; justify-content: space-between; gap: .5rem; min-width: 0;
        padding: .65rem .75rem; border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff;
        font-size: .875rem; font-weight: 500; color: #64748b; transition: color .15s, background-color .15s, border-color .15s; }
    .tr-tabs .tr-tab:first-child { grid-column: 1 / -1; }
    .tr-tabs .tr-tab.is-active { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; }
    @media (min-width: 640px) {
        .tr-tabs { display: flex; flex-wrap: wrap; gap: 0 1.5rem; border-bottom: 1px solid #e2e8f0; }
        .tr-tabs .tr-tab { justify-content: flex-start; padding: .25rem .25rem .75rem; border: 0; border-bottom: 2px solid transparent;
            border-radius: 0; background: transparent; margin-bottom: -1px; }
        .tr-tabs .tr-tab:first-child { grid-column: auto; }
        .tr-tabs .tr-tab:hover { border-bottom-color: #cbd5e1; color: #334155; }
        .tr-tabs .tr-tab.is-active { border-bottom-color: #2563eb; background: transparent; color: #2563eb; }
    }

    /* Pemisah Dokumen Masuk / Keluar */
    .tr-seg { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    .tr-seg > a { display: flex; align-items: center; gap: .65rem; min-width: 0; padding: .7rem .75rem;
        border: 1px solid #e2e8f0; border-radius: .75rem; background: #fff; color: #475569; transition: border-color .15s, background-color .15s; }
    .tr-seg > a:hover { border-color: #cbd5e1; background: #f8fafc; }
    .tr-seg > a.is-active { border-color: #2563eb; background: #eff6ff; color: #1e3a8a; box-shadow: inset 0 0 0 1px #2563eb; }
    .tr-seg-ico { display: flex; align-items: center; justify-content: center; flex-shrink: 0; width: 2.25rem; height: 2.25rem;
        border-radius: 9999px; background: #f1f5f9; color: #64748b; }
    .tr-seg > a.is-active .tr-seg-ico { background: #2563eb; color: #fff; }
    .tr-badge { flex-shrink: 0; min-width: 1.5rem; padding: .1rem .5rem; border-radius: 9999px; font-size: .75rem; font-weight: 600; text-align: center; }
    .tr-badge-amber { background: #f59e0b; color: #fff; }
    .tr-badge-rose { background: #f43f5e; color: #fff; }
    .tr-seg.has-3 > a:last-child { grid-column: 1 / -1; }
    @media (min-width: 640px) { .tr-seg.has-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .tr-seg.has-3 > a:last-child { grid-column: auto; } }

    /* Petunjuk alur */
    .tr-steps { display: grid; gap: 1rem; list-style: none; margin: 0; }
    @media (min-width: 768px) { .tr-steps { grid-template-columns: repeat(3, minmax(0, 1fr)); } }

    /* Pengaman: isi halaman tidak boleh melebarkan layout (layout memakai flex-1 tanpa min-w-0) */
    .tr-page { max-width: 100%; min-width: 0; overflow-x: clip; contain: inline-size; }

    /* Pagination */
    .tr-pager { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem; max-width: 100%; }
    .tr-pg { display: inline-flex; align-items: center; justify-content: center; min-width: 2.5rem; height: 2.5rem; padding: 0 .65rem;
        border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff; color: #475569; font-size: .8125rem; line-height: 1; }
    a.tr-pg:hover { background: #f8fafc; border-color: #cbd5e1; color: #334155; }
    .tr-pg.is-current { border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8; font-weight: 600; }
    .tr-pg.is-disabled { opacity: .45; }
    .tr-dots { display: inline-flex; align-items: center; padding: 0 .2rem; color: #94a3b8; }
    /* Di HP: tanpa lompat ke halaman pertama/terakhir supaya muat satu baris */
    @media (max-width: 639px) { .tr-opt { display: none; } }
</style>
@endsection