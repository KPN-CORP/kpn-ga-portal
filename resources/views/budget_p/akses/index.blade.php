@extends('layouts.app-budget-sidebar')
@section('title', $peran === 'admin' ? 'Akses' : 'PIC saya')
@section('content')
@php
    $admin = $peran === 'admin';
    $headDari = $picHead->groupBy('pic_user_id');
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
                <option value="dept_head">Dept Head</option>
                <option value="admin">Admin</option>
                <option value="pic">PIC (tanpa Dept Head dulu)</option>
            </select>
            <input type="hidden" name="aktif" value="1">
            <button class="btn pr" onclick="if(!document.getElementById('ak-user').value){alert('Pilih user dari hasil pencarian dulu.');return false}">Simpan akses</button>
        </div>
    </form>
    <p class="lbl">Satu user hanya punya satu peran. User tanpa akses aktif tidak bisa membuka /budgeting.</p>
</div>

<div class="card scroll">
    <h3>Daftar akses</h3>
    <table>
        <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Dept Head (untuk PIC)</th><th>Aktif</th><th></th></tr>
        @foreach($akses as $a)
            <tr>
                <td><b>{{ $a->user->name ?? ('#' . $a->user_id) }}</b></td>
                <td>{{ $a->user->email ?? '-' }}</td>
                <td>
                    <select name="peran" form="fa{{ $a->id }}" aria-label="Peran {{ $a->user->name ?? '' }}">
                        @foreach(['admin' => 'Admin', 'dept_head' => 'Dept Head', 'pic' => 'PIC'] as $v => $l)<option value="{{ $v }}" @selected($a->peran === $v)>{{ $l }}</option>@endforeach
                    </select>
                </td>
                <td>
                    @if($a->peran === 'pic')
                        {{ ($headDari[$a->user_id] ?? collect())->map(fn ($p) => $p->deptHead->name ?? '-')->implode(', ') ?: 'Belum ada' }}
                    @else - @endif
                </td>
                <td><input type="hidden" name="aktif" value="0" form="fa{{ $a->id }}"><input type="checkbox" name="aktif" value="1" form="fa{{ $a->id }}" @checked($a->aktif) aria-label="Aktif"></td>
                <td class="n"><button class="btn sm" form="fa{{ $a->id }}">Simpan</button></td>
            </tr>
        @endforeach
    </table>
    @foreach($akses as $a)
        <form id="fa{{ $a->id }}" method="post" action="{{ route('budgeting.akses.simpan') }}">@csrf<input type="hidden" name="user_id" value="{{ $a->user_id }}"></form>
    @endforeach
</div>
@endif

<div class="card">
    <h3>{{ $admin ? 'Hubungan PIC dan Dept Head' : 'Tambah PIC di bawah Anda' }}</h3>
    <form method="post" action="{{ route('budgeting.akses.pic.tambah') }}">
        @csrf
        <input type="hidden" name="pic_user_id" id="pic-user">
        <div class="row">
            <div style="flex:1;min-width:260px">
                <input type="search" id="pic-cari" placeholder="Cari user yang akan menjadi PIC" style="width:100%" autocomplete="off">
                <div id="pic-hasil" style="max-height:200px;overflow:auto"></div>
                <div class="lbl" id="pic-label">Belum ada user dipilih.</div>
            </div>
            @if($admin)
                <select name="dept_head_user_id" required aria-label="Dept Head">
                    <option value="">Pilih Dept Head</option>
                    @foreach($kepala as $h)<option value="{{ $h->user_id }}">{{ $h->user->name ?? ('#' . $h->user_id) }}</option>@endforeach
                </select>
            @endif
            <button class="btn pr" onclick="if(!document.getElementById('pic-user').value){alert('Pilih user dari hasil pencarian dulu.');return false}">Tambah PIC</button>
        </div>
    </form>
    <p class="lbl">Satu PIC boleh berada di bawah lebih dari satu Dept Head: tambahkan lagi dengan Dept Head lain.</p>

    @if($picHead->isEmpty())
        <p class="lbl">Belum ada PIC.</p>
    @else
    <div class="scroll">
    <table>
        <tr><th>PIC</th><th>Email</th>@if($admin)<th>Dept Head</th>@endif<th></th></tr>
        @foreach($picHead as $p)
            <tr>
                <td><b>{{ $p->pic->name ?? ('#' . $p->pic_user_id) }}</b></td>
                <td>{{ $p->pic->email ?? '-' }}</td>
                @if($admin)<td>{{ $p->deptHead->name ?? ('#' . $p->dept_head_user_id) }}</td>@endif
                <td class="n">
                    <form method="post" action="{{ route('budgeting.akses.pic.lepas', $p) }}" style="display:inline"
                          onsubmit="return confirm('Lepas PIC ini? Item tetap milik Dept Head, dan PIC dikosongkan dari item-item Dept Head tersebut.')">@csrf @method('DELETE')<button class="btn sm dg">Lepas</button></form>
                </td>
            </tr>
        @endforeach
    </table>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
@if($admin)
pasangCariUser(document.getElementById('ak-cari'), document.getElementById('ak-hasil'), document.getElementById('ak-user'), document.getElementById('ak-label'));
@endif
pasangCariUser(document.getElementById('pic-cari'), document.getElementById('pic-hasil'), document.getElementById('pic-user'), document.getElementById('pic-label'));
</script>
@endpush
