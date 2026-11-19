<div class="bg-white rounded-lg border border-gray-200 p-4">
    <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
        <i class="fas fa-play-circle text-blue-500 mr-2"></i> Lanjutkan Proses
    </h3>
    
    <form action="{{ route('help.proses.resume', $tiket) }}" method="POST" class="space-y-3">
        @csrf
        <div>
            <textarea name="catatan" 
                      rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm resize-none"
                      placeholder="Catatan lanjutan proses (misal: PO sudah disetujui, barang sudah datang, dll)"
                      required></textarea>
        </div>
        
        <button type="button" 
                onclick="confirmResume(this)"
                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
            <i class="fas fa-play mr-2"></i> Lanjutkan Proses
        </button>
    </form>
</div>