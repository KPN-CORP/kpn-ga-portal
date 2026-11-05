@extends('layouts.app-budget-sidebar')
@section('title', $peran === 'admin' ? 'Akses' : 'PIC saya')
@section('content')
@php
    $admin = $peran === 'admin';
    $headDari = $picHead->groupBy('pic_user_id');
    $namaDept = fn ($id) => \App\Support\budget_s\BudgetPeran::namaDept($id);
@endphp

<div class="top"><h2>{{ $admin ? 'Akses budget' : 'PIC saya' }}</h2></div>

@if($admin)
<div class="card">
    <h3>Tambah atau ubah akses user</h3>
    <form method="post" action="{{ route('budgeting.akses.simpan') }}" id="f-akses">
        @csrf
        <input type="hidden" name="user_id" id="ak-user" required>
        <div class="row">
            <div style="flex:1;min-width:260px">
                <input type="search" id="ak-cari" placeholder="Ketik nama, email, atau nomor karyawan (min. 2 huruf)" style="width:100%" autocomplete="off">
                <div id="ak-hasil" style="max-height:200px;overflow:auto"></div>
                <div class="lbl" id="ak-label">Belum ada user dipilih.</div>
            </div>
            <select name="peran" aria-label="Peran">
                <option value="dept_head">Dept</option>
                <option value="admin">Admin</option>
            </select>
            <input name="nama_dept" maxlength="100" placeholder="Nama departemen / kelompok (untuk Dept)" style="min-width:260px" aria-label="Nama departemen">
            <input type="hidden" name="aktif" value="1">
            <button class="btn pr" onclick="if(!document.getElementById('ak-user').value){alert('Pilih user dari hasil pencarian dulu.');return false}">Simpan akses</button>
        </div>
    </form>
    <p class="lbl">Nama departemen adalah nama yang Anda tentukan sendiri, dan dipakai sebagai label di dashboard, daftar budget, kategori, dan laporan. Kosongkan untuk memakai nama orangnya. PIC ditambahkan oleh Dept masing-masing.</p>
</div>

<div class="card scroll">
    <h3>Daftar akses</h3>
    <table>
        <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Nama departemen / Dept</th><th>Aktif</th><th></th></tr>
        @foreach($akses as $a)
            @php $pic = $a->peran === 'pic'; @endphp
            <tr>
                <td><b>{{ $a->user->name ?? ('#' . $a->user_id) }}</b></td>
                <td>{{ $a->user->email ?? '-' }}</td>
                <td>
                    @if($pic)
                        PIC
                    @else
                        <select name="peran" form="fa{{ $a->id }}" aria-label="Peran {{ $a->user->name ?? '' }}">
                            @foreach(['admin' => 'Admin', 'dept_head' => 'Dept'] as $v => $l)<option value="{{ $v }}" @selected($a->peran === $v)>{{ $l }}</option>@endforeach
                        </select>
                    @endif
                </td>
                <td>
                    @if($pic)
                        {{ ($headDari[$a->user_id] ?? collect())->map(fn ($p) => $namaDept($p->dept_head_user_id))->implode(', ') ?: 'Belum ada' }}
                    @elseif($a->peran === 'dept_head')
                        <input name="nama_dept" form="fa{{ $a->id }}" value="{{ $a->nama_dept }}" maxlength="100" placeholder="{{ $a->user->name ?? 'Nama departemen' }}" aria-label="Nama departemen {{ $a->user->name ?? '' }}">
                    @else - @endif
                </td>
                <td>
                    @if($pic)
                        {{ $a->aktif ? 'Ya' : 'Tidak' }}
                    @else
                        <input type="hidden" name="aktif" value="0" form="fa{{ $a->id }}"><input type="checkbox" name="aktif" value="1" form="fa{{ $a->id }}" @checked($a->aktif) aria-label="Aktif">
                    @endif
                </td>
                <td class="n">
                    @unless($pic)
                        <button class="btn sm" form="fa{{ $a->id }}">Simpan</button> <button class="btn sm dg" form="fh{{ $a->id }}">Hapus</button>
                    @endunless
                </td>
            </tr>
        @endforeach
    </table>
    @foreach($akses->where('peran', '!=', 'pic') as $a)
        <form id="fa{{ $a->id }}" method="post" action="{{ route('budgeting.akses.simpan') }}">@csrf<input type="hidden" name="user_id" value="{{ $a->user_id }}"></form>
        <form id="fh{{ $a->id }}" method="post" action="{{ route('budgeting.akses.hapus', $a->id) }}" onsubmit="return confirm('Hapus hak akses user ini dari database? PIC di bawahnya ikut kehilangan hubungan ke Dept ini.')">@csrf @method('DELETE')</form>
    @endforeach
</div>

