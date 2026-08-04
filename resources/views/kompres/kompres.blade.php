@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-gray-900">🗜️ Kompres &amp; Kelola File Mailing</h1>
            <p class="text-sm text-gray-600">
                Folder target: <strong class="font-mono">{{ $selectedFolder !== '' ? $selectedFolder : '(root) storage/app' }}</strong>
                <span class="text-gray-400">(termasuk semua subfolder di dalamnya)</span>
            </p>
        </div>
        <a href="{{ route('kompres.browse') }}" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm font-semibold">
            ← Pilih Folder Lain
        </a>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ session('error') }}</div>
    @endif

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="text-2xl font-bold text-blue-600">{{ $totalFiles }}</div>
            <div class="text-sm text-gray-600">Total File (gambar + PDF)</div>
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

    {{-- Tombol aksi --}}
    <div class="bg-white border rounded-xl p-4 flex flex-wrap items-center gap-3">
        <button id="startCompressBtn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold flex items-center gap-2">
            <i class="fas fa-play"></i> Kompres Semua yang Perlu
        </button>
        <button id="deleteSelectedBtn" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold flex items-center gap-2" disabled>
            <i class="fas fa-trash"></i> Hapus File Terpilih (<span id="selectedCount">0</span>)
        </button>
        <span class="text-xs text-gray-500">
            <i class="fas fa-info-circle"></i> Kompres berjalan bertahap (50 file/batch). Gambar & PDF diproses otomatis sesuai jenisnya.
        </span>
    </div>

    {{-- Progress bar --}}
    <div class="bg-white border rounded-xl p-4 hidden" id="progressContainer">
        <div class="mb-2 flex justify-between text-sm">
            <span>Progres</span>
            <span id="progressPercent">0%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
            <div id="progressBar" class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
        </div>
        <div class="text-xs text-gray-500 mt-2">
            <span id="processedCount">0</span> / <span id="totalCount">0</span> file
        </div>
    </div>

    {{-- Log hasil --}}
    <div class="bg-gray-900 rounded-xl p-4 text-green-400 font-mono text-xs max-h-64 overflow-y-auto hidden" id="logContainer">
        <div id="logContent"><div>⚙️ Siap memproses...</div></div>
    </div>

    {{-- Tabel daftar file --}}
    <div class="bg-white border rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="p-3 text-left w-8"><input type="checkbox" id="selectAll"></th>
                    <th class="p-3 text-left">File</th>
                    <th class="p-3 text-left">Lokasi</th>
                    <th class="p-3 text-left">Ukuran</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody id="fileTableBody">
                @forelse($files as $f)
                <tr class="border-b hover:bg-gray-50" data-path="{{ $f['path'] }}">
                    <td class="p-3"><input type="checkbox" class="rowCheck" value="{{ $f['path'] }}"></td>
                    <td class="p-3">
                        {{ $f['type'] === 'pdf' ? '📄' : '🖼️' }} {{ $f['name'] }}
                    </td>
                    <td class="p-3 text-gray-500 font-mono text-xs">{{ $f['sub_dir'] ?: '(folder utama)' }}</td>
                    <td class="p-3 sizeCell">{{ $f['size_mb'] }} MB</td>
                    <td class="p-3 statusCell">
                        @if($f['need_compress'])
                            <span class="text-yellow-600 text-xs bg-yellow-100 px-2 py-0.5 rounded-full">perlu kompres</span>
                        @else
                            <span class="text-green-600 text-xs bg-green-100 px-2 py-0.5 rounded-full">sudah ≤1.5MB</span>
                        @endif
                    </td>
                    <td class="p-3">
                        <button type="button" class="deleteOneBtn text-red-500 hover:underline text-xs" data-path="{{ $f['path'] }}">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="p-6 text-center text-gray-500">Tidak ada file gambar/PDF di folder ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    const startBtn = document.getElementById('startCompressBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const selectAll = document.getElementById('selectAll');
    const selectedCountEl = document.getElementById('selectedCount');
    const progressContainer = document.getElementById('progressContainer');
    const logContainer = document.getElementById('logContainer');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const processedSpan = document.getElementById('processedCount');
    const totalSpan = document.getElementById('totalCount');
    const logContent = document.getElementById('logContent');
    const tableBody = document.getElementById('fileTableBody');

    function addLog(message, type = 'info') {
        const colors = { success: 'text-green-400', failed: 'text-red-400', skip: 'text-yellow-400', info: 'text-blue-400' };
        const div = document.createElement('div');
        div.className = (colors[type] || colors.info) + ' mb-1';
        div.innerHTML = message;
        logContent.appendChild(div);
        logContainer.classList.remove('hidden');
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    function rowFor(path) {
        return tableBody.querySelector(`tr[data-path="${CSS.escape(path)}"]`);
    }

    function updateSelectedCount() {
        const n = tableBody.querySelectorAll('.rowCheck:checked').length;
        selectedCountEl.textContent = n;
        deleteSelectedBtn.disabled = n === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            tableBody.querySelectorAll('.rowCheck').forEach(cb => cb.checked = selectAll.checked);
            updateSelectedCount();
        });
    }
    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('rowCheck')) updateSelectedCount();
    });

    // ---- KOMPRES ----
    let pendingFiles = Array.from(tableBody.querySelectorAll('tr[data-path]'))
        .filter(tr => tr.querySelector('.statusCell').textContent.includes('perlu kompres'))
        .map(tr => tr.dataset.path);
    let totalToProcess = pendingFiles.length;
    let processed = 0, successCount = 0, failedCount = 0, skipCount = 0;
    totalSpan.textContent = totalToProcess;

    if (totalToProcess === 0) {
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="fas fa-check-circle"></i> Semua file sudah sesuai';
        startBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
    }

    function updateProgress() {
        const percent = totalToProcess ? (processed / totalToProcess) * 100 : 0;
        progressBar.style.width = percent + '%';
        progressPercent.textContent = Math.round(percent) + '%';
        processedSpan.textContent = processed;
    }

    function applyCompressResult(r) {
        processed++;
        const tr = rowFor(r.name_path || '');
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
    }

    async function processBatch(batch) {
        if (batch.length === 0) return;
        try {
            const res = await fetch('{{ route("kompres.proses") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ files: batch })
            });
            const data = await res.json();
            (data.results || []).forEach((r, idx) => {
                r.name_path = batch[idx];
                applyCompressResult(r);
                const tr = rowFor(batch[idx]);
                if (tr && r.status === 'success') {
                    tr.querySelector('.sizeCell').textContent = r.new_mb + ' MB';
                    tr.querySelector('.statusCell').innerHTML = '<span class="text-green-600 text-xs bg-green-100 px-2 py-0.5 rounded-full">sudah ≤1.5MB</span>';
                }
            });
        } catch (err) {
            addLog(`⚠️ Error: ${err.message}`, 'failed');
        }
    }

    async function startCompression() {
        if (totalToProcess === 0) return;
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        progressContainer.classList.remove('hidden');
        addLog(`🚀 Memulai kompresi ${totalToProcess} file...`, 'info');
        const batchSize = 50;
        for (let i = 0; i < pendingFiles.length; i += batchSize) {
            const batch = pendingFiles.slice(i, i + batchSize);
            addLog(`📦 Batch ${Math.floor(i / batchSize) + 1} (${batch.length} file)`, 'info');
            await processBatch(batch);
        }
        addLog(`🎉 KOMPRESI SELESAI!`, 'success');
        addLog(`📊 Hasil: ${successCount} berhasil, ${skipCount} skip, ${failedCount} gagal`, 'info');
        startBtn.innerHTML = '<i class="fas fa-check-circle"></i> Selesai';
    }
    startBtn.addEventListener('click', startCompression);

    // ---- HAPUS ----
    async function deletePaths(paths) {
        if (!paths.length) return;
        try {
            const res = await fetch('{{ route("kompres.hapus") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ files: paths })
            });
            const data = await res.json();
            (data.results || []).forEach((r, idx) => {
                const path = paths[idx];
                const tr = rowFor(path);
                if (r.status === 'success') {
                    addLog(`🗑️ ${r.name} : terhapus`, 'success');
                    if (tr) tr.remove();
                } else {
                    addLog(`❌ ${r.name} : ${r.message}`, 'failed');
                }
            });
            updateSelectedCount();
        } catch (err) {
            addLog(`⚠️ Error hapus: ${err.message}`, 'failed');
        }
    }

    deleteSelectedBtn.addEventListener('click', function() {
        const checked = Array.from(tableBody.querySelectorAll('.rowCheck:checked')).map(cb => cb.value);
        if (checked.length === 0) return;
        if (!confirm(`Hapus ${checked.length} file terpilih? Tindakan ini tidak bisa dibatalkan.`)) return;
        deletePaths(checked);
    });

    tableBody.addEventListener('click', function(e) {
        const btn = e.target.closest('.deleteOneBtn');
        if (!btn) return;
        const path = btn.dataset.path;
        if (!confirm(`Hapus file ini?\n${path}`)) return;
        deletePaths([path]);
    });
});
</script>
@endpush
