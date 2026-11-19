{{--
    Modal "Kembalikan Kartu" untuk pemohon (satu modal untuk semua halaman).
    Tombol pemicu: <button type="button" data-return-card data-id="{{ $item->id }}" data-nama="{{ $item->nama }}" data-nomor="{{ $item->nomor_kartu }}">
    Nilai dikirim lewat atribut data-* (di-escape Blade) dan ditulis dengan textContent, jadi aman dari XSS.
--}}
<div id="returnCardModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="returnCardTitle">
    <div class="bg-white rounded-lg shadow-lg p-6 w-96 max-w-full">
        <h3 id="returnCardTitle" class="text-lg font-semibold text-gray-800 mb-1">Kembalikan Kartu</h3>
        <p class="text-sm text-gray-600 mb-4">
            Kartu <span id="returnCardNama" class="font-semibold"></span>
            <span id="returnCardNomorWrap" class="hidden">(No. <span id="returnCardNomor" class="font-mono"></span>)</span>
        </p>
        <form id="returnCardForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi kartu *</label>
                <select name="return_kondisi" id="returnCardKondisi" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="baik">Baik</option>
                    <option value="rusak">Rusak</option>
                    <option value="hilang">Hilang</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                <textarea name="return_note" rows="2" maxlength="500" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Mis. kartu diserahkan ke resepsionis lantai 3"></textarea>
            </div>
            <p class="text-xs text-gray-500 mb-4">Serahkan kartu fisik ke GA. Pengembalian dianggap selesai setelah GA mengonfirmasi.</p>
            <div class="flex justify-end gap-3">
                <button type="button" data-return-close class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-400">Batal</button>
                <button type="submit" id="returnCardSubmit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Ajukan Pengembalian</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('returnCardModal');
    const form  = document.getElementById('returnCardForm');
    const submit = document.getElementById('returnCardSubmit');
    const url = @json(route('idcard.return.request', ['id' => '__ID__']));

    function open(id, nama, nomor) {
        document.getElementById('returnCardNama').textContent = nama;
        const wrap = document.getElementById('returnCardNomorWrap');
        document.getElementById('returnCardNomor').textContent = nomor || '';
        wrap.classList.toggle('hidden', !nomor);
        form.reset();
        form.action = url.replace('__ID__', encodeURIComponent(id));
        submit.disabled = false;
        submit.textContent = 'Ajukan Pengembalian';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function close() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.querySelectorAll('[data-return-card]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            open(btn.dataset.id, btn.dataset.nama, btn.dataset.nomor);
        });
    });
    document.querySelectorAll('[data-return-close]').forEach(b => b.addEventListener('click', close));
    modal.addEventListener('click', e => { if (e.target === modal) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

    // cegah klik ganda
    form.addEventListener('submit', function () {
        submit.disabled = true;
        submit.textContent = 'Memproses...';
    });
    window.addEventListener('pageshow', function () { submit.disabled = false; close(); });
})();
</script>
