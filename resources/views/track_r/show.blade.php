@extends('layouts.app-sidebar')

@section('content')
@php
    $userStatus = $document->statusForUser(auth()->user());
    $imageExts  = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $fotos      = $document->fotos ?? collect();

    $isCurrentRecipient = auth()->id() == $document->penerima_id;
    $isSender           = auth()->id() == $document->pengirim_id;
    $isPending          = in_array($document->status, ['dikirim', 'diteruskan']);

    // Siapa boleh apa (harus sama dengan guard di TrackRController)
    $canTerima   = $isCurrentRecipient && $isPending;
    $canTolak    = $isCurrentRecipient && $isPending;
    $canTeruskan = ($isCurrentRecipient && in_array($document->status, ['dikirim', 'diteruskan', 'diterima']))
                   || ($isSender && ($isPending || $document->status === 'ditolak'));
    $canAct      = $canTerima || $canTolak || $canTeruskan;

    // Pengirim setelah ditolak = Kirim Ulang; pengirim saat masih menunggu = Alihkan
    $teruskanMode = $document->status === 'ditolak' ? 'kirim_ulang'
                  : (($isSender && !$isCurrentRecipient) ? 'alihkan' : 'teruskan');
    $teruskanText = [
        'teruskan'    => ['btn' => 'Teruskan',    'label' => 'Teruskan ke',    'submit' => 'Teruskan dokumen', 'hint' => 'Instruksi untuk penerima berikutnya'],
        'alihkan'     => ['btn' => 'Alihkan',     'label' => 'Alihkan ke',     'submit' => 'Alihkan dokumen',  'hint' => 'Alasan dialihkan'],
        'kirim_ulang' => ['btn' => 'Kirim Ulang', 'label' => 'Kirim ulang ke', 'submit' => 'Kirim ulang',      'hint' => 'Apa yang sudah diperbaiki'],
    ][$teruskanMode];

    $initials = fn ($n) => collect(preg_split('/\s+/', trim((string) $n)))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';

    // Nama file di storage berformat "nama_<time>_<uniqid>.ext" — tampilkan nama aslinya saja
    $displayName = fn ($n) => preg_replace('/_\d{10}_[0-9a-f]{13}(?=\.[^.]+$)/i', '', (string) $n) ?: (string) $n;

    $imageFotos = $fotos->filter(fn ($f) => in_array(strtolower($f->tipe), $imageExts))->values();
    $imageIds   = $imageFotos->pluck('id');
    $imageList  = $imageFotos->map(fn ($f) => [
        'url'      => route('track-foto.view', $f->id),
        'name'     => $displayName($f->nama_file),
        'download' => route('track-foto.download', $f->id),
    ])->values();

    $userList = $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->values();

    $logVerb  = ['kirim' => 'mengirim', 'terima' => 'menerima', 'tolak' => 'menolak dokumen dari', 'teruskan' => 'meneruskan', 'kirim_ulang' => 'mengirim ulang'];
    $logIcon  = ['kirim' => 'paper-plane', 'terima' => 'check', 'tolak' => 'times', 'teruskan' => 'share', 'kirim_ulang' => 'redo'];
    $logTone  = [
        'kirim'    => 'bg-blue-100 text-blue-700',
        'terima'   => 'bg-emerald-100 text-emerald-700',
        'tolak'    => 'bg-rose-100 text-rose-700',
        'teruskan' => 'bg-violet-100 text-violet-700',
        'kirim_ulang' => 'bg-amber-100 text-amber-700',
    ];
    $boilerplateNotes = ['Dokumen dikirim', 'Dokumen diteruskan'];

    // Posisi dokumen & langkah berikutnya, sesuai peran user
    $senderName = $document->pengirim->name ?? '-';
    $holderName = $document->penerima->name ?? '-';
    $lastReject = $document->logs->where('aksi', 'tolak')->last();
    $flow = null;
    if ($isCurrentRecipient && $isPending) {
        $flow = ['box' => 'border-amber-200 bg-amber-50', 'ico' => 'text-amber-600', 'txt' => 'text-amber-900', 'icon' => 'inbox',
                 'title' => 'Dokumen ini menunggu tindakan Anda',
                 'text'  => 'Dikirim oleh ' . $senderName . '. Periksa lampiran, lalu pilih Terima, Tolak, atau Teruskan di panel Tindakan.', 'cta' => true];
    } elseif ($isCurrentRecipient && in_array($document->status, ['diterima', 'selesai'], true)) {
        $flow = ['box' => 'border-emerald-200 bg-emerald-50', 'ico' => 'text-emerald-600', 'txt' => 'text-emerald-900', 'icon' => 'check-circle',
                 'title' => 'Anda sudah menerima dokumen ini',
                 'text'  => 'Jika perlu ditindaklanjuti orang lain, gunakan tombol Teruskan di panel Tindakan.', 'cta' => $canTeruskan];
    } elseif ($isCurrentRecipient && $document->status === 'ditolak') {
        $flow = ['box' => 'border-slate-200 bg-slate-50', 'ico' => 'text-slate-500', 'txt' => 'text-slate-800', 'icon' => 'undo',
                 'title' => 'Anda menolak dokumen ini',
                 'text'  => 'Dokumen dikembalikan ke ' . $senderName . ' untuk diperbaiki dan dikirim ulang.', 'cta' => false];
    } elseif ($isSender && $isPending) {
        $flow = ['box' => 'border-blue-200 bg-blue-50', 'ico' => 'text-blue-600', 'txt' => 'text-blue-900', 'icon' => 'paper-plane',
                 'title' => 'Dokumen Anda sedang berada di ' . $holderName,
                 'text'  => 'Menunggu ' . $holderName . ' menerima. Jika salah tujuan, Anda bisa mengalihkannya lewat panel Tindakan.', 'cta' => $canTeruskan];
    } elseif ($isSender && $document->status === 'ditolak') {
        $flow = ['box' => 'border-rose-200 bg-rose-50', 'ico' => 'text-rose-600', 'txt' => 'text-rose-900', 'icon' => 'times-circle',
                 'title' => 'Dokumen ditolak oleh ' . ($lastReject->dariUser->name ?? $holderName),
                 'text'  => (filled($lastReject->catatan ?? null) ? 'Alasan: ' . $lastReject->catatan . '. ' : '') . 'Perbaiki dokumen, lalu pilih Kirim Ulang di panel Tindakan.', 'cta' => $canTeruskan];
    } elseif ($isSender && in_array($document->status, ['diterima', 'selesai'], true)) {
        $flow = ['box' => 'border-emerald-200 bg-emerald-50', 'ico' => 'text-emerald-600', 'txt' => 'text-emerald-900', 'icon' => 'check-circle',
                 'title' => 'Dokumen Anda sudah diterima oleh ' . $holderName,
                 'text'  => 'Tidak ada tindakan lagi yang diperlukan.', 'cta' => false];
    }

    $textarea = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-base text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 sm:text-sm';
