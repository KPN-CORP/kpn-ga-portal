@extends('layouts.app_apartemen_sidebar')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- HEADER --}}
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">Pengajuan Apartemen</h1>
        <p class="text-sm text-gray-500">Form permintaan hunian karyawan</p>
    </div>

    {{-- PESAN DARI SERVER (sebelumnya tidak ditampilkan di halaman ini) --}}
    @if(session('error'))
        <div class="pj-alert" role="alert">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="pj-alert" role="alert">
            <ul>
                @foreach($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="apartemenForm" method="POST" action="{{ route('apartemen.user.store') }}" class="space-y-6" novalidate>
        @csrf

        {{-- PENGHUNI: kolom "Jumlah Penghuni" dihapus, diganti tombol Tambah penghuni --}}
        <div class="pj-head">
            <h2 class="text-lg font-semibold text-gray-800">Penghuni</h2>
            <span id="hitung" class="text-sm text-gray-500" aria-live="polite">1 dari 15</span>
        </div>

        <div id="penghuni" class="space-y-5"></div>

        <div class="pj-addrow">
            <button type="button" id="btnTambah" class="pj-add">+ Tambah penghuni</button>
            <span id="batas" class="pj-note" hidden>Batas 15 penghuni tercapai.</span>
        </div>

        {{-- ALASAN --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <label for="alasan" class="block text-sm font-medium text-gray-700 mb-2">
                Alasan Pengajuan <span class="text-red-500">*</span>
            </label>
            <textarea id="alasan" name="alasan" rows="3" required minlength="20"
                      placeholder="Contoh: Penempatan kerja proyek jangka menengah"
                      class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500">{{ old('alasan') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">Minimal 20 karakter</p>
            <p class="pj-err" id="err-alasan"></p>
        </div>

        {{-- ACTION --}}
        <div class="pj-actions">
            <div id="ringkas" class="text-sm text-gray-500" aria-live="polite"></div>
            <div class="pj-btns">
                <a href="{{ route('apartemen.user.index') }}"
                   class="px-5 py-2 rounded-xl border text-gray-600 hover:bg-gray-50">
                    Batal
                </a>
                <button id="submitBtn" type="submit"
                        class="px-6 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium shadow">
                    Ajukan Permintaan
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Pesan "Urungkan" setelah menghapus kartu penghuni yang sudah terisi --}}
<div id="undoBar" class="pj-undo" role="status" hidden>
    <span id="undoMsg"></span>
    <button type="button" id="undoBtn">Urungkan</button>
</div>

<style>
/* Semua kelas baru berawalan "pj-" dan ditulis di sini, jadi tidak perlu build ulang Tailwind. */
.pj-alert{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:.75rem;padding:.75rem 1rem;font-size:.875rem;margin-bottom:1.25rem}
.pj-alert ul{margin:0;padding-left:1.1rem;list-style:disc}
.pj-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem}
.pj-label{display:block;font-size:.8125rem;color:#6b7280;margin-bottom:.25rem}
.pj-err{font-size:.8125rem;color:#dc2626;margin-top:.25rem}
.pj-err:empty{display:none}
.pj-invalid{border-color:#f87171 !important}
.pj-card-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:1rem}
.pj-head-actions{display:flex;gap:.5rem;flex:none}
.pj-sum{display:none;flex:1;min-width:0;font-size:.8125rem;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pj-btn-sm{font-size:.8125rem;padding:.3rem .7rem;border-radius:.6rem;border:1px solid #e5e7eb;color:#4b5563;background:#fff;cursor:pointer}
.pj-btn-sm:hover{background:#f9fafb}
.pj-btn-sm[hidden]{display:none !important}
.pj-remove{color:#b91c1c;border-color:#fecaca}
.pj-remove:hover{background:#fef2f2}
.pj-toggle{display:none}
.pj-malamcell{display:flex;align-items:flex-end;padding-bottom:.55rem}
.pj-malam{font-size:.8125rem;color:#6b7280}
.pj-addrow{display:flex;align-items:center;gap:.75rem}
.pj-add{padding:.55rem 1.1rem;border-radius:.75rem;border:1px dashed #93c5fd;color:#1d4ed8;background:#eff6ff;font-weight:500;font-size:.875rem;cursor:pointer}
.pj-add:hover{background:#dbeafe}
.pj-add[hidden]{display:none}
.pj-note{font-size:.875rem;color:#6b7280}
.pj-actions{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap}
.pj-btns{display:flex;gap:.75rem;margin-left:auto}
.pj-undo{position:fixed;left:50%;bottom:1rem;transform:translateX(-50%);display:flex;align-items:center;gap:.9rem;background:#111827;color:#fff;padding:.6rem 1rem;border-radius:.75rem;font-size:.875rem;z-index:200}
.pj-undo[hidden]{display:none}
.pj-undo button{background:none;border:0;color:#93c5fd;font-weight:500;cursor:pointer}
.pj-sug{position:absolute;left:2.5rem;width:calc(100% - 2.5rem);max-height:12rem;overflow-y:auto;background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;box-shadow:0 10px 15px -3px rgba(0,0,0,.1);margin-top:.25rem;z-index:100}
.pj-sug div{padding:.5rem 1rem;cursor:pointer;font-size:.875rem}
.pj-sug div:hover,.pj-sug div.pj-active{background:#eff6ff}
@media (max-width:767px){
    .pj-toggle{display:inline-block}
    .pj-collapsed .pj-sum{display:block}
    .pj-collapsed .pj-body{display:none}
    .pj-collapsed .pj-card-head{margin-bottom:0}
    .pj-collapsed .pj-title{display:none}
    .pj-malamcell{padding-bottom:0}
}
</style>

<script>
const MAX = 15;
const OLD = @json(array_values((array) old('penghuni', [])));

const form       = document.getElementById('apartemenForm');
const wrap       = document.getElementById('penghuni');
const btnTambah  = document.getElementById('btnTambah');
const batas      = document.getElementById('batas');
const hitung     = document.getElementById('hitung');
const ringkas    = document.getElementById('ringkas');
const submitBtn  = document.getElementById('submitBtn');
const alasan     = document.getElementById('alasan');
const errAlasan  = document.getElementById('err-alasan');
const undoBar    = document.getElementById('undoBar');
const undoMsg    = document.getElementById('undoMsg');
const undoBtn    = document.getElementById('undoBtn');

let seq = 0;               // penomor unik untuk nama field (penghuni[i][...]); tidak dipakai ulang
let submitting = false;
let undoState = null;
let undoTimer = null;

const FIELDS = ['nama', 'id_karyawan', 'no_hp', 'tanggal_mulai', 'tanggal_selesai'];

const isMobile = () => window.matchMedia('(max-width: 767px)').matches;
const scrollBehavior = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';

/* ========= UTIL ========= */
const cards = () => Array.from(wrap.querySelectorAll('.pj-card'));
const q = (card, f) => card.querySelector('[data-f="' + f + '"]');
const numberOf = card => cards().indexOf(card) + 1;

function setErr(card, key, msg) {
    const p = card.querySelector('[data-err="' + key + '"]');
    if (p) p.textContent = msg || '';
    const el = q(card, key);
    if (el) el.classList.toggle('pj-invalid', !!msg);
}

function isEmptyCard(card) {
    return FIELDS.every(f => !q(card, f).value.trim());
}

// Aturan nomor HP sama dengan server (UserController::validatePhoneNumber):
// buang 0 / 62 di depan, sisa 9-13 digit, diawali 81-89.
function hpOk(raw) {
    let d = String(raw || '').replace(/\D/g, '');
    if (d.startsWith('0')) d = d.slice(1);
    else if (d.startsWith('62')) d = d.slice(2);
    return /^[0-9]{9,13}$/.test(d) && /^8[1-9]/.test(d);
}

function nights(m, s) {
    if (!m || !s) return 0;
    return Math.round((new Date(s) - new Date(m)) / 86400000);
}

function fmtDate(str) {
    return new Date(str + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

/* ========= KARTU PENGHUNI ========= */
function field(i, key, label, icon, attrs, ph) {
    const id = 'p' + i + '-' + key;
    return `
    <div>
        <label class="pj-label" for="${id}">${label}</label>
        <div class="relative">
            <input id="${id}" name="penghuni[${i}][${key}]" data-f="${key}" ${attrs || ''}
                   placeholder="${ph || label}"
                   class="w-full pl-10 pr-3 py-2 rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500">
            ${iconSvg(icon)}
        </div>
        <p class="pj-err" data-err="${key}"></p>
    </div>`;
}

function dateField(i, key, label) {
    const id = 'p' + i + '-' + key;
    return `
    <div>
        <label class="pj-label" for="${id}">${label}</label>
        <input type="date" id="${id}" name="penghuni[${i}][${key}]" data-f="${key}"
               class="w-full px-3 py-2 rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500">
        <p class="pj-err" data-err="${key}"></p>
    </div>`;
}

function golField(i) {
    const id = 'p' + i + '-gol';
    return `
    <div>
        <label class="pj-label" for="${id}">Golongan</label>
        <div class="relative">
            <select id="${id}" name="penghuni[${i}][gol]" data-f="gol"
                class="w-full pl-10 pr-3 py-2 rounded-xl border-gray-200 bg-white focus:ring-blue-500 focus:border-blue-500">
                <option value="">Pilih Golongan</option>
                <option>4</option>
                <option>5</option>
                <option>6</option>
                <option>7</option>
                <option>8</option>
                <option>9</option>
                <option>10</option>
            </select>
            ${iconSvg('layers')}
        </div>
    </div>`;
}

function cardHtml(i) {
    return `
    <div class="pj-card bg-white rounded-2xl shadow-sm p-6" data-idx="${i}">
        <div class="pj-card-head">
            <h3 class="font-semibold text-gray-800 pj-title">Penghuni <span class="pj-no"></span></h3>
            <span class="pj-sum" data-sum></span>
            <div class="pj-head-actions">
                <button type="button" class="pj-btn-sm pj-toggle" data-act="toggle" hidden>Ubah</button>
                <button type="button" class="pj-btn-sm pj-remove" data-act="remove" hidden>Hapus</button>
            </div>
        </div>
        <div class="pj-body">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                ${field(i, 'nama', 'Nama Lengkap', 'user', 'autocomplete="off" aria-required="true"', 'Ketik minimal 3 huruf')}
                ${field(i, 'id_karyawan', 'ID Karyawan', 'badge', 'aria-required="true"')}
                ${field(i, 'no_hp', 'No HP', 'phone', 'inputmode="numeric" maxlength="15" aria-required="true"', '081234567890')}
                ${golField(i)}
                ${field(i, 'unit_kerja', 'Unit Kerja', 'office')}
                <div class="hidden md:block"></div>
                ${dateField(i, 'tanggal_mulai', 'Tanggal Mulai')}
                ${dateField(i, 'tanggal_selesai', 'Tanggal Selesai')}
                <div class="pj-malamcell"><span class="pj-malam" data-malam></span></div>
            </div>
        </div>
    </div>`;
}

function addCard(vals, before) {
    if (cards().length >= MAX) { refresh(); return null; }
    const i = seq++;
    const box = document.createElement('div');
    box.innerHTML = cardHtml(i).trim();
    const card = box.firstElementChild;
    wrap.insertBefore(card, before || null);
    if (vals) {
        Object.keys(vals).forEach(k => {
            const el = q(card, k);
            if (el && vals[k] !== null && vals[k] !== undefined) el.value = vals[k];
        });
    }
    syncDateMin(card);
    refresh();
    return card;
}

function focusNama(card) {
    card.scrollIntoView({ behavior: scrollBehavior(), block: 'center' });
    q(card, 'nama').focus({ preventScroll: true });
}

function syncDateMin(card) {
    const m = q(card, 'tanggal_mulai').value;
    q(card, 'tanggal_selesai').min = m || '';
}

function expand(card) { card.classList.remove('pj-collapsed'); refresh(); }

/* ========= VALIDASI ========= */
function cardProblems(card) {
    const p = {};
    const v = f => q(card, f).value.trim();
    if (!v('nama')) p.nama = 'Isi nama penghuni.';
    if (!v('id_karyawan')) p.id_karyawan = 'Isi ID karyawan.';
    if (!v('no_hp')) p.no_hp = 'Isi nomor HP.';
    else if (!hpOk(v('no_hp'))) p.no_hp = 'Nomor HP tidak valid. Contoh: 081234567890.';
    if (!v('tanggal_mulai')) p.tanggal_mulai = 'Pilih tanggal mulai.';
    if (!v('tanggal_selesai')) p.tanggal_selesai = 'Pilih tanggal selesai.';
    else if (v('tanggal_mulai') && v('tanggal_selesai') <= v('tanggal_mulai')) p.tanggal_selesai = 'Tanggal selesai harus setelah tanggal mulai.';
    return p;
}

// Orang yang sama hanya dilarang bila tanggalnya bertumpuk (sama dengan aturan server).
function dupProblems() {
    const res = new Map();
    const list = cards();
    list.forEach((c, ci) => {
        const id = q(c, 'id_karyawan').value.trim().toLowerCase();
        if (!id || id === '-') return;
        const m = q(c, 'tanggal_mulai').value, s = q(c, 'tanggal_selesai').value;
        if (!m || !s) return;
        for (let k = 0; k < ci; k++) {
            const o = list[k];
            if (q(o, 'id_karyawan').value.trim().toLowerCase() !== id) continue;
            const om = q(o, 'tanggal_mulai').value, os = q(o, 'tanggal_selesai').value;
            if (om && os && m < os && s > om) {
                const nama = q(c, 'nama').value.trim() || 'Karyawan ini';
                res.set(c, nama + ' sudah ada di Penghuni ' + numberOf(o) + ' dengan tanggal yang bertumpuk.');
                break;
            }
        }
    });
    return res;
}

// Pemeriksaan langsung saat mengetik: hanya urutan tanggal dan duplikat (bukan "wajib diisi").
function applyLive() {
    const dups = dupProblems();
    cards().forEach(c => {
        const m = q(c, 'tanggal_mulai').value, s = q(c, 'tanggal_selesai').value;
        if (m && s) setErr(c, 'tanggal_selesai', s <= m ? 'Tanggal selesai harus setelah tanggal mulai.' : '');
        if (q(c, 'id_karyawan').value.trim()) setErr(c, 'id_karyawan', dups.get(c) || '');
    });
}

function validateAll() {
    const invalid = [];
    cards().forEach(c => {
        const p = cardProblems(c);
        FIELDS.forEach(k => setErr(c, k, p[k] || ''));
        Object.keys(p).forEach(k => invalid.push(q(c, k)));
        if (Object.keys(p).length) expand(c);
    });
    dupProblems().forEach((msg, c) => {
        setErr(c, 'id_karyawan', msg);
        invalid.push(q(c, 'id_karyawan'));
        expand(c);
    });
    if (alasan.value.trim().length < 20) {
        errAlasan.textContent = 'Tulis alasan minimal 20 karakter.';
        alasan.classList.add('pj-invalid');
        invalid.push(alasan);
    }
    if (!invalid.length) return true;
    invalid.sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING) ? -1 : 1);
    invalid[0].scrollIntoView({ behavior: scrollBehavior(), block: 'center' });
    invalid[0].focus({ preventScroll: true });
    return false;
}

/* ========= TAMPILAN: nomor, penghitung, ringkasan ========= */
function refresh() {
    const list = cards();
    list.forEach((c, n) => {
        c.querySelector('.pj-no').textContent = n + 1;
        c.querySelector('[data-act="remove"]').hidden = list.length <= 1;

        const nama = q(c, 'nama').value.trim(), id = q(c, 'id_karyawan').value.trim();
        const m = q(c, 'tanggal_mulai').value, s = q(c, 'tanggal_selesai').value;
        const bits = ['Penghuni ' + (n + 1) + (nama ? ': ' + nama : '')];
        if (id) bits.push(id);
        if (m && s) bits.push(fmtDate(m) + ' - ' + fmtDate(s));
        c.querySelector('[data-sum]').textContent = bits.join(' | ');

        const malam = nights(m, s);
        c.querySelector('[data-malam]').textContent = malam > 0 ? malam + ' malam' : '';

        const collapsed = c.classList.contains('pj-collapsed');
        const tg = c.querySelector('[data-act="toggle"]');
        tg.hidden = !(collapsed || Object.keys(cardProblems(c)).length === 0);
        tg.textContent = collapsed ? 'Ubah' : 'Ringkas';
    });

    hitung.textContent = list.length + ' dari ' + MAX;
    const penuh = list.length >= MAX;
    btnTambah.hidden = penuh;
    batas.hidden = !penuh;

    const mulai = list.map(c => q(c, 'tanggal_mulai').value).filter(Boolean).sort();
    const selesai = list.map(c => q(c, 'tanggal_selesai').value).filter(Boolean).sort();
    let teks = list.length + ' penghuni';
    if (mulai.length && selesai.length) teks += ' | ' + fmtDate(mulai[0]) + ' - ' + fmtDate(selesai[selesai.length - 1]);
    ringkas.textContent = teks;
}

/* ========= TAMBAH / HAPUS / URUNGKAN ========= */
function onTambah() {
    const list = cards();
    const last = list[list.length - 1];
    // Jangan menumpuk kartu kosong: arahkan ke kartu kosong yang sudah ada.
    if (last && isEmptyCard(last)) { expand(last); focusNama(last); return; }
    if (list.length >= MAX) return;

    // Rombongan biasanya menginap di tanggal yang sama: salin dari penghuni terakhir yang tanggalnya lengkap.
    const src = list.slice().reverse().find(c => q(c, 'tanggal_mulai').value && q(c, 'tanggal_selesai').value);
    const vals = src ? { tanggal_mulai: q(src, 'tanggal_mulai').value, tanggal_selesai: q(src, 'tanggal_selesai').value } : null;

    // Di HP, ringkas kartu yang sudah lengkap supaya halaman tidak memanjang.
    if (isMobile()) {
        list.forEach(c => { if (Object.keys(cardProblems(c)).length === 0 && !dupProblems().has(c)) c.classList.add('pj-collapsed'); });
    }

    const card = addCard(vals);
    if (card) { applyLive(); focusNama(card); }
}

function removeCard(card) {
    if (cards().length <= 1) return;
    const kosong = isEmptyCard(card);
    const next = card.nextElementSibling;
    const label = 'Penghuni ' + numberOf(card);
    card.remove();
    refresh();
    applyLive();
    if (kosong) return;
    undoState = { card: card, next: next, label: label };
    undoMsg.textContent = label + ' dihapus.';
    undoBar.hidden = false;
    clearTimeout(undoTimer);
    undoTimer = setTimeout(hideUndo, 7000);
}

function hideUndo() {
    undoBar.hidden = true;
    undoState = null;
    clearTimeout(undoTimer);
}

function undo() {
    if (!undoState || cards().length >= MAX) { hideUndo(); return; }
    const ref = undoState.next && undoState.next.parentNode === wrap ? undoState.next : null;
    wrap.insertBefore(undoState.card, ref);
    hideUndo();
    refresh();
    applyLive();
}

/* ========= EVENT KARTU (delegasi) ========= */
wrap.addEventListener('input', e => {
    const el = e.target;
    const card = el.closest('.pj-card');
    const f = el.dataset.f;
    if (!card || !f) return;

    if (f === 'no_hp') el.value = el.value.replace(/[^0-9]/g, '');
    setErr(card, f, '');
    if (f === 'tanggal_mulai') syncDateMin(card);
    if (f === 'nama') searchEmployee(el);

    applyLive();
    refresh();
});

wrap.addEventListener('change', e => {
    const el = e.target;
    const card = el.closest('.pj-card');
    if (!card || !el.dataset.f) return;
    if (el.dataset.f === 'tanggal_mulai') syncDateMin(card);
    applyLive();
    refresh();
});

wrap.addEventListener('click', e => {
    const btn = e.target.closest('[data-act]');
    if (!btn) return;
    const card = btn.closest('.pj-card');
    if (btn.dataset.act === 'remove') removeCard(card);
    if (btn.dataset.act === 'toggle') { card.classList.toggle('pj-collapsed'); refresh(); }
});

wrap.addEventListener('keydown', e => {
    if (!e.target.matches || !e.target.matches('[data-f="nama"]') || !sugBox) return;
    const items = Array.from(sugBox.children);
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        sugActive = (sugActive + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
        items.forEach((it, n) => it.classList.toggle('pj-active', n === sugActive));
    } else if (e.key === 'Enter' && sugActive >= 0) {
        e.preventDefault();
        pickSuggestion(sugActive);
    } else if (e.key === 'Escape') {
        closeSuggestions();
    }
});

alasan.addEventListener('input', () => { errAlasan.textContent = ''; alasan.classList.remove('pj-invalid'); });
btnTambah.addEventListener('click', onTambah);
undoBtn.addEventListener('click', undo);

/* ========= AUTOCOMPLETE KARYAWAN =========
   Dikaitkan ke elemen input yang sedang diketik (bukan indeks global), jadi aman
   walau kartu ditambah atau dihapus. Permintaan lama dibatalkan. */
let sugBox = null, sugInput = null, sugItems = [], sugActive = -1;
let sugTimer = null, sugAbort = null;

function closeSuggestions() {
    if (sugBox) sugBox.remove();
    sugBox = null; sugInput = null; sugItems = []; sugActive = -1;
}

function openSuggestions(input, list) {
    closeSuggestions();
    if (!list.length) return;
    sugInput = input; sugItems = list;
    sugBox = document.createElement('div');
    sugBox.className = 'pj-sug';
    sugBox.setAttribute('role', 'listbox');
    list.forEach((emp, n) => {
        const it = document.createElement('div');
        it.setAttribute('role', 'option');
        it.textContent = emp.fullname + ' - ' + emp.employee_id;
        it.addEventListener('mousedown', ev => { ev.preventDefault(); pickSuggestion(n); });
        sugBox.appendChild(it);
    });
    input.parentNode.appendChild(sugBox);
}

function pickSuggestion(n) {
    const emp = sugItems[n], input = sugInput;
    if (!emp || !input) return;
    const card = input.closest('.pj-card');
    q(card, 'nama').value = emp.fullname;
    q(card, 'id_karyawan').value = emp.employee_id;
    q(card, 'unit_kerja').value = emp.group_company || '';
    setErr(card, 'nama', '');
    setErr(card, 'id_karyawan', '');
    closeSuggestions();
    applyLive();
    refresh();
    q(card, 'no_hp').focus();
}

function searchEmployee(input) {
    const v = input.value.trim();
    clearTimeout(sugTimer);
    if (v.length < 3) { closeSuggestions(); return; }
    sugTimer = setTimeout(() => {
        if (sugAbort) sugAbort.abort();
        sugAbort = new AbortController();
        fetch('/employee/search?q=' + encodeURIComponent(v), { signal: sugAbort.signal, headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (document.activeElement === input && input.value.trim() === v) {
                    openSuggestions(input, Array.isArray(data) ? data : []);
                }
            })
            .catch(err => { if (err.name !== 'AbortError') console.error('Autocomplete error:', err); });
    }, 250);
}

document.addEventListener('click', e => {
    if (!(e.target.matches && e.target.matches('[data-f="nama"]'))) closeSuggestions();
});

/* ========= ICONS ========= */
function iconSvg(type) {
    const icons = {
        user:`<svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9 9 0 1119.9 7.1"/></svg>`,
        phone:`<svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2l2 5-2 1a11 11 0 005 5l1-2 5 2v2a2 2 0 01-2 2A16 16 0 013 5z"/></svg>`,
        badge:`<svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>`,
        office:`<svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 21h18V7H3v14z"/></svg>`,
        layers:`<svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 2l9 5-9 5-9-5 9-5zm0 10l9 5-9 5-9-5 9-5z"/></svg>`
    };
    return icons[type] ?? '';
}

/* ========= KIRIM ========= */
form.addEventListener('submit', e => {
    e.preventDefault();
    if (submitting) return;
    if (!validateAll()) return;

    submitting = true;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Mengirim...';
    form.submit();
});

// Tombol kembali di browser: aktifkan lagi tombol kirim.
window.addEventListener('pageshow', e => {
    if (e.persisted) {
        submitting = false;
        submitBtn.disabled = false;
        submitBtn.textContent = 'Ajukan Permintaan';
    }
});

/* ========= AWAL ========= */
document.addEventListener('DOMContentLoaded', () => {
    // Setelah gagal di server, isian sebelumnya dikembalikan (sebelumnya semua kosong lagi).
    if (OLD.length) OLD.slice(0, MAX).forEach(v => addCard(v));
    else addCard();
    applyLive();
    refresh();
});
</script>
@endsection