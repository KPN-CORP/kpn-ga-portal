@extends('layouts.app_supplies_sidebar')

@section('content')
<div class="max-w-3xl mx-auto">
    <h2 class="text-xl font-semibold mb-1">Form Permintaan Supplies (Max 5 Item)</h2>
    <p class="text-sm text-gray-500 mb-4">Barang yang sama baru bisa diajukan lagi setelah jeda {{ $jedaMenit }} menit.</p>
    <div class="bg-white rounded-xl border p-6">
        <form method="POST" action="{{ route('supplies.permintaan.store') }}" id="multiRequestForm">
            @csrf
            <div x-data="{
                items: [{ id_barang: '', jumlah: '', keterangan: '' }],
                maxItems: 5,
                barangList: {{ Js::from($barang->map(fn($b) => ['id' => $b->id, 'nama' => $b->nama_barang, 'kode' => $b->kode_barang, 'satuan' => $b->satuan])) }},
                removeItem(index) { this.items.splice(index, 1); },
                addItem() { if(this.items.length < this.maxItems) this.items.push({ id_barang: '', jumlah: '', keterangan: '' }); }
            }">
                <template x-for="(item, index) in items" :key="index">
                    <div class="border rounded-lg p-4 mb-4 relative">
                        <button type="button" x-show="items.length > 1" @click="removeItem(index)" class="absolute top-2 right-2 text-red-500 hover:text-red-700 text-xl leading-5">&times;</button>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Barang</label>
                                <select x-model="item.id_barang" :name="'items['+index+'][id_barang]'" class="w-full border rounded-lg px-3 py-2" required>
                                    <option value="">-- Pilih --</option>
                                    <template x-for="b in barangList">
                                        <option :value="b.id" x-text="b.kode + ' - ' + b.nama + ' (' + b.satuan + ')'"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Jumlah</label>
                                <input type="number" step="0.01" x-model="item.jumlah" :name="'items['+index+'][jumlah]'" class="w-full border rounded-lg px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Keterangan</label>
                                <textarea x-model="item.keterangan" :name="'items['+index+'][keterangan]'" rows="1" class="w-full border rounded-lg px-3 py-2"></textarea>
                            </div>
                        </div>
                    </div>
                </template>
                <div class="flex justify-between mt-2">
                    <button type="button" @click="addItem" x-show="items.length < maxItems" class="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg text-sm hover:bg-blue-200">+ Tambah Barang (max 5)</button>
                    <div class="text-xs text-gray-500" x-text="items.length + ' dari ' + maxItems + ' item'"></div>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <a href="{{ route('supplies.permintaan.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</a>
                <button type="submit" id="btnAjukan" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Ajukan Semua</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function () {
        var form = document.getElementById('multiRequestForm');
        var btn  = document.getElementById('btnAjukan');

        form.addEventListener('submit', function (e) {
            if (btn.dataset.submitted === '1') { e.preventDefault(); return; }
            btn.dataset.submitted = '1';
            btn.disabled = true;
            btn.classList.add('opacity-60', 'cursor-not-allowed');
            btn.textContent = 'Mengajukan...';
        });

        // Tombol dipulihkan kalau user kembali ke halaman ini lewat tombol Back.
        window.addEventListener('pageshow', function () {
            btn.dataset.submitted = '0';
            btn.disabled = false;
            btn.classList.remove('opacity-60', 'cursor-not-allowed');
            btn.textContent = 'Ajukan Semua';
        });
    })();
</script>
@endsection