@endphp

<div class="tr-page mx-auto max-w-6xl space-y-5 text-slate-800">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a href="{{ route('track-r.index') }}"
               class="inline-flex h-9 items-center gap-2 rounded-lg pr-3 text-sm font-medium text-slate-500 transition hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i> Kembali ke daftar
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-2">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Tanda terima dokumen</h1>
                @include('track_r._status-badge', ['status' => $document->status, 'label' => $userStatus['label']])
            </div>
        </div>
        <a href="{{ route('track-r.pdf', $document->id) }}"
           class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 sm:h-10">
            <i class="fas fa-file-pdf text-red-500" aria-hidden="true"></i> Unduh PDF
        </a>
    </div>

    {{-- ERROR DARI AKSI (tolak / teruskan) --}}
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-disc space-y-0.5 pl-5">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- POSISI DOKUMEN & LANGKAH BERIKUTNYA --}}
    @if($flow)
        <div class="rounded-xl border p-4 {{ $flow['box'] }}">
            <div class="flex items-start gap-3">
                <i class="fas fa-{{ $flow['icon'] }} mt-0.5 text-xl {{ $flow['ico'] }}" aria-hidden="true"></i>
                <div class="min-w-0 flex-1">
                    <p class="tr-wrap font-semibold {{ $flow['txt'] }}">{{ $flow['title'] }}</p>
                    <p class="tr-wrap mt-0.5 text-sm {{ $flow['txt'] }} opacity-90">{{ $flow['text'] }}</p>
                    <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs {{ $flow['txt'] }} opacity-80">
                        <span>Pengirim: <b>{{ $senderName }}</b></span>
                        <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i>
                        <span>Sekarang di: <b>{{ $holderName }}</b></span>
                    </p>
                </div>
            </div>
            @if($flow['cta'] && $canAct)
                <a href="#panel-tindakan"
                   class="mt-3 inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-white px-4 text-sm font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 sm:w-auto">
                    <i class="fas fa-arrow-down text-xs" aria-hidden="true"></i> Ke panel Tindakan
                </a>
            @endif
        </div>
    @endif

    {{-- AKSES DITOLAK --}}
    @if(!$document->hasAccess(auth()->user()) && !auth()->user()->isSuperadminTrack())
        <div role="alert" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4">
            <i class="fas fa-exclamation-triangle mt-0.5 text-red-500" aria-hidden="true"></i>
            <div>
                <p class="font-semibold text-red-800">Akses ditolak</p>
                <p class="text-sm text-red-700">Anda tidak memiliki akses ke dokumen ini.</p>
            </div>
        </div>
    @endif

    {{--
        TATA LETAK
        - HP/tablet: satu kolom, urutan: ringkasan → tindakan → lampiran → riwayat
          (wrapper memakai "contents" supaya anak-anaknya bisa diurutkan dengan order-*)
        - Laptop/desktop (lg): 2/3 kiri (ringkasan, lampiran) + 1/3 kanan (tindakan, riwayat)
    --}}
    <div class="flex flex-col gap-6 lg:grid lg:grid-cols-3 lg:items-start">

        {{-- KOLOM KIRI --}}
        <div class="contents lg:col-span-2 lg:block lg:space-y-6">

            {{-- KARTU TANDA TERIMA --}}
            <article class="order-1 overflow-hidden rounded-xl border border-slate-200 bg-white lg:order-none">
                <div class="flex flex-col gap-2 bg-slate-900 px-5 py-4 text-white sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <p class="text-base font-semibold">Tanda terima dokumen</p>
                        <p class="text-xs text-slate-300">GA Portal – Sistem Track R</p>
                    </div>
                    <div class="sm:text-right">
                        <p class="tr-wrap font-mono text-base">No. {{ $document->nomor_dokumen }}</p>
                        <p class="text-xs text-slate-300">{{ $document->created_at->translatedFormat('d F Y') }}</p>
                    </div>
                </div>

                <div class="tr-fit gap-4 p-5 sm:p-6">
                    <div>
                        <p class="mb-2 text-xs font-medium text-slate-500">Pengirim</p>
                        <div class="flex items-center gap-3 rounded-lg bg-slate-50 p-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-600" aria-hidden="true">{{ $initials($document->pengirim->name ?? '') }}</div>
                            <div class="min-w-0">
                                <p class="tr-wrap font-medium text-slate-900">{{ $document->pengirim->name ?? '-' }}</p>
                                <p class="tr-wrap text-xs text-slate-500">{{ $document->pengirim->email ?? '' }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-medium text-slate-500">Penerima saat ini</p>
                        <div class="flex items-center gap-3 rounded-lg bg-slate-50 p-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-700" aria-hidden="true">{{ $initials($document->penerima->name ?? '') }}</div>
                            <div class="min-w-0">
                                <p class="tr-wrap font-medium text-slate-900">{{ $document->penerima->name ?? '-' }}</p>
                                <p class="tr-wrap text-xs text-slate-500">{{ $document->penerima->email ?? '' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="md-span">
                        <p class="mb-2 text-xs font-medium text-slate-500">Judul dan keterangan</p>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="tr-wrap font-semibold text-slate-900">{{ $document->judul }}</p>
                            @if(filled($document->keterangan))
                                <p class="tr-wrap mt-1.5 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $document->keterangan }}</p>
                            @else
                                <p class="mt-1.5 text-sm italic text-slate-400">Tidak ada keterangan</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    <p class="mb-3 text-xs font-medium text-slate-500">Riwayat penerima</p>
                    @if($document->recipients->count())
                        <ol class="flex flex-wrap items-center gap-y-2">
                            @foreach($document->recipients as $recip)
                                @php $isCurrent = $recip->id === $document->penerima_id; @endphp
                                <li class="flex min-w-0 max-w-full items-center">
                                    <span class="tr-wrap inline-flex max-w-full flex-wrap items-center gap-x-2 rounded-2xl px-3 py-1.5 text-sm {{ $isCurrent ? 'bg-blue-50 font-medium text-blue-800 ring-1 ring-inset ring-blue-300' : 'bg-slate-50 text-slate-600 ring-1 ring-inset ring-slate-200' }}"
                                          @if($isCurrent) aria-current="true" @endif>
                                        {{ $recip->name }}
                                        @if($recip->pivot->received_at)
                                            <span class="text-xs tabular-nums {{ $isCurrent ? 'text-blue-600' : 'text-slate-400' }}">{{ \Carbon\Carbon::parse($recip->pivot->received_at)->format('d/m H:i') }}</span>
                                        @endif
                                    </span>
                                    @unless($loop->last)
                                        <i class="fas fa-chevron-right mx-2 text-[10px] text-slate-300" aria-hidden="true"></i>
                                    @endunless
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-sm text-slate-500">Belum ada penerima.</p>
                    @endif
                </div>

                <dl class="flex flex-wrap items-center gap-x-8 gap-y-1 border-t border-slate-100 bg-slate-50/60 px-5 py-3 text-sm sm:px-6">
                    <div class="flex items-center gap-2">
                        <dt class="text-slate-500">Status dokumen</dt>
                        <dd class="font-medium text-slate-800">{{ ucfirst($document->status) }}</dd>
                    </div>
                </dl>
            </article>

            {{-- LAMPIRAN --}}
            @if($fotos->count() > 0)
                <section class="order-3 overflow-hidden rounded-xl border border-slate-200 bg-white lg:order-none" aria-labelledby="lampiran-title">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5 sm:px-6">
                        <h2 id="lampiran-title" class="text-base font-semibold text-slate-900">Lampiran</h2>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $fotos->count() }} file</span>
                    </div>

                    <ul class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 sm:p-5 xl:grid-cols-4">
                        @foreach($fotos as $foto)
                            @php
                                $ext     = strtolower($foto->tipe);
                                $isImage = in_array($ext, $imageExts);
                                $name    = $displayName($foto->nama_file);
                                $viewUrl = route('track-foto.view', $foto->id);
                                $dlUrl   = route('track-foto.download', $foto->id);
                                $icon = match($ext) {
                                    'pdf'         => 'fa-file-pdf text-red-500',
                                    'doc', 'docx' => 'fa-file-word text-blue-500',
                                    'xls', 'xlsx' => 'fa-file-excel text-emerald-600',
                                    'ppt', 'pptx' => 'fa-file-powerpoint text-orange-500',
                                    'txt'         => 'fa-file-alt text-slate-500',
                                    'zip', 'rar'  => 'fa-file-archive text-amber-600',
                                    default       => 'fa-file text-slate-500',
                                };
                            @endphp
                            <li class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white">
                                @if($isImage)
                                    <button type="button" onclick="openLightbox({{ $imageIds->search($foto->id) }})"
                                            aria-label="Perbesar {{ $name }}"
                                            class="relative block aspect-square w-full overflow-hidden bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500">
                                        <img src="{{ $viewUrl }}" alt="" loading="lazy" draggable="false"
                                             class="h-full w-full object-cover"
                                             onerror="this.onerror=null; this.src='{{ $viewUrl }}?nocache=' + Date.now();">
                                    </button>
                                @else
                                    <a href="{{ $dlUrl }}" aria-label="Unduh {{ $name }}"
                                       class="flex aspect-square w-full flex-col items-center justify-center gap-2 bg-slate-50 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500">
                                        <i class="fas {{ $icon }} text-4xl" aria-hidden="true"></i>
                                        <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">{{ strtoupper($ext) }}</span>
                                    </a>
                                @endif

                                <div class="border-t border-slate-100 p-2.5">
                                    <p class="tr-wrap text-xs font-medium text-slate-700" title="{{ $name }}">{{ $name }}</p>
                                    <div class="mt-1.5 flex items-center justify-between gap-2">
                                        <span class="text-xs tabular-nums text-slate-500">{{ number_format($foto->ukuran / 1024, 1) }} KB</span>
                                        <span class="flex gap-1">
                                            @if($isImage)
                                                <a href="{{ $viewUrl }}" target="_blank" rel="noopener" title="Buka di tab baru" aria-label="Buka {{ $name }} di tab baru"
                                                   class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                                    <i class="fas fa-external-link-alt text-xs" aria-hidden="true"></i>
                                                </a>
                                            @endif
                                            <a href="{{ $dlUrl }}" title="Unduh" aria-label="Unduh {{ $name }}"
                                               class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                                <i class="fas fa-download text-xs" aria-hidden="true"></i>
                                            </a>
                                        </span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @else
                <div class="order-3 rounded-xl border border-dashed border-slate-300 bg-white px-6 py-8 text-center lg:order-none">
                    <i class="far fa-file text-2xl text-slate-300" aria-hidden="true"></i>
                    <p class="mt-2 text-sm font-medium text-slate-600">Tidak ada lampiran</p>
                    <p class="text-xs text-slate-400">Dokumen ini dikirim tanpa file.</p>
                </div>
            @endif
        </div>

        {{-- KOLOM KANAN --}}
        <div class="contents lg:block lg:space-y-6">

            {{-- TINDAKAN --}}
            @if($canAct)
                <section id="panel-tindakan" class="order-2 scroll-mt-4 rounded-xl border border-slate-200 bg-white p-4 sm:p-5 lg:order-none" aria-labelledby="aksi-title">
                    <h2 id="aksi-title" class="text-base font-semibold text-slate-900">Tindakan</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Pilih yang akan Anda lakukan pada dokumen ini.</p>

                    <div class="mt-4 flex flex-col gap-2 sm:flex-row lg:flex-col">
                        @if($canTerima)
                            <form action="{{ route('track-r.terima', $document->id) }}" method="POST" data-once class="sm:flex-1"
                                  onsubmit="return confirm('Terima dokumen ini? Tindakan ini tidak dapat dibatalkan.')">
                                @csrf
                                <button type="submit"
                                        class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 disabled:opacity-60">
                                    <i class="fas fa-check-circle" aria-hidden="true"></i> Terima
                                </button>
                            </form>
                        @endif

                        @if($canTolak)
                            <button type="button" id="btnTolak" onclick="togglePanel('tolak')" aria-expanded="false" aria-controls="tolakForm"
                                    class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-red-300 bg-white px-4 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 sm:flex-1">
                                <i class="fas fa-times-circle" aria-hidden="true"></i> Tolak
                            </button>
                        @endif

                        @if($canTeruskan)
                        <button type="button" id="btnTeruskan" onclick="togglePanel('teruskan')" aria-expanded="false" aria-controls="teruskanForm"
                                class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 sm:flex-1">
                            <i class="fas fa-{{ $teruskanMode === 'kirim_ulang' ? 'redo' : 'share-alt' }}" aria-hidden="true"></i> {{ $teruskanText['btn'] }}
                        </button>
                        @endif
                    </div>

                    {{-- Form Tolak --}}
                    @if($canTolak)
                        <div id="tolakForm" class="mt-4 hidden rounded-lg border border-red-200 bg-red-50/60 p-4">
                            <form action="{{ route('track-r.tolak', $document->id) }}" method="POST" data-once>
                                @csrf
                                <label for="tolak_catatan" class="mb-1.5 block text-sm font-medium text-slate-700">
                                    Alasan penolakan <span class="text-red-600" aria-hidden="true">*</span>
                                </label>
                                <textarea id="tolak_catatan" name="catatan" rows="3" required
                                          placeholder="Jelaskan kenapa dokumen ini ditolak" class="{{ $textarea }}"></textarea>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button type="button" onclick="togglePanel('tolak')"
                                            class="h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                                    <button type="submit"
                                            class="h-10 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">Tolak dokumen</button>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if($canTeruskan)
                    {{-- Form Teruskan --}}
                    <div id="teruskanForm" class="mt-4 hidden rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <form action="{{ route('track-r.teruskan', $document->id) }}" method="POST" data-once id="formTeruskan">
                            @csrf
                            <label for="penerima_search" class="mb-1.5 block text-sm font-medium text-slate-700">
                                {{ $teruskanText['label'] }} <span class="text-red-600" aria-hidden="true">*</span>
                            </label>

                            <input type="hidden" name="penerima_id" id="penerima_id">

                            <div id="recipientSearchWrap" class="relative">
                                <input type="text" id="penerima_search" autocomplete="off"
                                       role="combobox" aria-expanded="false" aria-controls="searchResults" aria-autocomplete="list"
                                       placeholder="Ketik nama atau email (min. 3 huruf)"
                                       class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 sm:h-10 sm:text-sm">
                                <ul id="searchResults" role="listbox" aria-label="Hasil pencarian penerima"
                                    class="absolute z-20 mt-1 hidden max-h-60 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg"></ul>
                            </div>

                            <div id="selectedUserDisplay" class="hidden">
                                <div class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 p-2.5">
                                    <div class="min-w-0 flex-1">
                                        <p id="selectedUserName" class="tr-wrap text-sm font-semibold text-slate-900"></p>
                                        <p id="selectedUserEmail" class="tr-wrap text-xs text-slate-500"></p>
                                    </div>
                                    <button type="button" id="clearRecipient"
                                            class="h-9 shrink-0 rounded-lg px-3 text-sm font-medium text-blue-700 hover:bg-blue-100">Ganti</button>
                                </div>
                            </div>
                            <p id="recipientError" class="mt-1.5 hidden text-xs text-red-600" role="alert">Pilih penerima dari hasil pencarian.</p>

                            <label for="teruskan_catatan" class="mb-1.5 mt-4 block text-sm font-medium text-slate-700">
                                Catatan <span class="font-normal text-slate-400">(opsional)</span>
                            </label>
                            <textarea id="teruskan_catatan" name="catatan" rows="2" maxlength="500"
                                      placeholder="{{ $teruskanText['hint'] }}" class="{{ $textarea }}"></textarea>

                            <div class="mt-3 flex justify-end gap-2">
                                <button type="button" onclick="togglePanel('teruskan')"
                                        class="h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                                <button type="submit"
                                        class="h-10 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">{{ $teruskanText['submit'] }}</button>
                            </div>
                        </form>
                    </div>
                    @endif
                </section>
            @endif

            {{-- RIWAYAT AKTIVITAS --}}
            <section class="order-4 overflow-hidden rounded-xl border border-slate-200 bg-white lg:order-none" aria-labelledby="riwayat-title">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                    <h2 id="riwayat-title" class="text-base font-semibold text-slate-900">Riwayat aktivitas</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $document->logs->count() }}</span>
                </div>

                @if($document->logs->count())
                    <ol class="p-5 lg:max-h-[32rem] lg:overflow-y-auto">
                        @foreach($document->logs as $log)
                            @php
                                $aksi = $log->aksi;
                                $tone = $logTone[$aksi] ?? 'bg-slate-100 text-slate-600';
                                $icon = $logIcon[$aksi] ?? 'circle';
                                $verb = $logVerb[$aksi] ?? $aksi;
                                $showNote = filled($log->catatan) && !in_array($log->catatan, $boilerplateNotes);
                            @endphp
                            <li class="relative pl-11 {{ $loop->last ? '' : 'pb-5' }}">
                                @unless($loop->last)
                                    <span class="absolute bottom-0 left-4 top-8 w-px bg-slate-200" aria-hidden="true"></span>
                                @endunless
                                <span class="absolute left-0 top-0 flex h-8 w-8 items-center justify-center rounded-full {{ $tone }}" aria-hidden="true">
                                    <i class="fas fa-{{ $icon }} text-xs"></i>
                                </span>
                                <p class="tr-wrap text-sm leading-snug text-slate-800">
                                    <span class="font-medium">{{ $log->dariUser->name ?? 'Sistem' }}</span>
                                    {{ $verb }}
                                    @if($log->keUser && $aksi !== 'terima') {{ $aksi === 'tolak' ? '' : 'kepada' }} <span class="font-medium">{{ $log->keUser->name }}</span> @endif
                                </p>
                                @if($showNote)
                                    <p class="tr-wrap mt-1 whitespace-pre-line rounded-md bg-slate-50 px-2.5 py-1.5 text-xs text-slate-600">{{ $log->catatan }}</p>
                                @endif
                                <time class="mt-1 block text-xs tabular-nums text-slate-400" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y H:i') }}</time>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="px-5 py-8 text-center text-sm text-slate-500">
                        <i class="fas fa-history mb-2 block text-2xl text-slate-300" aria-hidden="true"></i>
                        Belum ada aktivitas
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>