<div class="card scroll">
    <h3>PIC per departemen</h3>
    @if($picHead->isEmpty())
        <p class="lbl">Belum ada PIC. PIC ditambahkan oleh Dept dari menu Pengaturan, PIC Saya.</p>
    @else
        <table>
            <tr><th>Departemen</th><th>Dept</th><th>PIC</th><th>Email</th></tr>
            @foreach($picHead->sortBy(fn ($p) => $namaDept($p->dept_head_user_id)) as $p)
                <tr>
                    <td><b>{{ $namaDept($p->dept_head_user_id) }}</b></td>
                    <td>{{ $p->deptHead->name ?? ('#' . $p->dept_head_user_id) }}</td>
                    <td>{{ $p->pic->name ?? ('#' . $p->pic_user_id) }}</td>
                    <td>{{ $p->pic->email ?? '-' }}</td>
                </tr>
            @endforeach
        </table>
        <p class="lbl">Hanya Dept yang bisa menambah atau melepas PIC.</p>
    @endif
</div>

<div class="card">
    <h3>Pindahkan data Dept</h3>
    <p class="lbl">Dipakai saat orang di sebuah Dept diganti. Seluruh item (aktif dan nonaktif), PIC, kategori, dan nama departemen pindah dari akun lama ke akun baru. Setelah itu akun lama bisa diubah atau dihapus.</p>
    <form method="post" action="{{ route('budgeting.akses.pindah') }}" onsubmit="return confirm('Pindahkan semua data Dept ini ke Dept tujuan? Tindakan ini tidak bisa dibatalkan.')">
        @csrf
        <div class="row">
            <select name="dari" required aria-label="Dept asal">
                <option value="">Dari Dept (akun lama)...</option>
                @foreach($akses->where('peran', 'dept_head') as $a)
                    <option value="{{ $a->user_id }}">{{ \App\Support\budget_s\BudgetPeran::namaDeptLengkap($a->user_id) }} - {{ $jumlahItem[$a->user_id] ?? 0 }} item{{ $a->aktif ? '' : ' (nonaktif)' }}</option>
                @endforeach
            </select>
            <select name="ke" required aria-label="Dept tujuan">
                <option value="">Ke Dept (akun baru)...</option>
                @foreach($kepala as $a)
                    <option value="{{ $a->user_id }}">{{ \App\Support\budget_s\BudgetPeran::namaDeptLengkap($a->user_id) }}</option>
                @endforeach
            </select>
            <button class="btn pr">Pindahkan</button>
        </div>
    </form>
    <p class="lbl">Akun baru harus sudah terdaftar sebagai Dept aktif di form "Tambah atau ubah akses user" di atas.</p>
</div>
@else
<div class="card">
    <h3>Tambah PIC di bawah Anda</h3>
    <form method="post" action="{{ route('budgeting.akses.pic.tambah') }}">
        @csrf
        <input type="hidden" name="pic_user_id" id="pic-user">
        <div class="row">
            <div style="flex:1;min-width:260px">
                <input type="search" id="pic-cari" placeholder="Cari user yang akan menjadi PIC" style="width:100%" autocomplete="off">
                <div id="pic-hasil" style="max-height:200px;overflow:auto"></div>
                <div class="lbl" id="pic-label">Belum ada user dipilih.</div>
            </div>
            <button class="btn pr" onclick="if(!document.getElementById('pic-user').value){alert('Pilih user dari hasil pencarian dulu.');return false}">Tambah PIC</button>
        </div>
    </form>
    <p class="lbl">PIC memiliki akses yang sama dengan Anda untuk data departemen ini (melihat dan mengubah item, budget, realisasi, dan kategori). Hanya Anda yang bisa menambah dan melepas PIC. Satu PIC boleh berada di bawah lebih dari satu Dept.</p>

    @if($picHead->isEmpty())
        <p class="lbl">Belum ada PIC.</p>
    @else
    <div class="scroll">
    <table>
        <tr><th>PIC</th><th>Email</th><th></th></tr>
        @foreach($picHead as $p)
            <tr>
                <td><b>{{ $p->pic->name ?? ('#' . $p->pic_user_id) }}</b></td>
                <td>{{ $p->pic->email ?? '-' }}</td>
                <td class="n">
                    <form method="post" action="{{ route('budgeting.akses.pic.lepas', $p) }}" style="display:inline"
                          onsubmit="return confirm('Lepas PIC ini? Item tetap milik departemen Anda. Jika PIC tidak punya Dept lain, hak aksesnya dihapus.')">@csrf @method('DELETE')<button class="btn sm dg">Lepas</button></form>
                </td>
            </tr>
        @endforeach
    </table>
    </div>
    @endif
</div>
@endif
@endsection

@push('scripts')
<script>
@if($admin)
pasangCariUser(document.getElementById('ak-cari'), document.getElementById('ak-hasil'), document.getElementById('ak-user'), document.getElementById('ak-label'));
@else
pasangCariUser(document.getElementById('pic-cari'), document.getElementById('pic-hasil'), document.getElementById('pic-user'), document.getElementById('pic-label'));
@endif
</script>
@endpush