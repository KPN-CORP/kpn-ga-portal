{{-- params: $tahun, $daftarTahun. Hanya Admin dan Dept Head. Hanya menyalin budget bulanan, bukan item. --}}
<dialog id="modal-salin" aria-labelledby="salin-judul">
    <div class="dh"><b id="salin-judul">Salin budget dari tahun lain</b><button type="button" class="btn sm" onclick="tutup('modal-salin')">Tutup</button></div>
    <form class="db" method="post" action="{{ route('budgeting.budget.salin') }}">
        @csrf
        <p class="lbl">Yang disalin hanya angka budget bulanan item yang Anda kelola. Item tidak dibuat ulang karena item dipakai lintas tahun.</p>
        <div class="f">
            <label><span>Salin dari tahun</span>
                <select name="dari">@foreach($daftarTahun as $t)<option value="{{ $t }}" @selected($t == $tahun - 1)>{{ $t }}</option>@endforeach</select>
            </label>
            <label><span>Ke tahun</span>
                <select name="ke">@foreach($daftarTahun as $t)<option value="{{ $t }}" @selected($t == $tahun)>{{ $t }}</option>@endforeach</select>
            </label>
        </div>
        <label style="display:block;margin-top:10px"><input type="hidden" name="timpa" value="0"><input type="checkbox" name="timpa" value="1"> Timpa angka yang sudah terisi di tahun tujuan</label>
        <div class="row" style="justify-content:flex-end;margin-top:12px"><button class="btn pr">Salin budget</button></div>
    </form>
</dialog>
