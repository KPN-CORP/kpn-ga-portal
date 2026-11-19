{{-- Filter area standar HSRM (cari area + datalist), sama dengan equipments/certificates.
     Param: $areas (collection AreaKerja), $listId (id datalist, unik per halaman). Kirim: area_id --}}
<div class="relative inline-block w-full sm:w-auto">
    <input type="text" id="area_name_filter" list="{{ $listId }}" autocomplete="off"
           placeholder="Cari area..." aria-label="Cari area"
           value="{{ optional($areas->firstWhere('id_area_kerja', request('area_id')))->nama_area }}"
           class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-full sm:w-40">
    <input type="hidden" name="area_id" id="area_id_filter" value="{{ request('area_id') }}">
    <datalist id="{{ $listId }}">
        <option value="">All Areas</option>
        @foreach($areas as $area)
            <option value="{{ $area->nama_area }}" data-id="{{ $area->id_area_kerja }}"></option>
        @endforeach
    </datalist>
</div>
<script>
    (function () {
        const nameInput = document.getElementById('area_name_filter');
        const idInput = document.getElementById('area_id_filter');
        const datalist = document.getElementById(@json($listId));
        if (!nameInput || !idInput || !datalist) return;
        const findOpt = (v) => Array.from(datalist.options).find(o => o.value === v);
        function syncAreaId() {
            const typed = nameInput.value.trim();
            if (typed === '') { idInput.value = ''; return; }
            const m = findOpt(typed);
            idInput.value = m ? (m.dataset.id || '') : idInput.value;
        }
        nameInput.addEventListener('input', syncAreaId);
        nameInput.addEventListener('change', syncAreaId);
        nameInput.closest('form').addEventListener('submit', function () {
            const typed = nameInput.value.trim();
            if (typed !== '' && !findOpt(typed)) idInput.value = '';
        });
    })();
</script>
