@extends('layouts.app-sidebar')

@section('content')
@php
    // Daftar penerima untuk pencarian di browser (di-encode aman lewat @json)
    $userList = $users->where('id', '!=', auth()->id())
        ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
        ->values();

    $base = 'h-11 w-full rounded-lg border bg-white px-3 text-base text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 sm:h-10 sm:text-sm';
    $ok   = 'border-slate-300 focus:border-blue-500 focus:ring-blue-500/30';
    $bad  = 'border-red-400 focus:border-red-500 focus:ring-red-500/30';
    $fileErrors = collect($errors->get('foto_dokumen.*'))->flatten()->unique();
@endphp

<div class="tr-page mx-auto max-w-6xl space-y-5 text-slate-800">

    {{-- HEADER --}}
    <div>
        <a href="{{ route('track-r.index') }}"
           class="inline-flex h-9 items-center gap-2 rounded-lg pr-3 text-sm font-medium text-slate-500 transition hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
            <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i> Kembali ke daftar
        </a>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Kirim dokumen</h1>
        <p class="mt-1 text-sm text-slate-500">Isi data dokumen, pilih penerima, lalu kirim. Kolom bertanda <span class="text-red-600">*</span> wajib diisi.</p>
        <p class="mt-1 text-sm text-slate-500">Setelah dikirim, dokumen muncul di <b>Dokumen Masuk</b> milik penerima, dan di <b>Dokumen Keluar</b> milik Anda.</p>
    </div>

    {{-- RINGKASAN ERROR --}}
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">Dokumen belum terkirim. Periksa isian berikut:</p>
            <ul class="mt-1.5 list-disc space-y-0.5 pl-5">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
            <p class="mt-2 text-red-700">Lampiran perlu dipilih ulang karena browser tidak menyimpannya.</p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">

        {{-- FORM --}}
        <form id="sendForm" method="POST" action="{{ route('track-r.store') }}" enctype="multipart/form-data" class="space-y-4 lg:col-span-2">
            @csrf

            <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">

                {{-- DATA DOKUMEN --}}
                <section class="space-y-4 p-4 sm:p-6" aria-labelledby="sec-data">
                    <h2 id="sec-data" class="text-base font-semibold text-slate-900">Data dokumen</h2>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="nomor_dokumen" class="mb-1.5 block text-sm font-medium text-slate-700">
                                Nomor dokumen <span class="text-red-600" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="nomor_dokumen" name="nomor_dokumen" required
                                   value="{{ old('nomor_dokumen') }}" placeholder="Contoh: DOC/2026/001"
                                   autocomplete="off" aria-describedby="hint-nomor"
                                   @if($errors->has('nomor_dokumen')) aria-invalid="true" @endif
                                   class="{{ $base }} {{ $errors->has('nomor_dokumen') ? $bad : $ok }}">
                            @error('nomor_dokumen')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p id="hint-nomor" class="mt-1.5 text-xs text-slate-500">Nomor harus unik.</p>
                        </div>

                        <div>
                            <label for="judul" class="mb-1.5 block text-sm font-medium text-slate-700">
                                Judul dokumen <span class="text-red-600" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="judul" name="judul" required
                                   value="{{ old('judul') }}" placeholder="Masukkan judul dokumen"
                                   @if($errors->has('judul')) aria-invalid="true" @endif
                                   class="{{ $base }} {{ $errors->has('judul') ? $bad : $ok }}">
                            @error('judul')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="keterangan" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Keterangan <span class="font-normal text-slate-400">(opsional)</span>
                        </label>
                        <textarea id="keterangan" name="keterangan" rows="4"
                                  placeholder="Tambahkan keterangan atau instruksi untuk penerima"
                                  class="w-full rounded-lg border bg-white px-3 py-2.5 text-base text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 sm:text-sm {{ $errors->has('keterangan') ? $bad : $ok }}">{{ old('keterangan') }}</textarea>
                        @error('keterangan')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                {{-- PENERIMA --}}
                <section class="p-4 sm:p-6" aria-labelledby="sec-penerima">
                    <h2 id="sec-penerima" class="text-base font-semibold text-slate-900">Penerima</h2>

                    <label for="penerima_search" class="mb-1.5 mt-4 block text-sm font-medium text-slate-700">
                        Kirim ke <span class="text-red-600" aria-hidden="true">*</span>
                    </label>

                    <input type="hidden" name="penerima_id" id="penerima_id" value="{{ old('penerima_id') }}">

                    <div id="recipientSearchWrap" class="relative">
                        <i class="fas fa-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
                        <input type="text" id="penerima_search" autocomplete="off"
                               role="combobox" aria-expanded="false" aria-controls="searchResults" aria-autocomplete="list"
                               placeholder="Ketik nama atau email penerima"
                               class="{{ $base }} pl-10 {{ $errors->has('penerima_id') ? $bad : $ok }}">
                        <ul id="searchResults" role="listbox" aria-label="Hasil pencarian penerima"
                            class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg"></ul>
                    </div>

                    <div id="selectedUserDisplay" class="hidden">
                        <div class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 p-3">
                            <div id="selectedUserAvatar" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white" aria-hidden="true"></div>
                            <div class="min-w-0 flex-1">
                                <p id="selectedUserName" class="tr-wrap text-sm font-semibold text-slate-900"></p>
                                <p id="selectedUserEmail" class="tr-wrap text-xs text-slate-500"></p>
                            </div>
                            <button type="button" id="clearRecipient"
                                    class="h-9 shrink-0 rounded-lg px-3 text-sm font-medium text-blue-700 transition hover:bg-blue-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                Ganti
                            </button>
                        </div>
                    </div>

                    <p id="recipientError" class="mt-1.5 hidden text-xs text-red-600" role="alert">Pilih penerima dari hasil pencarian.</p>
                    @error('penerima_id')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-slate-500">Ketik minimal 3 huruf, lalu pilih dari daftar yang muncul. Dokumen akan masuk ke Dokumen Masuk milik penerima.</p>
                </section>

                {{-- LAMPIRAN --}}
                <section class="p-4 sm:p-6" aria-labelledby="sec-lampiran">
                    <h2 id="sec-lampiran" class="text-base font-semibold text-slate-900">
                        Lampiran <span class="text-sm font-normal text-slate-400">(opsional)</span>
                    </h2>

                    <label id="dropzone" for="foto_dokumen_input"
                           class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:border-slate-400 hover:bg-slate-100 focus-within:ring-2 focus-within:ring-blue-500/40">
                        <i class="fas fa-cloud-upload-alt text-2xl text-slate-400" aria-hidden="true"></i>
                        <span class="mt-2 text-sm text-slate-600">
                            <span class="font-semibold text-blue-700">Pilih file</span><span class="hidden sm:inline"> atau tarik ke sini</span>
                        </span>
                        <span class="mt-1 text-xs text-slate-500">JPG, PNG, PDF, DOC, DOCX. Maks. 5 MB per file.</span>
                        <input id="foto_dokumen_input" type="file" name="foto_dokumen[]" multiple
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="sr-only">
                    </label>
                    <p class="mt-1.5 text-xs text-slate-500">Lampiran tidak dapat dihapus setelah dokumen dikirim.</p>

                    <div id="fileSummaryWrap" class="mt-3 hidden">
                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                            <p id="fileSummary" class="font-medium text-slate-700"></p>
                            <button type="button" id="clearFiles"
                                    class="h-9 rounded-lg px-3 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                                Hapus semua
                            </button>
                        </div>
                        <ul id="fileList" class="divide-y divide-slate-100 rounded-lg border border-slate-200"></ul>
                    </div>

                    <p id="fileError" class="mt-2 hidden text-xs text-red-600" role="alert"></p>
                    @foreach($fileErrors as $message)
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @endforeach
                </section>
            </div>

            {{-- AKSI: menempel di bawah layar pada HP agar tombol Kirim selalu terjangkau --}}
            <div class="sticky bottom-3 z-20 flex items-center gap-3 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur sm:static sm:justify-end sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none">
                <a href="{{ route('track-r.index') }}"
                   class="inline-flex h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:h-10 sm:flex-none">
                    Batal
                </a>
                <button type="submit" id="submitBtn"
                        class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:h-10 sm:flex-none">
                    <i class="fas fa-paper-plane text-xs" aria-hidden="true"></i>
                    <span>Kirim dokumen</span>
                </button>
            </div>
        </form>

        {{-- INFO --}}
        <aside class="rounded-xl border border-blue-100 bg-blue-50/60 p-4 sm:p-5 lg:sticky lg:top-6">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-blue-900">
                <i class="fas fa-route text-blue-500" aria-hidden="true"></i> Alur setelah dikirim
            </h2>
            <ol class="mt-3 space-y-3 text-sm text-blue-900/80">
                <li class="flex gap-2.5"><span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white" aria-hidden="true">1</span><span><b>Anda</b>: dokumen tercatat di <b>Dokumen Keluar &rsaquo; Menunggu</b>.</span></li>
                <li class="flex gap-2.5"><span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white" aria-hidden="true">2</span><span><b>Penerima</b>: dokumen muncul di <b>Dokumen Masuk &rsaquo; Perlu Tindakan</b>, lalu ia memilih Terima, Tolak, atau Teruskan.</span></li>
                <li class="flex gap-2.5"><span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white" aria-hidden="true">3</span><span><b>Hasil</b>: pindah ke tab <b>Diterima</b>. Jika <b>Ditolak</b>, buka dokumennya di Dokumen Keluar lalu Kirim Ulang.</span></li>
            </ol>
            <hr class="my-4 border-blue-100">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-blue-900">
                <i class="fas fa-info-circle text-blue-500" aria-hidden="true"></i> Sebelum mengirim
            </h2>
            <ul class="mt-3 space-y-2.5 text-sm text-blue-900/80">
                <li class="flex gap-2.5"><i class="fas fa-check mt-1 text-xs text-blue-500" aria-hidden="true"></i><span>Dokumen bersifat 1 ke 1, hanya antara pengirim dan penerima.</span></li>
                <li class="flex gap-2.5"><i class="fas fa-check mt-1 text-xs text-blue-500" aria-hidden="true"></i><span>Lampiran tidak dapat dihapus setelah dikirim.</span></li>
                <li class="flex gap-2.5"><i class="fas fa-check mt-1 text-xs text-blue-500" aria-hidden="true"></i><span>Penerima dapat menerima, menolak, atau meneruskan dokumen.</span></li>
                <li class="flex gap-2.5"><i class="fas fa-check mt-1 text-xs text-blue-500" aria-hidden="true"></i><span>Semua aktivitas tercatat di riwayat.</span></li>
            </ul>
        </aside>
    </div>
