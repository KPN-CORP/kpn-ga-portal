@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-gray-900">📁 Telusuri Folder untuk Kompresi</h1>
            <p class="text-sm text-gray-600">Navigasi ke folder yang berisi gambar, lalu klik tombol kompres</p>
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

        {{-- Tombol kompres folder ini (termasuk semua subfolder) --}}
        @php
            $folderParam = $currentPath ?: 'public/mailing-foto';
        @endphp
        <form method="GET" action="{{ route('mailing.kompres') }}" class="mb-4">
            <input type="hidden" name="folder" value="{{ $folderParam }}">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg font-semibold">
                🗜️ Kompres Semua Gambar di Folder Ini (termasuk subfolder)
            </button>
        </form>

        {{-- Daftar subfolder --}}
        @if(count($directories) > 0)
        <div class="mb-6">
            <h3 class="font-semibold text-gray-700 mb-2">📂 Subfolder:</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                @foreach($directories as $dir)
                <a href="{{ route('mailing.kompres.browse', ['path' => $currentPath ? $currentPath . '/' . $dir : $dir]) }}" 
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
        <div>
            <h3 class="font-semibold text-gray-700 mb-2">🖼️ File gambar di folder ini:</h3>
            <div class="space-y-2 text-sm">
                @foreach($images as $img)
                <div class="flex items-center justify-between border-b py-2">
                    <div class="flex items-center space-x-3">
                        {{-- Thumbnail gambar --}}
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
                    <a href="{{ $img['url'] }}" target="_blank" class="text-blue-500 hover:underline text-xs">
                        🔍 Preview
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <p class="text-gray-500 text-sm mt-4">Tidak ada file gambar di folder ini (hanya subfolder).</p>
        @endif

        @if($currentPath)
        <div class="mt-6 pt-4 border-t">
            <a href="{{ route('mailing.kompres.browse', ['path' => dirname($currentPath) == '.' ? '' : dirname($currentPath)]) }}" 
               class="text-blue-600 hover:underline">
                <i class="fas fa-arrow-up"></i> Naik ke folder atas
            </a>
        </div>
        @endif
    </div>
</div>
@endsection