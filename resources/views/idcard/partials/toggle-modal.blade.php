{{--
    Modal Nonaktifkan / Aktifkan kartu (satu modal untuk semua halaman).
    Tombol pemicu tidak memakai onclick inline; nilainya dikirim lewat atribut data-*
    yang sudah di-escape Blade, jadi aman dari XSS. Atribut yang dipakai:
    data-toggle-card (isi: nonaktif atau aktif), data-id, data-nama, data-nomor.
--}}

<div id="toggleCardModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="toggleCardTitle">
    <div class="bg-white rounded-lg shadow-lg p-6 w-96 max-w-full">
        <h3 id="toggleCardTitle" class="text-lg font-semibold text-gray-800 mb-4"></h3>
        <form id="toggleCardForm" method="POST">
            @csrf
            <p class="text-sm text-gray-600 mb-2"><span id="toggleCardVerb"></span> kartu untuk: <span id="toggleCardNama" class="font-semibold"></span></p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Kartu (Opsional)</label>
                <input type="text" name="nomor_kartu" id="toggleCardNomor" maxlength="50" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Ubah nomor kartu jika perlu">
                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" data-toggle-close class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-400">Batal</button>
                <button type="submit" id="toggleCardSubmit" class="px-4 py-2 text-white rounded-lg text-sm"></button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal  = document.getElementById('toggleCardModal');
    const form   = document.getElementById('toggleCardForm');
    const submit = document.getElementById('toggleCardSubmit');
    const urls = {
        nonaktif: @json(route('idcard.nonaktifkan.satu', ['id' => '__ID__'])),
        aktif:    @json(route('idcard.aktifkan.satu', ['id' => '__ID__']))
    };
    const modes = {
        nonaktif: { title: 'Nonaktifkan Kartu', verb: 'Nonaktifkan', btn: 'bg-red-600 hover:bg-red-700' },
        aktif:    { title: 'Aktifkan Kartu',    verb: 'Aktifkan',    btn: 'bg-green-600 hover:bg-green-700' }
    };
    const btnClasses = ['bg-red-600', 'hover:bg-red-700', 'bg-green-600', 'hover:bg-green-700'];

    function open(mode, id, nama, nomor) {
        const m = modes[mode];
        document.getElementById('toggleCardTitle').textContent = m.title;
        document.getElementById('toggleCardVerb').textContent  = m.verb;
        document.getElementById('toggleCardNama').textContent  = nama;   // textContent: aman dari XSS
        document.getElementById('toggleCardNomor').value       = nomor || '';
        form.action = urls[mode].replace('__ID__', encodeURIComponent(id));
        submit.textContent = m.verb;
        submit.disabled = false;
        btnClasses.forEach(c => submit.classList.remove(c));
        m.btn.split(' ').forEach(c => submit.classList.add(c));
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function close() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-toggle-card]');
        if (trigger) {
            open(trigger.dataset.toggleCard, trigger.dataset.id, trigger.dataset.nama, trigger.dataset.nomor);
            return;
        }
        if (e.target.closest('[data-toggle-close]') || e.target === modal) close();
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    form.addEventListener('submit', () => { submit.disabled = true; submit.textContent = 'Memproses...'; });
    window.addEventListener('pageshow', () => { submit.disabled = false; });
})();
</script>