{{-- LIGHTBOX --}}
<div id="lightboxModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-label="Pratinjau gambar">
    <div class="absolute inset-0 bg-black/90" onclick="closeLightbox()"></div>

    <div class="pointer-events-none absolute inset-0 flex items-center justify-center px-3 pb-16 pt-16 sm:px-16">
        <img id="lightboxImage" src="" alt="" class="pointer-events-auto max-h-full max-w-full rounded-lg object-contain shadow-2xl">
    </div>

    <div class="absolute inset-x-0 top-0 flex items-center justify-between gap-3 px-3 pb-3 text-white"
         style="padding-top: max(0.75rem, env(safe-area-inset-top));">
        <span id="lightboxCounter" class="rounded-full bg-white/10 px-3 py-1.5 text-sm tabular-nums"></span>
        <div class="flex items-center gap-2">
            <a id="lightboxDownload" href="#"
               class="inline-flex h-11 items-center gap-2 rounded-full bg-white/10 px-4 text-sm font-medium transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                <i class="fas fa-download" aria-hidden="true"></i> <span class="hidden sm:inline">Unduh</span>
            </a>
            <button type="button" id="lightboxClose" onclick="closeLightbox()" aria-label="Tutup pratinjau"
                    class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <button type="button" id="lightboxPrev" onclick="prevImage()" aria-label="Gambar sebelumnya"
            class="absolute left-2 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white sm:left-4">
        <i class="fas fa-chevron-left" aria-hidden="true"></i>
    </button>
    <button type="button" id="lightboxNext" onclick="nextImage()" aria-label="Gambar berikutnya"
            class="absolute right-2 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white sm:right-4">
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
    </button>

    <div class="absolute inset-x-0 bottom-0 flex justify-center px-4 pb-4" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
        <span id="lightboxCaption" class="max-w-full truncate rounded-full bg-white/10 px-4 py-1.5 text-sm text-white"></span>
    </div>
