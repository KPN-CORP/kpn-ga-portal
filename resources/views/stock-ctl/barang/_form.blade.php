<div class="mb-4">
    <label class="block text-sm font-medium text-gray-600 mb-1">Kode Barang</label>
    <input type="text" name="kode_barang" value="{{ old('kode_barang', $barang->kode_barang ?? '') }}" 
           class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-600 mb-1">Nama Barang</label>
    <input type="text" name="nama_barang" value="{{ old('nama_barang', $barang->nama_barang ?? '') }}" 
           class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-600 mb-1">Satuan</label>
    <select name="satuan" 
        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">

        <option value="">Pilih satuan</option>
        <option value="pcs" {{ old('satuan', $barang->satuan ?? '') == 'pcs' ? 'selected' : '' }}>Pcs</option>
        <option value="unit" {{ old('satuan', $barang->satuan ?? '') == 'unit' ? 'selected' : '' }}>Unit</option>
        <option value="box" {{ old('satuan', $barang->satuan ?? '') == 'box' ? 'selected' : '' }}>Box</option>
        <option value="pack" {{ old('satuan', $barang->satuan ?? '') == 'pack' ? 'selected' : '' }}>Pack</option>
        <option value="set" {{ old('satuan', $barang->satuan ?? '') == 'set' ? 'selected' : '' }}>Set</option>
        <option value="lusin" {{ old('satuan', $barang->satuan ?? '') == 'lusin' ? 'selected' : '' }}>Lusin</option>
        <option value="rim" {{ old('satuan', $barang->satuan ?? '') == 'rim' ? 'selected' : '' }}>Rim</option>
        <option value="kg" {{ old('satuan', $barang->satuan ?? '') == 'kg' ? 'selected' : '' }}>Kg</option>
        <option value="gram" {{ old('satuan', $barang->satuan ?? '') == 'gram' ? 'selected' : '' }}>Gram</option>
        <option value="liter" {{ old('satuan', $barang->satuan ?? '') == 'liter' ? 'selected' : '' }}>Liter</option>
        <option value="meter" {{ old('satuan', $barang->satuan ?? '') == 'meter' ? 'selected' : '' }}>Meter</option>
        <option value="roll" {{ old('satuan', $barang->satuan ?? '') == 'roll' ? 'selected' : '' }}>Roll</option>

    </select>
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-600 mb-1">Harga</label>
    <input type="number" step="100" name="harga" value="{{ old('harga', $barang->harga ?? '') }}" 
           class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-600 mb-1">Deskripsi</label>
    <textarea name="deskripsi" rows="3" 
        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">{{ old('deskripsi', $barang->deskripsi ?? '') }}</textarea>
</div>