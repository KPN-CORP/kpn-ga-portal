<div class="mb-4">
    <label>Kode Barang</label>
    <input type="text" name="kode_barang" value="{{ old('kode_barang') }}" class="w-full border rounded-lg p-2" required>
</div>
<div class="mb-4">
    <label>Nama Barang</label>
    <input type="text" name="nama_barang" value="{{ old('nama_barang') }}" class="w-full border rounded-lg p-2" required>
</div>
<div class="mb-4">
    <label>Satuan</label>
    <select name="satuan" class="w-full border rounded-lg p-2">
        <option value="">Pilih</option>
        <option value="pcs">Pcs</option><option value="unit">Unit</option><option value="box">Box</option>
        <option value="pack">Pack</option><option value="set">Set</option><option value="kg">Kg</option>
        <option value="liter">Liter</option><option value="meter">Meter</option>
    </select>
</div>
<div class="mb-4">
    <label>Harga (Rp)</label>
    <input type="number" step="100" name="harga" value="{{ old('harga', $barang->harga ?? 0) }}" class="w-full border rounded-lg p-2">
</div>
<div class="mb-4">
    <label>Area Simpan</label>
    <input type="text" name="lokasi_rak" value="{{ old('lokasi_rak') }}" class="w-full border rounded-lg p-2">
</div>
<div class="mb-4">
    <label>Stok Minimum</label>
    <input type="number" step="0.01" name="stok_minimum" value="{{ old('stok_minimum', 0) }}" class="w-full border rounded-lg p-2">
</div>
<div class="mb-4">
    <label>Deskripsi</label>
    <textarea name="deskripsi" rows="3" class="w-full border rounded-lg p-2">{{ old('deskripsi') }}</textarea>
</div>