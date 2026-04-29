@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Track R – Dokumen</h2>
            <p class="text-xs text-gray-500">Daftar dokumen yang dapat Anda akses</p>
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('track-r.create') }}"
               class="flex-1 sm:flex-none inline-flex items-center justify-center
                      px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold
                      hover:bg-blue-700 transition">
                <i class="fas fa-plus mr-1"></i> Kirim Dokumen
            </a>
        </div>
    </div>

    {{-- FORM PENCARIAN & FILTER --}}
    <div class="bg-white rounded-xl border shadow-sm p-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}"
                    placeholder="Cari nomor dokumen atau judul..." 
                    class="w-full pl-9 border rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <select name="status" class="border rounded-lg px-4 py-2.5 text-sm">
                <option value="">Semua Status</option>
                <option value="dikirim" @selected(request('status') == 'dikirim')>Dikirim</option>
                <option value="diterima" @selected(request('status') == 'diterima')>Diterima</option>
                <option value="ditolak" @selected(request('status') == 'ditolak')>Ditolak</option>
                <option value="diteruskan" @selected(request('status') == 'diteruskan')>Diteruskan</option>
            </select>
            <button type="submit" class="px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-filter mr-1"></i> Terapkan
            </button>
            @if(request('search') || request('status'))
            <a href="{{ route('track-r.index') }}" class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition flex items-center">
                <i class="fas fa-times mr-1"></i> Reset
            </a>
            @endif
        </form>
    </div>

    {{-- DESKTOP TABLE --}}
    <div class="hidden sm:block bg-white rounded-xl border shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-800 border-b">
                <tr>
                    <th class="px-4 py-3 text-left font-bold">Nomor Dokumen</th>
                    <th class="px-4 py-3 text-left font-bold">Judul</th>
                    <th class="px-4 py-3 text-left font-bold">Pengirim</th>
                    <th class="px-4 py-3 text-left font-bold">Penerima Saat Ini</th>
                    <th class="px-4 py-3 text-left font-bold">Status Saya</th>
                    <th class="px-4 py-3 text-left font-bold">Penerima Lain</th>
                    <th class="px-4 py-3 text-center font-bold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($documents as $doc)
                @php
                    $userStatus = $doc->statusForUser(auth()->user());
                    $recipientsCount = $doc->recipients->count();
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono font-medium">{{ $doc->nomor_dokumen }}</td>
                    <td class="px-4 py-3 max-w-[200px] truncate" title="{{ $doc->judul }}">{{ $doc->judul }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user text-blue-600 text-xs"></i>
                            <span>{{ $doc->pengirim->name ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user-check text-green-600 text-xs"></i>
                            <span>{{ $doc->penerima->name ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $userStatus['color'] }}">
                            {{ $userStatus['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($recipientsCount > 1)
                            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                                +{{ $recipientsCount - 1 }} lainnya
                            </span>
                        @else
                            <span class="text-xs text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('track-r.show', $doc->id) }}"
                           class="text-blue-600 font-semibold hover:underline inline-flex items-center gap-1">
                            <i class="fas fa-eye text-xs"></i> Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-3xl mb-2 text-gray-300"></i>
                        <p>Tidak ada dokumen ditemukan</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- MOBILE CARD --}}
    <div class="block sm:hidden space-y-3">
        @forelse($documents as $doc)
        @php
            $userStatus = $doc->statusForUser(auth()->user());
        @endphp
        <div class="bg-white rounded-xl border shadow-sm p-4">
            <div class="flex justify-between items-start">
                <div>
                    <div class="font-mono font-semibold text-gray-800">{{ $doc->nomor_dokumen }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $doc->judul }}</div>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $userStatus['color'] }}">
                    {{ $userStatus['label'] }}
                </span>
            </div>
            <div class="mt-3 flex flex-col gap-1 text-xs text-gray-600">
                <div><i class="fas fa-user text-blue-500 mr-1"></i>Pengirim: {{ $doc->pengirim->name ?? '-' }}</div>
                <div><i class="fas fa-user-check text-green-600 mr-1"></i>Penerima: {{ $doc->penerima->name ?? '-' }}</div>
                @if($doc->recipients->count() > 1)
                <div class="text-gray-500">+{{ $doc->recipients->count() - 1 }} penerima lain</div>
                @endif
            </div>
            <div class="mt-3">
                <a href="{{ route('track-r.show', $doc->id) }}" class="text-blue-600 font-semibold text-sm hover:underline">Lihat Detail →</a>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border shadow-sm p-6 text-center text-gray-500">
            <i class="fas fa-inbox text-3xl mb-2 text-gray-300"></i><p>Tidak ada dokumen ditemukan</p>
        </div>
        @endforelse
    </div>

    {{-- PAGINATION --}}
    @if($documents->hasPages())
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm text-gray-600">
        <div>Menampilkan {{ $documents->firstItem() }} – {{ $documents->lastItem() }} dari {{ $documents->total() }} dokumen</div>
        {{ $documents->links() }}
    </div>
    @else
    <div class="text-center text-sm text-gray-500 py-4">Total {{ $documents->count() }} dokumen</div>
    @endif
</div>
@endsection