</div>

<script>
(function () {
    const USERS     = @json($userList);
    const MAX_BYTES = 5 * 1024 * 1024;               // sama dengan max:5120 di controller
    const ALLOWED   = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']; // sama dengan mimes di controller
    const $ = (id) => document.getElementById(id);

    const form = $('sendForm');
    const submitBtn = $('submitBtn');

    /* =========================================================
       PENERIMA — combobox dengan navigasi keyboard
    ========================================================= */
    const search = $('penerima_search');
    const results = $('searchResults');
    const hidden = $('penerima_id');
    const selectedBox = $('selectedUserDisplay');
    const searchWrap = $('recipientSearchWrap');
    const recipientError = $('recipientError');
    let matches = [];
    let active = -1;

    const initials = (name) => name.trim().split(/\s+/).slice(0, 2).map((w) => w.charAt(0).toUpperCase()).join('') || '?';

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
        const items = results.querySelectorAll('[role="option"]');
        items.forEach((el, idx) => {
            const on = idx === i;
            el.classList.toggle('bg-blue-50', on);
            el.setAttribute('aria-selected', on ? 'true' : 'false');
            if (on) {
                search.setAttribute('aria-activedescendant', el.id);
                el.scrollIntoView({ block: 'nearest' });
            }
        });
        active = i;
    }

    function renderResults() {
        results.innerHTML = '';
        const shown = matches.slice(0, 8);
        shown.forEach((u, i) => {
            const li = document.createElement('li');
            li.id = 'opt-' + i;
            li.setAttribute('role', 'option');
            li.className = 'cursor-pointer px-3 py-2.5 hover:bg-slate-50';
            const name = document.createElement('div');
            name.className = 'text-sm font-medium text-slate-900';
            name.textContent = u.name;                       // textContent: aman dari XSS
            const mail = document.createElement('div');
            mail.className = 'text-xs text-slate-500';
            mail.textContent = u.email;
            li.append(name, mail);
            li.addEventListener('click', () => choose(u));
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

    function onInput() {
        const term = search.value.trim().toLowerCase();
        if (term.length === 0) { closeResults(); return; }
        if (term.length < 3)   { showMessage('Ketik minimal 3 huruf'); return; }
        matches = USERS.filter((u) => (u.name + ' ' + u.email).toLowerCase().includes(term));
        if (!matches.length)   { showMessage('Tidak ada pengguna yang cocok'); return; }
        renderResults();
    }

    function choose(u) {
        hidden.value = u.id;
        $('selectedUserName').textContent = u.name;
        $('selectedUserEmail').textContent = u.email;
        $('selectedUserAvatar').textContent = initials(u.name);
        selectedBox.classList.remove('hidden');
        searchWrap.classList.add('hidden');
        recipientError.classList.add('hidden');
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

    search.addEventListener('input', onInput);
    search.addEventListener('keydown', (e) => {
        const open = !results.classList.contains('hidden') && matches.length > 0;
        const last = Math.min(matches.length, 8) - 1;
        if (e.key === 'ArrowDown' && open) { e.preventDefault(); setActive(Math.min(active + 1, last)); }
        else if (e.key === 'ArrowUp' && open) { e.preventDefault(); setActive(Math.max(active - 1, 0)); }
        else if (e.key === 'Enter') { e.preventDefault(); if (open && active >= 0) choose(matches[active]); }
        else if (e.key === 'Escape') { closeResults(); }
    });
    $('clearRecipient').addEventListener('click', clearRecipient);
    document.addEventListener('click', (e) => { if (!searchWrap.contains(e.target)) closeResults(); });

    // Isi ulang pilihan penerima setelah gagal validasi di server
    const oldId = @json((string) old('penerima_id'));
    if (oldId) {
        const found = USERS.find((u) => String(u.id) === oldId);
        if (found) choose(found);
    }

    /* =========================================================
       LAMPIRAN — preview, hapus per file, validasi ukuran & format
    ========================================================= */
    const input = $('foto_dokumen_input');
    const dropzone = $('dropzone');
    const list = $('fileList');
    const summaryWrap = $('fileSummaryWrap');
    const summary = $('fileSummary');
    const fileError = $('fileError');
    let files = [];

    const CAN_MERGE = (function () { try { new DataTransfer(); return true; } catch (e) { return false; } })();
    const ext = (n) => (n.split('.').pop() || '').toLowerCase();
    const fmt = (b) => b < 1024 ? b + ' B' : b < 1048576 ? Math.round(b / 1024) + ' KB' : (b / 1048576).toFixed(1) + ' MB';
    const keyOf = (f) => [f.name, f.size, f.lastModified].join('|');

    function problem(f) {
        if (!ALLOWED.includes(ext(f.name))) return 'Format tidak didukung';
        if (f.size > MAX_BYTES) return 'Melebihi 5 MB';
        return null;
    }

    function sync() {
        if (!CAN_MERGE) return;
        const dt = new DataTransfer();
        files.forEach((f) => dt.items.add(f));
        input.files = dt.files;
    }

    function addFiles(incoming) {
        const fresh = Array.from(incoming);
        if (!CAN_MERGE) {
            files = fresh;                                    // browser lama: pilihan baru menggantikan yang lama
        } else {
            const seen = new Set(files.map(keyOf));
            fresh.forEach((f) => { if (!seen.has(keyOf(f))) { files.push(f); seen.add(keyOf(f)); } });
            sync();
        }
        renderFiles();
    }

    function removeFile(i) { files.splice(i, 1); sync(); renderFiles(); }

    function renderFiles() {
        list.innerHTML = '';
        fileError.classList.add('hidden');
        if (!files.length) { summaryWrap.classList.add('hidden'); return; }
        summaryWrap.classList.remove('hidden');

        let total = 0;
        files.forEach((f, i) => {
            total += f.size;
            const bad = problem(f);
            const type = ext(f.name);

            const li = document.createElement('li');
            li.className = 'flex items-center gap-3 p-2.5';

            const thumb = document.createElement('div');
            thumb.className = 'flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-md bg-slate-100 text-lg text-slate-400';
            if (!bad && ['jpg', 'jpeg', 'png'].includes(type)) {
                const img = document.createElement('img');
                img.alt = '';
                img.className = 'h-full w-full object-cover';
                img.src = URL.createObjectURL(f);
                img.onload = () => URL.revokeObjectURL(img.src);
                thumb.appendChild(img);
            } else {
                const icon = document.createElement('i');
                icon.setAttribute('aria-hidden', 'true');
                icon.className = 'fas ' + (type === 'pdf' ? 'fa-file-pdf text-red-500'
                                 : (type === 'doc' || type === 'docx') ? 'fa-file-word text-blue-500' : 'fa-file');
                thumb.appendChild(icon);
            }

            const info = document.createElement('div');
            info.className = 'min-w-0 flex-1';
            const nameEl = document.createElement('p');
            nameEl.className = 'tr-wrap text-sm font-medium text-slate-800';
            nameEl.title = f.name;
            nameEl.textContent = f.name;
            const meta = document.createElement('p');
            meta.className = 'text-xs ' + (bad ? 'font-medium text-red-600' : 'text-slate-500');
            meta.textContent = fmt(f.size) + (bad ? ' – ' + bad : '');
            info.append(nameEl, meta);

            const rm = document.createElement('button');
            rm.type = 'button';
            rm.setAttribute('aria-label', 'Hapus ' + f.name);
            rm.className = 'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500';
            rm.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
            rm.addEventListener('click', () => removeFile(i));

            li.append(thumb, info, rm);
            if (bad) li.classList.add('bg-red-50');
            list.appendChild(li);
        });

        summary.textContent = files.length + ' file, ' + fmt(total);
    }

    input.addEventListener('change', () => addFiles(input.files));
    $('clearFiles').addEventListener('click', () => { files = []; sync(); if (!CAN_MERGE) input.value = ''; renderFiles(); });

    ['dragenter', 'dragover'].forEach((t) => dropzone.addEventListener(t, (e) => {
        e.preventDefault();
        dropzone.classList.add('border-blue-400', 'bg-blue-50');
    }));
    ['dragleave', 'drop'].forEach((t) => dropzone.addEventListener(t, (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-blue-400', 'bg-blue-50');
    }));
    dropzone.addEventListener('drop', (e) => { if (e.dataTransfer && e.dataTransfer.files.length) addFiles(e.dataTransfer.files); });

    /* =========================================================
       SUBMIT — validasi ringan + cegah klik ganda
    ========================================================= */
    form.addEventListener('submit', (e) => {
        let firstProblem = null;

        if (!hidden.value) {
            recipientError.classList.remove('hidden');
            firstProblem = search;
        }
        const badFiles = files.filter((f) => problem(f));
        if (badFiles.length) {
            fileError.textContent = badFiles.length + ' file tidak bisa dikirim. Hapus atau ganti file yang bertanda merah.';
            fileError.classList.remove('hidden');
            firstProblem = firstProblem || dropzone;
        }
        if (firstProblem) {
            e.preventDefault();
            firstProblem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (firstProblem === search) search.focus();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.querySelector('i').className = 'fas fa-spinner fa-spin text-xs';
        submitBtn.querySelector('span').textContent = 'Mengirim…';
    });

    // Tombol Kembali di browser: pulihkan tombol kirim
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) {
            submitBtn.disabled = false;
            submitBtn.querySelector('i').className = 'fas fa-paper-plane text-xs';
            submitBtn.querySelector('span').textContent = 'Kirim dokumen';
        }
    });
})();
</script>
<style>

    /* Pengaman: isi halaman tidak boleh melebarkan layout (layout memakai flex-1 tanpa min-w-0) */
    .tr-page { max-width: 100%; min-width: 0; overflow-x: clip; contain: inline-size; }

    /* Teks panjang turun ke baris berikutnya (tidak melebarkan layar HP) */
    .tr-wrap { overflow-wrap: anywhere; word-break: break-word; min-width: 0; }
</style>
@endsection
