@extends('layouts.app-budget-sidebar')
@section('title', $peran === 'admin' ? 'Hak Akses' : 'PIC Saya')
@php
    $admin = $peran === 'admin';
    $P = \App\Support\budget_s\BudgetPeran::class;
    $headDari = $picHead->groupBy('pic_user_id');
    $namaDept = fn ($id) => $P::namaDept($id);
    $inisial = fn ($n) => mb_strtoupper(mb_substr(trim((string) $n) ?: '?', 0, 1));
    $warna = ['#2563eb', '#7c3aed', '#059669', '#d97706', '#be185d', '#0e7490', '#c2410c', '#4338ca'];
    $ringkasTugas = function ($picId, $headId) use ($tugasRingkas) {
        $r = $tugasRingkas[$picId . ':' . $headId] ?? [];
        return ['kat' => $r['kat'] ?? 0, 'sub' => $r['sub'] ?? 0, 'item' => $r['item'] ?? 0];
    };
    if ($admin) {
        $jmlAdmin = $akses->where('peran', 'admin')->count();
        $jmlDept = $akses->where('peran', 'dept_head')->count();
        $jmlPic = $akses->where('peran', 'pic')->count();
        $jmlBatas = collect($batasiMap)->filter()->count();
    } else {
        $jmlPic = $picHead->count();
        $batasSaya = (bool) ($batasiMap[auth()->id()] ?? false);
        $jmlTugas = $picHead->sum(fn ($p) => array_sum($ringkasTugas($p->pic_user_id, $p->dept_head_user_id)));
    }
@endphp
@section('content')
@push('styles')
    @include('budget_p.partials.gaya-halaman')
@endpush

<div class="pg-head">
    <div>
        <h2>{{ $admin ? 'Hak Akses' : 'PIC Saya' }}</h2>
        <p>
            @if($admin)
                Kelola siapa yang menjadi Admin dan Dept Head di modul budgeting. PIC ditambahkan oleh Dept Head masing-masing, lalu ditugaskan ke kategori, subkategori, atau item lewat menu Tugas PIC.
            @else
                Tambah PIC di bawah Anda, lalu tentukan bagian mana yang mereka proses di menu Tugas PIC. Hanya Anda yang bisa menambah dan melepas PIC. Satu PIC boleh berada di bawah lebih dari satu Dept Head.
            @endif
        </p>
    </div>
    @if($admin)
        <button type="button" class="btn pr" onclick="aksesModal()">+ Tambah akses</button>
    @else
        <a class="btn" href="{{ route('budgeting.tugas') }}">Atur Tugas PIC</a>
    @endif
</div>

<div class="pg-stats">
    @if($admin)
        <div class="pg-stat"><div class="ic" style="background:#fee2e2;color:#b91c1c"><i class="fas fa-user-shield"></i></div><div><b>{{ $jmlAdmin }}</b><span>Admin</span></div></div>
        <div class="pg-stat"><div class="ic"><i class="fas fa-user-tie"></i></div><div><b>{{ $jmlDept }}</b><span>Dept Head</span></div></div>
        <div class="pg-stat"><div class="ic g"><i class="fas fa-user-check"></i></div><div><b>{{ $jmlPic }}</b><span>PIC</span></div></div>
        <div class="pg-stat"><div class="ic v"><i class="fas fa-filter"></i></div><div><b>{{ $jmlBatas }}</b><span>Dept membatasi PIC</span></div></div>
    @else
        <div class="pg-stat"><div class="ic g"><i class="fas fa-user-check"></i></div><div><b>{{ $jmlPic }}</b><span>PIC di tim Anda</span></div></div>
        <div class="pg-stat"><div class="ic v"><i class="fas fa-list-check"></i></div><div><b>{{ $jmlTugas }}</b><span>Penugasan aktif</span></div></div>
        <div class="pg-stat"><div class="ic {{ $batasSaya ? 'g' : 'a' }}"><i class="fas fa-filter"></i></div><div><b>{{ $batasSaya ? 'Aktif' : 'Nonaktif' }}</b><span>Batasi PIC per tugas</span></div></div>
    @endif
