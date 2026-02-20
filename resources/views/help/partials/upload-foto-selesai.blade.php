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
            <div id="fotoPreview" class="mt-3 space-y-2 hidden"></div>
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