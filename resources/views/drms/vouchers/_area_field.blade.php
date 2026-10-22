{{-- Field Area voucher. Dipakai di create, edit, dan upload.
     Variabel: $lockedArea, $areas, $areasByBu, $isSuperAdmin, $selectedArea (opsional) --}}
@php $selectedArea = old('area', $selectedArea ?? ''); @endphp
<div class="mb-4">
    <label for="area" class="block text-sm font-medium text-gray-700 mb-1">Area</label>

    @if($lockedArea ?? null)
        <input type="text" value="{{ $lockedArea }}" disabled
               class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600">
        <p class="text-xs text-gray-500 mt-1">Voucher otomatis tercatat di area Anda dan hanya bisa dilihat admin area ini.</p>
    @elseif($isSuperAdmin ?? false)
        <select name="area" id="area" required
                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">-- Pilih Area --</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">Pilihan area mengikuti Business Unit yang dipilih.</p>
        <script>
        (function () {
            const areasByBu = @json($areasByBu ?? []);
            const selected  = @json($selectedArea);
            const buSelect  = document.getElementById('business_unit_id');
            const areaSelect = document.getElementById('area');
            function fill() {
                const list = (buSelect && areasByBu[buSelect.value]) ? areasByBu[buSelect.value] : [];
                const keep = areaSelect.value || selected;
                areaSelect.innerHTML = '<option value="">-- Pilih Area --</option>';
                list.forEach(function (a) {
                    const o = document.createElement('option');
                    o.value = a; o.textContent = a; o.selected = (a === keep);
                    areaSelect.appendChild(o);
                });
            }
            if (buSelect) buSelect.addEventListener('change', function () { areaSelect.value = ''; fill(); });
            fill();
        })();
        </script>
    @else
        <select name="area" id="area" required
                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">-- Pilih Area --</option>
            @foreach(($areas ?? collect()) as $a)
                <option value="{{ $a }}" {{ strcasecmp($selectedArea, $a) === 0 ? 'selected' : '' }}>{{ $a }}</option>
            @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">Voucher hanya bisa dilihat dan diberikan ke pemohon di area yang dipilih.</p>
    @endif
</div>