</div>

@if($admin)
{{-- ===================== ADMIN ===================== --}}
<div class="pg-tabs" role="tablist">
    <button type="button" class="pg-tab" data-tab="akses" onclick="pgTab('akses')">Daftar Akses<span class="n">{{ $akses->count() }}</span></button>
    <button type="button" class="pg-tab" data-tab="pic" onclick="pgTab('pic')">PIC per Departemen<span class="n">{{ $picHead->count() }}</span></button>
    <button type="button" class="pg-tab" data-tab="pindah" onclick="pgTab('pindah')">Pindah Data Dept Head</button>
</div>

<div class="pg-pane on" id="pane-akses">
    <div class="pg-card">
        <div class="hd">
            <div class="pg-tool"><input type="search" placeholder="Cari nama, email, atau departemen..." oninput="pgCari(this, '#tb-akses tbody tr')" aria-label="Cari akses"></div>
            <span class="lbl">Nama departemen dipakai sebagai label di dashboard, daftar budget, kategori, dan laporan. Kosongkan untuk memakai nama orangnya.</span>
        </div>
        <div class="scroll">
        <table class="pg-t" id="tb-akses">
            <thead><tr><th>Pengguna</th><th>Peran</th><th>Departemen</th><th>Batasi PIC</th><th>Status</th><th class="r"></th></tr></thead>
            <tbody>
            @foreach($akses as $a)
                @php
                    $pic = $a->peran === 'pic';
                    $nm = $a->user->name ?? ('#' . $a->user_id);
                @endphp
                <tr>
                    <td><div class="pg-who"><span class="pg-av" style="background:{{ $warna[$a->user_id % count($warna)] }}">{{ $inisial($nm) }}</span><div><b>{{ $nm }}</b><small>{{ $a->user->email ?? '-' }}</small></div></div></td>
                    <td><span class="pg-pill {{ $a->peran }}">{{ $P::label($a->peran) }}</span></td>
                    <td>
                        @if($pic)
                            {{ ($headDari[$a->user_id] ?? collect())->map(fn ($p) => $namaDept($p->dept_head_user_id))->implode(', ') ?: 'Belum ada' }}
                        @elseif($a->peran === 'dept_head')
                            {{ $a->nama_dept ?: $nm }}
                        @else - @endif
                    </td>
                    <td>
                        @if($a->peran === 'dept_head')
                            <span class="pg-pill {{ !empty($batasiMap[$a->user_id]) ? 'ok' : 'off' }}">{{ !empty($batasiMap[$a->user_id]) ? 'Aktif' : 'Nonaktif' }}</span>
                        @else - @endif
                    </td>
                    <td><span class="pg-pill {{ $a->aktif ? 'ok' : 'off' }}">{{ $a->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="r">
                        @if($pic)
                            <span class="lbl">Dikelola Dept Head</span>
                        @else
                            <button type="button" class="btn sm" data-d="{{ json_encode(['user_id' => $a->user_id, 'nama' => $nm, 'email' => $a->user->email ?? '', 'peran' => $a->peran, 'nama_dept' => $a->nama_dept, 'aktif' => (bool) $a->aktif]) }}" onclick="aksesModal(JSON.parse(this.dataset.d))">Ubah</button>
                            <button type="button" class="btn sm dg" onclick="konfirmasi({{ json_encode(route('budgeting.akses.hapus', $a->id)) }}, {method:'DELETE', bahaya:true, tombol:'Hapus akses', judul:'Hapus hak akses', teks:{{ json_encode('Hapus hak akses ' . $nm . ' dari database? PIC di bawahnya ikut kehilangan hubungan ke Dept Head ini, dan penugasan PIC-nya dihapus.') }}})">Hapus</button>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @if($akses->isEmpty())<div class="pg-empty"><b>Belum ada akses</b>Tambahkan Admin atau Dept Head pertama lewat tombol "Tambah akses".</div>@endif
    </div>
</div>

<div class="pg-pane" id="pane-pic">
    <div class="pg-card">
        <div class="hd"><h3>PIC per departemen</h3><span class="lbl">Dept Head menambah dan melepas PIC; Admin bisa memindahkan PIC ke Dept Head lain.</span></div>
        @if($picHead->isEmpty())
            <div class="pg-empty"><b>Belum ada PIC</b>PIC ditambahkan oleh Dept Head dari menu PIC Saya.</div>
        @else
        <div class="scroll">
        <table class="pg-t">
            <thead><tr><th>Departemen</th><th>Dept Head</th><th>PIC</th><th>Tugas</th><th>Pindah ke Dept Head</th></tr></thead>
            <tbody>
            @foreach($picHead->sortBy(fn ($p) => $namaDept($p->dept_head_user_id)) as $p)
                @php $rt = $ringkasTugas($p->pic_user_id, $p->dept_head_user_id); $nmPic = $p->pic->name ?? ('#' . $p->pic_user_id); @endphp
                <tr>
                    <td><b>{{ $namaDept($p->dept_head_user_id) }}</b></td>
                    <td>{{ $p->deptHead->name ?? ('#' . $p->dept_head_user_id) }}</td>
                    <td><div class="pg-who"><span class="pg-av" style="background:{{ $warna[$p->pic_user_id % count($warna)] }}">{{ $inisial($nmPic) }}</span><div><b>{{ $nmPic }}</b><small>{{ $p->pic->email ?? '-' }}</small></div></div></td>
                    <td>
                        @if(array_sum($rt) > 0)
                            <span class="pg-pill pc">{{ $rt['kat'] }} kategori</span><span class="pg-pill pc">{{ $rt['sub'] }} sub</span><span class="pg-pill pc">{{ $rt['item'] }} item</span>
                        @else
                            <span class="pg-pill wn">Belum ada</span>
                        @endif
                    </td>
                    <td>
                        <form method="post" action="{{ route('budgeting.akses.pic.pindah', $p) }}" class="pg-tool" style="gap:6px"
                              onsubmit="return konfirmasiForm(this, {judul:'Pindahkan PIC', tombol:'Pindahkan', teks:'Pindahkan PIC ini ke Dept Head tujuan? Item di Dept asal yang ditunjuk ke PIC ini akan dikosongkan PIC-nya, dan penugasannya di Dept asal dihapus.'})">
                            @csrf
                            <select name="ke" required aria-label="Dept Head tujuan">
                                <option value="">Pilih Dept Head...</option>
                                @foreach($kepala->where('user_id', '!=', $p->dept_head_user_id) as $k)
                                    <option value="{{ $k->user_id }}">{{ $P::namaDeptLengkap($k->user_id) }}</option>
                                @endforeach
                            </select>
                            <button class="btn sm pr">Pindah</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</div>

<div class="pg-pane" id="pane-pindah">
    <div class="pg-card">
        <div class="hd"><h3>Pindahkan data Dept Head</h3></div>
        <div class="bd2">
            <div class="pg-note wn">Dipakai saat orang yang memegang sebuah departemen diganti. Seluruh item (aktif dan nonaktif), PIC beserta penugasannya, kategori, dan nama departemen pindah dari akun lama ke akun baru. Tindakan ini tidak bisa dibatalkan. Akun baru harus sudah terdaftar sebagai Dept Head aktif; setelah pindah, akun lama bisa diubah atau dihapus.</div>
            <form method="post" action="{{ route('budgeting.akses.pindah') }}" class="pg-tool"
                  onsubmit="return konfirmasiForm(this, {judul:'Pindahkan data Dept Head', bahaya:true, tombol:'Pindahkan', teks:'Pindahkan semua data Dept Head ini ke Dept Head tujuan? Tindakan ini tidak bisa dibatalkan.'})">
                @csrf
                <select name="dari" required aria-label="Dept Head asal" style="min-width:280px">
                    <option value="">Dari Dept Head (akun lama)...</option>
                    @foreach($akses->where('peran', 'dept_head') as $a)
                        <option value="{{ $a->user_id }}">{{ $P::namaDeptLengkap($a->user_id) }} - {{ $jumlahItem[$a->user_id] ?? 0 }} item{{ $a->aktif ? '' : ' (nonaktif)' }}</option>
                    @endforeach
                </select>
                <span class="lbl">&rarr;</span>
                <select name="ke" required aria-label="Dept Head tujuan" style="min-width:280px">
                    <option value="">Ke Dept Head (akun baru)...</option>
                    @foreach($kepala as $a)
                        <option value="{{ $a->user_id }}">{{ $P::namaDeptLengkap($a->user_id) }}</option>
                    @endforeach
                </select>
                <button class="btn pr">Pindahkan</button>
            </form>
        </div>
    </div>
</div>

{{-- Dialog tambah / ubah akses --}}
<dialog id="modal-akses" class="pg-dlg" aria-labelledby="ma-judul">
    <div class="dh"><b id="ma-judul">Tambah akses</b><button type="button" class="btn sm" onclick="tutup('modal-akses')">Tutup</button></div>
    <form method="post" action="{{ route('budgeting.akses.simpan') }}" id="f-akses" class="db pg-f">
        @csrf
        <input type="hidden" name="user_id" id="ak-user">
        <div id="ma-cari">
            <label><span>Pengguna</span><input type="search" id="ak-cari" placeholder="Ketik nama, email, atau nomor karyawan (min. 2 huruf)" autocomplete="off"></label>
            <div id="ak-hasil" class="pg-found" style="max-height:200px;overflow:auto"></div>
        </div>
        <div class="lbl" id="ak-label">Belum ada user dipilih.</div>
        <div class="two" style="grid-template-columns:1fr 1fr">
            <label><span>Peran</span>
                <select name="peran" id="ak-peran" onchange="aksesPeran()">
                    <option value="dept_head">Dept Head</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <label id="ma-dept"><span>Nama departemen / kelompok</span><input type="text" name="nama_dept" id="ak-dept" maxlength="100" placeholder="Kosongkan untuk memakai nama orangnya"></label>
        </div>
        <label style="display:flex;align-items:center;gap:10px"><input type="hidden" name="aktif" value="0"><span class="pg-sw"><input type="checkbox" name="aktif" id="ak-aktif" value="1" checked><i></i></span><b style="font-weight:600">Akses aktif</b></label>
        <div class="pg-acts">
            <button type="button" class="btn" onclick="tutup('modal-akses')">Batal</button>
            <button class="btn pr" onclick="if(!document.getElementById('ak-user').value){alert('Pilih user dari hasil pencarian dulu.');return false}">Simpan akses</button>
        </div>
    </form>
</dialog>

@else
{{-- ===================== DEPT HEAD: PIC SAYA ===================== --}}
<div class="pg-card">
    <div class="hd"><h3>Tambah PIC di bawah Anda</h3></div>
    <div class="bd2">
        <form method="post" action="{{ route('budgeting.akses.pic.tambah') }}" class="pg-tool" style="align-items:flex-start">
            @csrf
            <input type="hidden" name="pic_user_id" id="pic-user">
            <div style="flex:1;min-width:260px">
                <input type="search" id="pic-cari" placeholder="Cari user yang akan menjadi PIC (min. 2 huruf)" style="width:100%" autocomplete="off">
                <div id="pic-hasil" class="pg-found" style="max-height:200px;overflow:auto"></div>
                <div class="lbl" id="pic-label" style="margin-top:4px">Belum ada user dipilih.</div>
            </div>
            <button class="btn pr" onclick="if(!document.getElementById('pic-user').value){alert('Pilih user dari hasil pencarian dulu.');return false}">Tambah PIC</button>
        </form>
        <div class="pg-note" style="margin:12px 0 0">
            @if($batasSaya)
                <b>Batasi PIC aktif.</b> PIC hanya melihat dan mengerjakan bagian yang Anda tugaskan. Anda tetap melihat seluruh data tim.
            @else
                <b>Batasi PIC belum aktif.</b> PIC masih melihat dan mengelola semua data departemen. Hidupkan pembatasan di menu <a href="{{ route('budgeting.tugas') }}" style="color:var(--ac)">Tugas PIC</a> agar PIC hanya mengerjakan bagian yang Anda tugaskan.
            @endif
        </div>
    </div>
</div>

<div class="pg-card">
    <div class="hd"><h3>Daftar PIC</h3></div>
    @if($picHead->isEmpty())
        <div class="pg-empty"><b>Belum ada PIC</b>Tambahkan PIC lewat form di atas.</div>
    @else
    <div class="scroll">
    <table class="pg-t">
        <thead><tr><th>PIC</th><th>Tugas</th><th class="r"></th></tr></thead>
        <tbody>
        @foreach($picHead as $p)
            @php $rt = $ringkasTugas($p->pic_user_id, $p->dept_head_user_id); $nmPic = $p->pic->name ?? ('#' . $p->pic_user_id); @endphp
            <tr>
                <td><div class="pg-who"><span class="pg-av" style="background:{{ $warna[$p->pic_user_id % count($warna)] }}">{{ $inisial($nmPic) }}</span><div><b>{{ $nmPic }}</b><small>{{ $p->pic->email ?? '-' }}</small></div></div></td>
                <td>
                    @if(array_sum($rt) > 0)
                        <span class="pg-pill pc">{{ $rt['kat'] }} kategori</span><span class="pg-pill pc">{{ $rt['sub'] }} sub</span><span class="pg-pill pc">{{ $rt['item'] }} item</span>
                    @else
                        <span class="pg-pill wn">Belum ada tugas</span>
                    @endif
                </td>
                <td class="r">
                    <a class="btn sm" href="{{ route('budgeting.tugas', ['pic' => $p->pic_user_id]) }}">Atur tugas</a>
                    <button type="button" class="btn sm dg" onclick="konfirmasi({{ json_encode(route('budgeting.akses.pic.lepas', $p)) }}, {method:'DELETE', bahaya:true, tombol:'Lepas PIC', judul:'Lepas PIC', teks:{{ json_encode('Lepas ' . $nmPic . '? Item tetap milik departemen Anda dan penugasannya dihapus. Jika PIC tidak punya Dept Head lain, hak aksesnya dihapus.') }}})">Lepas</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>
@endif

@include('budget_p.partials.modal-konfirmasi')
@endsection

@push('scripts')
<script>
@if($admin)
pasangCariUser(document.getElementById('ak-cari'), document.getElementById('ak-hasil'), document.getElementById('ak-user'), document.getElementById('ak-label'));
function aksesPeran(){
    document.getElementById('ma-dept').style.display = document.getElementById('ak-peran').value === 'dept_head' ? '' : 'none';
}
function aksesModal(d){
    var f = document.getElementById('f-akses');
    f.reset();
    document.getElementById('ak-hasil').innerHTML = '';
    var lab = document.getElementById('ak-label');
    if(d){
        document.getElementById('ma-judul').textContent = 'Ubah akses';
        document.getElementById('ma-cari').style.display = 'none';
        document.getElementById('ak-user').value = d.user_id;
        lab.textContent = d.nama + ' (' + d.email + ')'; lab.style.color = 'var(--ok)'; lab.style.fontWeight = '600';
        document.getElementById('ak-peran').value = d.peran;
        document.getElementById('ak-dept').value = d.nama_dept || '';
        document.getElementById('ak-aktif').checked = !!d.aktif;
    } else {
        document.getElementById('ma-judul').textContent = 'Tambah akses';
        document.getElementById('ma-cari').style.display = '';
        document.getElementById('ak-user').value = '';
        lab.textContent = 'Belum ada user dipilih.'; lab.style.color = ''; lab.style.fontWeight = '';
        document.getElementById('ak-aktif').checked = true;
    }
    aksesPeran();
    buka('modal-akses');
}
@else
pasangCariUser(document.getElementById('pic-cari'), document.getElementById('pic-hasil'), document.getElementById('pic-user'), document.getElementById('pic-label'));
@endif
</script>
@endpush