</div>

<script>
/* =========================================================
   LIGHTBOX
========================================================= */
const IMAGES = @json($imageList);
let currentImageIndex = 0;
let lastFocused = null;

function renderLightbox() {
    const item = IMAGES[currentImageIndex];
    if (!item) return;
    const img = document.getElementById('lightboxImage');
    img.src = item.url;
    img.alt = item.name;
    document.getElementById('lightboxCaption').textContent = item.name;
    document.getElementById('lightboxCounter').textContent = (currentImageIndex + 1) + ' / ' + IMAGES.length;
    document.getElementById('lightboxDownload').href = item.download;
    const multi = IMAGES.length > 1;
    document.getElementById('lightboxPrev').classList.toggle('hidden', !multi);
    document.getElementById('lightboxNext').classList.toggle('hidden', !multi);
}
function openLightbox(index) {
    if (!IMAGES.length) return;
    currentImageIndex = (index >= 0 && index < IMAGES.length) ? index : 0;
    lastFocused = document.activeElement;
    renderLightbox();
    document.getElementById('lightboxModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('lightboxClose').focus();
}
function closeLightbox() {
    document.getElementById('lightboxModal').classList.add('hidden');
    document.body.style.overflow = '';
    if (lastFocused && lastFocused.focus) lastFocused.focus();
}
function nextImage() { if (IMAGES.length > 1) { currentImageIndex = (currentImageIndex + 1) % IMAGES.length; renderLightbox(); } }
function prevImage() { if (IMAGES.length > 1) { currentImageIndex = (currentImageIndex - 1 + IMAGES.length) % IMAGES.length; renderLightbox(); } }

document.addEventListener('keydown', function (e) {
    const modal = document.getElementById('lightboxModal');
    if (modal.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeLightbox();
    else if (e.key === 'ArrowRight') { e.preventDefault(); nextImage(); }
    else if (e.key === 'ArrowLeft')  { e.preventDefault(); prevImage(); }
});

// Geser kiri/kanan di layar sentuh
(function () {
    const modal = document.getElementById('lightboxModal');
    let startX = 0;
    modal.addEventListener('touchstart', (e) => { startX = e.changedTouches[0].screenX; }, { passive: true });
    modal.addEventListener('touchend', (e) => {
        const dx = e.changedTouches[0].screenX - startX;
        if (dx < -50) nextImage();
        if (dx > 50) prevImage();
    }, { passive: true });
})();

/* =========================================================
   TINDAKAN — buka/tutup panel & cegah klik ganda
========================================================= */
function togglePanel(which) {
    const panels = { tolak: document.getElementById('tolakForm'), teruskan: document.getElementById('teruskanForm') };
    const buttons = { tolak: document.getElementById('btnTolak'), teruskan: document.getElementById('btnTeruskan') };
    Object.keys(panels).forEach(function (key) {
        const panel = panels[key];
        if (!panel) return;
        const show = key === which ? panel.classList.contains('hidden') : false;
        panel.classList.toggle('hidden', !show);
        if (buttons[key]) buttons[key].setAttribute('aria-expanded', show ? 'true' : 'false');
        if (show) {
            const field = panel.querySelector('textarea, input[type="text"]');
            if (field) field.focus();
        }
    });
}

document.querySelectorAll('form[data-once]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
        // Dicek setelah semua handler lain selesai: kalau submit dibatalkan (validasi gagal / confirm ditolak),
        // tombol dibiarkan aktif supaya bisa dicoba lagi.
        setTimeout(function () {
            if (e.defaultPrevented) return;
            f.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = true; });
        }, 0);
    });
});

