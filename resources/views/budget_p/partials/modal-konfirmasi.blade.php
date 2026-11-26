{{-- Dialog konfirmasi bersama (pengganti confirm() bawaan browser). Pakai: konfirmasi(url, {judul, teks, tombol, method, bahaya}) --}}
<dialog id="modal-konfirmasi" class="pg-dlg" aria-labelledby="kf-judul">
    <div class="dh"><b id="kf-judul">Konfirmasi</b><button type="button" class="btn sm" onclick="tutup('modal-konfirmasi')">Tutup</button></div>
    <form method="post" id="kf-form" class="db">
        @csrf
        <input type="hidden" name="_method" id="kf-method" value="POST">
        <p id="kf-teks" style="margin:0 0 14px"></p>
        <div class="pg-acts">
            <button type="button" class="btn" onclick="tutup('modal-konfirmasi')">Batal</button>
            <button class="btn pr" id="kf-ok">Ya, lanjutkan</button>
        </div>
    </form>
</dialog>
<script>
    function konfirmasi(url, o){
        o = o || {};
        var f = document.getElementById('kf-form');
        f.action = url;
        document.getElementById('kf-method').value = o.method || 'POST';
        document.getElementById('kf-judul').textContent = o.judul || 'Konfirmasi';
        document.getElementById('kf-teks').textContent = o.teks || 'Lanjutkan tindakan ini?';
        var ok = document.getElementById('kf-ok');
        ok.type = 'submit'; ok.onclick = null;
        ok.textContent = o.tombol || 'Ya, lanjutkan';
        ok.className = 'btn pr' + (o.bahaya ? ' dg' : '');
        if (o.bahaya) { ok.style.background = 'var(--er)'; ok.style.borderColor = 'var(--er)'; ok.style.color = '#fff'; }
        else { ok.style.background = ''; ok.style.borderColor = ''; ok.style.color = ''; }
        buka('modal-konfirmasi');
    }
    /* Konfirmasi untuk form yang sudah ada: dipakai sebagai onsubmit="return konfirmasiForm(this, {...})" */
    function konfirmasiForm(form, o){
        konfirmasi('#', o);
        var ok = document.getElementById('kf-ok');
        ok.type = 'button';
        ok.onclick = function(){ tutup('modal-konfirmasi'); form.submit(); };
        return false;
    }
</script>
