<div class="bg-white rounded-lg border border-gray-200 p-4">
    <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
        <i class="fas fa-hourglass-half text-orange-500 mr-2"></i> Minta Info / PO
    </h3>
    
    <form action="{{ route('help.proses.waiting', $tiket) }}" method="POST" class="space-y-3">
        @csrf
        <div>
            <textarea name="catatan" 
                      rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm resize-none"
                      placeholder="Contoh: Proses PO Barang, menunggu approval, perlu info tambahan..."
                      required></textarea>
        </div>
        
        <button type="submit" 
                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors">
            <i class="fas fa-hourglass-half mr-2"></i> Set Menunggu (WAITING)
        </button>
    </form>
</div>