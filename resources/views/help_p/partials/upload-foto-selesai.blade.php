<div class="bg-white rounded-lg border border-gray-200 p-4">
    <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
        <i class="fas fa-camera text-green-600 mr-2"></i> Upload Foto Hasil Pekerjaan
    </h3>
    
    <form action="{{ route('help.proses.upload-foto-selesai', $tiket) }}" 
          method="POST" 
          enctype="multipart/form-data" 
          class="space-y-3"
          id="uploadFotoForm">
        @csrf
        
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-green-400 transition-colors">
            <div class="mb-2">
                <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl"></i>
            </div>
            <input type="file" 
                   name="foto_hasil[]" 
                   id="foto_hasil"
                   multiple
                   accept="image/*"
                   class="hidden"
                   onchange="previewFotoFiles(this)">
            <button type="button" 
                    onclick="document.getElementById('foto_hasil').click()"
                    class="inline-flex items-center px-4 py-2 bg-green-50 hover:bg-green-100 text-green-700 font-medium rounded-lg border border-green-200 transition-colors">
                <i class="fas fa-plus mr-2"></i> Pilih Foto
            </button>
            <p class="text-xs text-gray-500 mt-2">Format: JPG, PNG (maks. 5MB per file)</p>
            
            <!-- INDIKATOR FILE TERLAMPIR -->
            <div id="fileIndicator" class="mt-2 text-sm font-medium text-green-600 hidden"></div>
            
            <!-- PREVIEW NAMA FILE (opsional, biar user tahu file apa saja) -->
            <div id="fotoPreview" class="mt-3 space-y-1 hidden"></div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Keterangan (opsional)
            </label>
            <textarea name="keterangan" 
                      rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm resize-none"
                      placeholder="Misal: Sudah diperbaiki, sudah dibersihkan, dll..."></textarea>
        </div>
        
        <button type="submit" 
                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
            <i class="fas fa-upload mr-2"></i> Upload Foto Hasil
        </button>
    </form>
</div>

<script>
// ==================== FUNGSI PREVIEW FOTO (DIPANGGIL SAAT ONCHANGE) ====================
window.previewFotoFiles = function(input) {
    const fileIndicator = document.getElementById('fileIndicator');
    const previewDiv = document.getElementById('fotoPreview');
    const files = input.files;
    const fileCount = files.length;
    
    if (fileCount === 0) {
        // Sembunyikan indikator dan preview
        fileIndicator.classList.add('hidden');
        previewDiv.classList.add('hidden');
        previewDiv.innerHTML = '';
        return;
    }
    
    // Tampilkan indikator jumlah file
    fileIndicator.textContent = `✅ ${fileCount} file terlampir`;
    fileIndicator.classList.remove('hidden');
    
    // Tampilkan daftar nama file (preview sederhana)
    previewDiv.classList.remove('hidden');
    previewDiv.innerHTML = '';
    
    for (let i = 0; i < fileCount; i++) {
        const file = files[i];
        const fileSize = (file.size / 1024).toFixed(0);
        const fileItem = document.createElement('div');
        fileItem.className = 'text-left text-sm text-gray-600 bg-gray-50 p-1 rounded';
        fileItem.innerHTML = `
            <i class="fas fa-image text-green-500 mr-1"></i> 
            ${file.name} <span class="text-gray-400 text-xs">(${fileSize} KB)</span>
        `;
        previewDiv.appendChild(fileItem);
    }
    
    // Optional: scroll ke bawah biar terlihat
    previewDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
};
</script>