@if($canTeruskan)
/* =========================================================
   TERUSKAN — combobox penerima (keyboard friendly)
========================================================= */
(function () {
    const USERS = @json($userList);
    const $ = (id) => document.getElementById(id);

    const form = $('formTeruskan');
    const search = $('penerima_search');
    const results = $('searchResults');
    const hidden = $('penerima_id');
    const selectedBox = $('selectedUserDisplay');
    const searchWrap = $('recipientSearchWrap');
    const errorEl = $('recipientError');
    let matches = [];
    let active = -1;

    function openResults()  { results.classList.remove('hidden'); search.setAttribute('aria-expanded', 'true'); }
    function closeResults() {
        results.classList.add('hidden');
        search.setAttribute('aria-expanded', 'false');
        search.removeAttribute('aria-activedescendant');
        active = -1;
    }
    function showMessage(text) {
        matches = [];
        results.innerHTML = '';
        const li = document.createElement('li');
        li.setAttribute('role', 'presentation');
        li.className = 'px-3 py-3 text-center text-sm text-slate-500';
        li.textContent = text;
        results.appendChild(li);
        openResults();
    }
    function setActive(i) {
        results.querySelectorAll('[role="option"]').forEach(function (el, idx) {
            const on = idx === i;
            el.classList.toggle('bg-blue-50', on);
            el.setAttribute('aria-selected', on ? 'true' : 'false');
            if (on) { search.setAttribute('aria-activedescendant', el.id); el.scrollIntoView({ block: 'nearest' }); }
        });
        active = i;
    }
    function renderResults() {
        results.innerHTML = '';
        const shown = matches.slice(0, 8);
        shown.forEach(function (u, i) {
            const li = document.createElement('li');
            li.id = 'opt-' + i;
            li.setAttribute('role', 'option');
            li.className = 'cursor-pointer px-3 py-2.5 hover:bg-slate-50';
            const n = document.createElement('div');
            n.className = 'text-sm font-medium text-slate-900';
            n.textContent = u.name;
            const m = document.createElement('div');
            m.className = 'text-xs text-slate-500';
            m.textContent = u.email;
            li.append(n, m);
            li.addEventListener('click', function () { choose(u); });
            results.appendChild(li);
        });
        if (matches.length > shown.length) {
            const more = document.createElement('li');
            more.setAttribute('role', 'presentation');
            more.className = 'border-t border-slate-100 px-3 py-2 text-center text-xs text-slate-500';
            more.textContent = (matches.length - shown.length) + ' hasil lain, ketik lebih spesifik';
            results.appendChild(more);
        }
        openResults();
        setActive(0);
    }
    function choose(u) {
        hidden.value = u.id;
        $('selectedUserName').textContent = u.name;
        $('selectedUserEmail').textContent = u.email;
        selectedBox.classList.remove('hidden');
        searchWrap.classList.add('hidden');
        errorEl.classList.add('hidden');
        search.value = '';
        closeResults();
    }
    function clearRecipient() {
        hidden.value = '';
        selectedBox.classList.add('hidden');
        searchWrap.classList.remove('hidden');
        search.value = '';
        closeResults();
        search.focus();
    }

    search.addEventListener('input', function () {
        const term = search.value.trim().toLowerCase();
        if (!term.length) { closeResults(); return; }
        if (term.length < 3) { showMessage('Ketik minimal 3 huruf'); return; }
        matches = USERS.filter(function (u) { return (u.name + ' ' + u.email).toLowerCase().includes(term); });
        if (!matches.length) { showMessage('Tidak ada pengguna yang cocok'); return; }
        renderResults();
    });
    search.addEventListener('keydown', function (e) {
        const open = !results.classList.contains('hidden') && matches.length > 0;
        const last = Math.min(matches.length, 8) - 1;
        if (e.key === 'ArrowDown' && open) { e.preventDefault(); setActive(Math.min(active + 1, last)); }
        else if (e.key === 'ArrowUp' && open) { e.preventDefault(); setActive(Math.max(active - 1, 0)); }
        else if (e.key === 'Enter') { e.preventDefault(); if (open && active >= 0) choose(matches[active]); }
        else if (e.key === 'Escape') { closeResults(); }
    });
    $('clearRecipient').addEventListener('click', clearRecipient);
    document.addEventListener('click', function (e) { if (!searchWrap.contains(e.target)) closeResults(); });

    form.addEventListener('submit', function (e) {
        if (!hidden.value) {
            e.preventDefault();
            errorEl.classList.remove('hidden');
            search.focus();
        }
    });
})();
@endif
</script>
<style>

    /* Pengaman: isi halaman tidak boleh melebarkan layout (layout memakai flex-1 tanpa min-w-0) */
    .tr-page { max-width: 100%; min-width: 0; overflow-x: clip; contain: inline-size; }

    /* Teks panjang turun ke baris berikutnya (tidak melebarkan layar HP) */
    .tr-wrap { overflow-wrap: anywhere; word-break: break-word; min-width: 0; }

    /* Kolom grid dikunci supaya teks panjang tidak melebarkan halaman di HP */
    .tr-fit { display: grid; grid-template-columns: minmax(0, 1fr); }
    .tr-fit > .md-span { grid-column: auto; }
    @media (min-width: 768px) {
        .tr-fit { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tr-fit > .md-span { grid-column: 1 / -1; }
    }
</style>
@endsection