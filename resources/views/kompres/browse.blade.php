@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-gray-900">📁 Telusuri Folder untuk Kompresi &amp; Hapus</h1>
            <p class="text-sm text-gray-600">Navigasi ke folder yang berisi gambar/PDF, lalu kompres atau hapus</p>
        </div>
        <a href="{{ route('mailing.proses') }}" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm font-semibold">
            ← Kembali ke Proses Mailing
        </a>
    </div>

    <div class="bg-white border rounded-xl p-4">
        <div class="text-sm text-gray-500 mb-2">
            📂 Lokasi saat ini:
            <span class="font-mono bg-gray-100 px-2 py-1 rounded">
                /storage/app/{{ $currentPath ?: '(root)' }}
            </span>
        </div>

        {{-- Tombol kompres folder ini (termasuk semua subfolder, gambar & PDF) --}}
        @php
            $folderParam = $currentPath !== '' ? $currentPath : '';
        @endphp
        <form method="GET" action="{{ route('kompres.index') }}" class="mb-4 inline-block">
            <input type="hidden" name="folder" value="{{ $folderParam }}">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg font-semibold">
                🗜️ Kompres Semua Gambar &amp; PDF di
                {{ $currentPath !== '' ? 'Folder Ini' : 'Seluruh storage/app' }} (termasuk subfolder)
            </button>
        </form>

        <button type="button" id="deleteSelectedBtn" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg font-semibold mb-4" disabled>
            🗑️ Hapus Terpilih (<span id="selectedCount">0</span>)
        </button>

        {{-- Log hasil hapus --}}
        <div class="bg-gray-900 rounded-xl p-3 text-green-400 font-mono text-xs max-h-40 overflow-y-auto hidden mb-4" id="logContainer">
            <div id="logContent"></div>
        </div>

        {{-- Daftar subfolder --}}
        @if(count($directories) > 0)
        <div class="mb-6">
            <h3 class="font-semibold text-gray-700 mb-2">📂 Subfolder:</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                @foreach($directories as $dir)
                <a href="{{ route('kompres.browse', ['path' => $currentPath ? $currentPath . '/' . $dir : $dir]) }}"
                   class="flex items-center p-2 border rounded-lg hover:bg-blue-50">
                    <i class="fas fa-folder text-yellow-600 mr-2"></i>
                    <span class="text-sm">{{ $dir }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Daftar gambar di folder ini (tidak termasuk subfolder) --}}
        @if(count($images) > 0)
        <div class="mb-6">
            <h3 class="font-semibold text-gray-700 mb-2">🖼️ File gambar di folder ini:</h3>
            <div class="space-y-2 text-sm">
                @foreach($images as $img)
                <div class="flex items-center justify-between border-b py-2" data-path="{{ $img['path'] }}">
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" class="rowCheck" value="{{ $img['path'] }}">
                        <a href="{{ $img['url'] }}" target="_blank" title="Klik untuk melihat ukuran penuh">
                            <img src="{{ $img['url'] }}"
                                 alt="{{ $img['name'] }}"
                                 class="w-16 h-16 object-cover rounded border hover:opacity-80 transition"
                                 onerror="this.style.display='none'">
                        </a>
                        <div>
                            <span class="font-medium">{{ $img['name'] }}</span>
                            <span class="text-gray-500 ml-2">{{ $img['size_mb'] }} MB</span>
                            @if($img['need_compress'])
                                <span class="text-yellow-600 text-xs bg-yellow-100 px-2 py-0.5 rounded-full">(perlu kompres)</span>
                            @else
                                <span class="text-green-600 text-xs bg-green-100 px-2 py-0.5 rounded-full">(sudah ≤1.5MB)</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ $img['url'] }}" target="_blank" class="text-blue-500 hover:underline text-xs">🔍 Preview</a>
                        <button type="button" class="deleteOneBtn text-red-500 hover:underline text-xs" data-path="{{ $img['path'] }}">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Daftar PDF di folder ini --}}
        @if(count($pdfs) > 0)
        <div class="mb-6">
            <h3 class="font-semibold text-gray-700 mb-2">📄 File PDF di folder ini:</h3>
            <div class="space-y-2 text-sm">
                @foreach($pdfs as $pdf)
                <div class="flex items-center justify-between border-b py-2" data-path="{{ $pdf['path'] }}">
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" class="rowCheck" value="{{ $pdf['path'] }}">
                        <span class="text-2xl">📄</span>
                        <div>
                            <span class="font-medium">{{ $pdf['name'] }}</span>
                            <span class="text-gray-500 ml-2">{{ $pdf['size_mb'] }} MB</span>
                            @if($pdf['need_compress'])
                                <span class="text-yellow-600 text-xs bg-yellow-100 px-2 py-0.5 rounded-full">(perlu kompres)</span>
                            @else
                                <span class="text-green-600 text-xs bg-green-100 px-2 py-0.5 rounded-full">(sudah ≤1.5MB)</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ $pdf['url'] }}" target="_blank" class="text-blue-500 hover:underline text-xs">🔍 Preview</a>
                        <button type="button" class="deleteOneBtn text-red-500 hover:underline text-xs" data-path="{{ $pdf['path'] }}">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if(count($images) === 0 && count($pdfs) === 0)
        <p class="text-gray-500 text-sm mt-4">Tidak ada file gambar/PDF di folder ini (hanya subfolder).</p>
        @endif

        @if($currentPath)
        <div class="mt-6 pt-4 border-t">
            <a href="{{ route('kompres.browse', ['path' => dirname($currentPath) == '.' ? '' : dirname($currentPath)]) }}"
               class="text-blue-600 hover:underline">
                <i class="fas fa-arrow-up"></i> Naik ke folder atas
            </a>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const selectedCountEl = document.getElementById('selectedCount');
    const logContainer = document.getElementById('logContainer');
    const logContent = document.getElementById('logContent');

    function addLog(message, ok = true) {
        const div = document.createElement('div');
        div.className = ok ? 'text-green-400 mb-1' : 'text-red-400 mb-1';
        div.textContent = message;
        logContent.appendChild(div);
        logContainer.classList.remove('hidden');
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    function updateSelectedCount() {
        const n = document.querySelectorAll('.rowCheck:checked').length;
        selectedCountEl.textContent = n;
        deleteSelectedBtn.disabled = n === 0;
    }
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('rowCheck')) updateSelectedCount();
    });

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
                const row = document.querySelector(`[data-path="${CSS.escape(path)}"]`);
                if (r.status === 'success') {
                    addLog(`🗑️ ${r.name} : terhapus`, true);
                    if (row) row.remove();
                } else {
                    addLog(`❌ ${r.name} : ${r.message}`, false);
                }
            });
            updateSelectedCount();
        } catch (err) {
            addLog(`⚠️ Error hapus: ${err.message}`, false);
        }
    }

    deleteSelectedBtn.addEventListener('click', function() {
        const checked = Array.from(document.querySelectorAll('.rowCheck:checked')).map(cb => cb.value);
        if (checked.length === 0) return;
        if (!confirm(`Hapus ${checked.length} file terpilih? Tindakan ini tidak bisa dibatalkan.`)) return;
        deletePaths(checked);
    });

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.deleteOneBtn');
        if (!btn) return;
        const path = btn.dataset.path;
        if (!confirm(`Hapus file ini?\n${path}`)) return;
        deletePaths([path]);
    });
});
</script>
@endpush
