{{-- resources/views/help/log/index.blade.php --}}
@extends('layouts.app-help-sidebar')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Log Sistem</h2>
        <p class="text-gray-600">Catatan aktivitas sistem GA Tiket</p>
    </div>
    
    <!-- Filter -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Aksi</label>
                <select class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">Semua Aksi</option>
                    <option value="CREATED">CREATED</option>
                    <option value="UPDATED">UPDATED</option>
                    <option value="DELETED">DELETED</option>
                    <option value="TICKET_CREATED">TICKET_CREATED</option>
                    <option value="TICKET_STATUS_CHANGED">TICKET_STATUS_CHANGED</option>
                </select>
            </div>
            
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pengguna</label>
                <select class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">Semua Pengguna</option>
                    <!-- Option dari database -->
                </select>
            </div>
            
            <div class="flex items-end">
                <button class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
            </div>
        </div>
    </div>
    
    <!-- Log Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pengguna</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Model</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($log as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $item->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="px-4 py-3">
                            @if($item->pengguna)
                            <div class="font-medium text-gray-900">{{ $item->pengguna->nama }}</div>
                            <div class="text-xs text-gray-500">{{ $item->pengguna->email ?? '-' }}</div>
                            @else
                            <span class="text-gray-500">System</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $actionColors = [
                                    'CREATED' => 'bg-green-100 text-green-800',
                                    'UPDATED' => 'bg-blue-100 text-blue-800',
                                    'DELETED' => 'bg-red-100 text-red-800',
                                    'TICKET_CREATED' => 'bg-purple-100 text-purple-800',
                                    'TICKET_STATUS_CHANGED' => 'bg-orange-100 text-orange-800',
                                    'VIEW_TICKET_DETAIL' => 'bg-gray-100 text-gray-800'
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $actionColors[$item->aksi] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $item->aksi }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $item->model }}
                        </td>
                        <td class="px-4 py-3">
                            <button type="button" 
                                    onclick="showLogData({{ $item->id }})"
                                    class="inline-flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                                <i class="fas fa-eye mr-1"></i> Lihat Data
                            </button>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $item->ip_address }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-history text-3xl mb-2 block"></i>
                            <p>Tidak ada log ditemukan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $log->links() }}
        </div>
    </div>
</div>

<!-- Modal for Log Data -->
<div id="logModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Detail Log</h3>
                <button type="button" 
                        onclick="closeLogModal()"
                        class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-y-auto max-h-[60vh]">
            <div id="logDetailContent"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showLogData(logId) {
    fetch(`/help/log/${logId}/detail`)
        .then(response => response.json())
        .then(data => {
            let content = '';
            
            if (data.data_lama) {
                content += `
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Data Lama</h4>
                        <pre class="bg-gray-50 p-3 rounded-lg text-sm overflow-auto">${JSON.stringify(JSON.parse(data.data_lama), null, 2)}</pre>
                    </div>
                `;
            }
            
            if (data.data_baru) {
                content += `
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Data Baru</h4>
                        <pre class="bg-gray-50 p-3 rounded-lg text-sm overflow-auto">${JSON.stringify(JSON.parse(data.data_baru), null, 2)}</pre>
                    </div>
                `;
            }
            
            document.getElementById('logDetailContent').innerHTML = content;
            document.getElementById('logModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function closeLogModal() {
    document.getElementById('logModal').classList.add('hidden');
}

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLogModal();
    }
});
</script>
@endpush
@endsection