@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-gray-900">🗜️ Kompres Semua Foto Mailing</h1>
            <p class="text-sm text-gray-600">Kompres gambar ke maksimal 1.5 MB tanpa mengubah format (JPG/PNG/WEBP)</p>
        </div>
        <a href="{{ route('mailing.proses') }}" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm font-semibold">
            ← Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="text-2xl font-bold text-blue-600">{{ $totalFiles }}</div>
            <div class="text-sm text-gray-600">Total Foto</div>
        </div>
        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="text-2xl font-bold text-yellow-600">{{ $needCompress }}</div>
            <div class="text-sm text-gray-600">Perlu dikompres</div>
        </div>
        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="text-2xl font-bold text-green-600">{{ $totalFiles - $needCompress }}</div>
            <div class="text-sm text-gray-600">Sudah ≤ 1.5 MB</div>
        </div>
    </div>

    <div class="bg-white border rounded-xl p-4">
        <button id="startCompressBtn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold flex items-center gap-2">
            <i class="fas fa-play"></i> Mulai Kompres Semua
        </button>
        <p class="text-xs text-gray-500 mt-2">
            <i class="fas fa-info-circle"></i> Proses berjalan bertahap (50 file per batch) agar tidak timeout.
        </p>
    </div>

    <div class="bg-white border rounded-xl p-4 hidden" id="progressContainer">
        <div class="mb-2 flex justify-between text-sm">
            <span>Progres Kompresi</span>
            <span id="progressPercent">0%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
            <div id="progressBar" class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
        </div>
        <div class="text-xs text-gray-500 mt-2">
            <span id="processedCount">0</span> / <span id="totalCount">0</span> file
        </div>
    </div>

    <div class="bg-gray-900 rounded-xl p-4 text-green-400 font-mono text-xs max-h-96 overflow-y-auto hidden" id="logContainer">
        <div id="logContent"><div>⚙️ Siap memulai kompresi...</div></div>
    </div>

    <div id="refreshContainer" class="hidden text-center">
        <a href="{{ route('mailing.kompres') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">🔄 Refresh Halaman</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startBtn = document.getElementById('startCompressBtn');
    const progressContainer = document.getElementById('progressContainer');
    const logContainer = document.getElementById('logContainer');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const processedSpan = document.getElementById('processedCount');
    const totalSpan = document.getElementById('totalCount');
    const logContent = document.getElementById('logContent');
    const refreshContainer = document.getElementById('refreshContainer');

    // Data dari server
    let allFiles = @json($files);
    let pendingFiles = allFiles.filter(f => f.need_compress).map(f => f.name);
    let totalToProcess = pendingFiles.length;
    let processed = 0;
    let successCount = 0;
    let failedCount = 0;
    let skipCount = 0;

    if (totalToProcess === 0 && allFiles.length > 0) {
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="fas fa-check-circle"></i> Semua file sudah sesuai';
        startBtn.classList.remove('bg-green-600');
        startBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
    } else if (allFiles.length === 0) {
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="fas fa-folder-open"></i> Tidak ada file foto';
        startBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
    }

    totalSpan.textContent = totalToProcess;

    function addLog(message, type = 'info') {
        const colors = { success: 'text-green-400', failed: 'text-red-400', skip: 'text-yellow-400', info: 'text-blue-400' };
        const div = document.createElement('div');
        div.className = (colors[type] || colors.info) + ' mb-1';
        div.innerHTML = message;
        logContent.appendChild(div);
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    function updateProgress() {
        const percent = totalToProcess ? (processed / totalToProcess) * 100 : 0;
        progressBar.style.width = percent + '%';
        progressPercent.textContent = Math.round(percent) + '%';
        processedSpan.textContent = processed;
    }

    async function processBatch(batch) {
        if (!batch.length) return;
        try {
            const res = await fetch('{{ route("mailing.kompres.proses") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ files: batch })
            });
            const data = await res.json();
            if (data.results) {
                data.results.forEach(r => {
                    processed++;
                    if (r.status === 'success') {
                        successCount++;
                        addLog(`✅ ${r.name} : ${r.old_mb} MB → ${r.new_mb} MB`, 'success');
                    } else if (r.status === 'failed') {
                        failedCount++;
                        addLog(`❌ ${r.name} : ${r.message}`, 'failed');
                    } else {
                        skipCount++;
                        addLog(`⏭️ ${r.name} : ${r.message}`, 'skip');
                    }
                    updateProgress();
                });
            }
        } catch (err) {
            addLog(`⚠️ Error: ${err.message}`, 'failed');
        }
    }

    async function startCompression() {
        if (!totalToProcess) return;
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        progressContainer.classList.remove('hidden');
        logContainer.classList.remove('hidden');
        addLog(`🚀 Memulai kompresi ${totalToProcess} file...`, 'info');
        const batchSize = 50;
        for (let i = 0; i < pendingFiles.length; i += batchSize) {
            const batch = pendingFiles.slice(i, i + batchSize);
            addLog(`📦 Batch ${Math.floor(i/batchSize)+1} (${batch.length} file)`, 'info');
            await processBatch(batch);
        }
        addLog(`🎉 KOMPRESI SELESAI!`, 'success');
        addLog(`📊 Hasil: ${successCount} berhasil, ${skipCount} skip, ${failedCount} gagal`, 'info');
        startBtn.innerHTML = '<i class="fas fa-check-circle"></i> Selesai';
        startBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
        refreshContainer.classList.remove('hidden');
    }

    startBtn.addEventListener('click', startCompression);
});
</script>
@endpush