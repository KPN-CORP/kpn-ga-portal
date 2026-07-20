@extends('layouts.app-sidebar')

@php
    // Helper functions untuk status dengan warna solid (menggunakan label)
    if (!function_exists('statusColorClass')) {
        function statusColorClass($statusLabel) {
            $label = strtolower(trim($statusLabel));
            if (str_contains($label, 'kirim')) return 'bg-blue-600 text-white border-blue-700';
            if (str_contains($label, 'terima')) return 'bg-emerald-600 text-white border-emerald-700';
            if (str_contains($label, 'tolak')) return 'bg-rose-600 text-white border-rose-700';
            if (str_contains($label, 'terus')) return 'bg-amber-600 text-white border-amber-700';
            return 'bg-purple-600 text-white border-purple-700';
        }
    }

    if (!function_exists('statusIcon')) {
        function statusIcon($statusLabel) {
            $label = strtolower(trim($statusLabel));
            if (str_contains($label, 'kirim')) return 'fas fa-paper-plane';
            if (str_contains($label, 'terima')) return 'fas fa-check-circle';
            if (str_contains($label, 'tolak')) return 'fas fa-times-circle';
            if (str_contains($label, 'terus')) return 'fas fa-share';
            return 'fas fa-tag';
        }
    }
@endphp

@section('content')
<div class="space-y-8 text-gray-800 font-sans antialiased">

    {{-- HEADER MODERN --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">
        <div>
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent">
                Track R – Dokumen
            </h1>
            <p class="text-sm text-slate-500 mt-1.5">
                Kelola dan pantau semua dokumen dalam satu tampilan modern
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('track-r.create') }}"
               class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-xl text-sm font-semibold shadow-md shadow-blue-200 hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
                <i class="fas fa-plus text-xs"></i> Kirim Dokumen
            </a>
            <a href="{{ route('track-r.export', request()->query()) }}"
               class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-semibold shadow-sm hover:bg-slate-50 hover:border-slate-300 transition-all duration-200">
                <i class="fas fa-download text-xs text-emerald-600"></i> CSV
            </a>
        </div>
    </div>

    {{-- FILTER CARD --}}
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-slate-200/80 shadow-xl shadow-slate-100 p-5 transition-all">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1.5">
                    Cari dokumen
                </label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nomor atau judul dokumen..."
                           class="w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-slate-50/50">
                </div>
            </div>

            <div class="flex gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1.5">
                        Dari tanggal
                    </label>
                    <input type="date" name="from" value="{{ request('from') }}"
                           class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1.5">
                        Sampai tanggal
                    </label>
                    <input type="date" name="to" value="{{ request('to') }}"
                           class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1.5">
                    Status
                </label>
                <select name="status" class="border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 bg-slate-50/50 pr-8">
                    <option value="">Semua status</option>
                    <option value="dikirim" @selected(request('status') == 'dikirim')>Dikirim</option>
                    <option value="diterima" @selected(request('status') == 'diterima')>Diterima</option>
                    <option value="ditolak" @selected(request('status') == 'ditolak')>Ditolak</option>
                    <option value="diteruskan" @selected(request('status') == 'diteruskan')>Diteruskan</option>
                </select>
            </div>

            <div class="flex gap-2 items-end">
                <button type="submit"
                        class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl text-sm font-semibold shadow-md shadow-blue-200 hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-sliders-h mr-1.5"></i> Terapkan
                </button>
                @if(request('search') || request('status') || request('from') || request('to'))
                    <a href="{{ route('track-r.index') }}"
                       class="px-5 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm font-medium hover:bg-slate-50 transition-all flex items-center gap-1">
                        <i class="fas fa-undo-alt text-xs"></i> Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- TABEL DESKTOP (diperbaiki agar tidak scroll horizontal & No Dokumen wrap) --}}
    <div class="hidden sm:block">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl shadow-slate-100 transition-all">
            <table class="w-full table-fixed">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-500 text-xs font-semibold uppercase tracking-wider border-b border-slate-200">
                        {{-- Lebar kolom disesuaikan secara proporsional --}}
                        <th class="px-4 py-3 text-left w-1/12">No Dokumen</th>
                        <th class="px-4 py-3 text-left w-2/12">Judul</th>
                        <th class="px-4 py-3 text-left w-1/12">Pengirim</th>
                        @if(auth()->user()->isSuperadminTrack())
                            <th class="px-4 py-3 text-left w-1/12">Unit Bisnis</th>
                        @endif
                        <th class="px-4 py-3 text-left w-1/12">Tgl Kirim</th>
                        <th class="px-4 py-3 text-left w-2/12">Penerima</th>
                        <th class="px-4 py-3 text-left w-1/12">Status</th>
                        <th class="px-4 py-3 text-left w-1/12">Update</th>
                        <th class="px-4 py-3 text-right w-1/12">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($documents as $doc)
                        @php
                            $userStatus = $doc->statusForUser(auth()->user());
                            $statusLabel = is_array($userStatus) ? ($userStatus['label'] ?? '') : (string) $userStatus;
                            if (empty($statusLabel)) $statusLabel = 'Unknown';
                            $otherRecipients = $doc->recipients->where('id', '!=', $doc->penerima_id);
                        @endphp
                        <tr class="group hover:bg-slate-50/60 transition duration-150">
                            {{-- No Dokumen: wrap & max-width agar turun ke bawah jika panjang --}}
                            <td class="px-4 py-3 font-mono text-sm font-semibold text-slate-700 break-words max-w-[150px]">
                                {{ $doc->nomor_dokumen }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-slate-800 break-words max-w-xs">
                                    {{ $doc->judul }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-user text-blue-600 text-xs"></i>
                                    </div>
                                    <span class="text-sm text-slate-700 truncate max-w-[100px]">{{ $doc->pengirim->name ?? '-' }}</span>
                                </div>
                            </td>
                            @if(auth()->user()->isSuperadminTrack())
                                <td class="px-4 py-3">
                                    <span class="text-xs bg-gray-100 px-2 py-1 rounded-full truncate max-w-[120px] block">
                                        {{ $doc->pengirim->company_name ?? '-' }}
                                    </span>
                                </td>
                            @endif
                            {{-- Tgl Kirim: tanpa whitespace-nowrap --}}
                            <td class="px-4 py-3 text-sm text-slate-500">
                                {{ $doc->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-user-check text-emerald-600 text-xs"></i>
                                    </div>
                                    <div class="text-sm">
                                        <span class="text-slate-700 truncate max-w-[80px] inline-block">{{ $doc->penerima->name ?? '-' }}</span>
                                        @if($otherRecipients->count() > 0)
                                            <span class="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full ml-1 cursor-help"
                                                  title="{{ $otherRecipients->pluck('name')->implode(', ') }}">
                                                +{{ $otherRecipients->count() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border shadow-sm {{ statusColorClass($statusLabel) }} whitespace-nowrap">
                                    <i class="{{ statusIcon($statusLabel) }} text-[11px]"></i>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            {{-- Update: tanpa whitespace-nowrap --}}
                            <td class="px-4 py-3 text-sm text-slate-500">
                                {{ $doc->updated_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('track-r.show', $doc->id) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-blue-100 hover:text-blue-700 transition-all whitespace-nowrap">
                                    <i class="fas fa-eye text-xs"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperadminTrack() ? 9 : 8 }}" class="text-center py-20 text-slate-500">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-folder-open text-5xl text-slate-300"></i>
                                    <p class="font-medium">Tidak ada dokumen</p>
                                    <p class="text-xs text-slate-400">Coba ubah filter atau kirim dokumen baru</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MOBILE CARDS --}}
    <div class="block sm:hidden space-y-4">
        @forelse($documents as $doc)
            @php
                $userStatus = $doc->statusForUser(auth()->user());
                $statusLabel = is_array($userStatus) ? ($userStatus['label'] ?? '') : (string) $userStatus;
                if (empty($statusLabel)) $statusLabel = 'Unknown';
                $otherRecipients = $doc->recipients->where('id', '!=', $doc->penerima_id);
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-lg p-5 transition hover:shadow-xl hover:border-blue-200">
                <div class="flex justify-between items-start mb-3">
                    <div class="font-mono text-sm font-bold bg-slate-100 px-2 py-1 rounded-lg text-slate-700">
                        {{ $doc->nomor_dokumen }}
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border shadow-sm {{ statusColorClass($statusLabel) }}">
                        <i class="{{ statusIcon($statusLabel) }} text-[11px]"></i>
                        {{ $statusLabel }}
                    </span>
                </div>
                <h3 class="text-base font-semibold text-slate-800 mb-3 leading-tight">{{ $doc->judul }}</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user text-blue-600 text-xs"></i>
                        </div>
                        <span class="text-slate-700">{{ $doc->pengirim->name ?? '-' }}</span>
                    </div>
                    @if(auth()->user()->isSuperadminTrack())
                        <div class="flex items-center gap-2">
                            <i class="fas fa-building text-gray-400 text-xs w-5"></i>
                            <span class="text-xs text-gray-600">Unit Bisnis: {{ $doc->pengirim->company_name ?? '-' }}</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center">
                            <i class="fas fa-user-check text-emerald-600 text-xs"></i>
                        </div>
                        <span class="text-slate-700">{{ $doc->penerima->name ?? '-' }}</span>
                        @if($otherRecipients->count() > 0)
                            <span class="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                                +{{ $otherRecipients->count() }}
                            </span>
                        @endif
                    </div>
                    <div class="flex gap-3 text-slate-500 text-xs pt-1">
                        <span><i class="far fa-calendar-alt mr-1"></i> {{ $doc->created_at->format('d/m/Y H:i') }}</span>
                        <span><i class="far fa-clock mr-1"></i> Update: {{ $doc->updated_at->format('H:i') }}</span>
                    </div>
                </div>
                <div class="flex justify-end mt-4">
                    <a href="{{ route('track-r.show', $doc->id) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-blue-100 hover:text-blue-700 transition-all">
                        <i class="fas fa-eye"></i> Detail
                    </a>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 shadow-lg p-10 text-center text-slate-500">
                <i class="fas fa-inbox text-4xl mb-3 text-slate-300"></i>
                <p>Tidak ada dokumen ditemukan</p>
            </div>
        @endforelse
    </div>

    {{-- PAGINATION --}}
    @if($documents->hasPages())
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
            <div class="text-sm text-slate-500 bg-slate-50/50 px-4 py-2 rounded-full self-start">
                <i class="fas fa-file-alt mr-1.5 text-slate-400"></i>
                Menampilkan {{ $documents->firstItem() }} – {{ $documents->lastItem() }} dari {{ $documents->total() }} dokumen
            </div>
            <div class="pagination-modern">
                {{ $documents->links() }}
            </div>
        </div>
    @elseif($documents->count() > 0)
        <div class="text-center text-sm text-slate-500 py-3 bg-slate-50 rounded-full">
            <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Total {{ $documents->count() }} dokumen
        </div>
    @endif
</div>

<style>
    /* Custom pagination modern */
    .pagination-modern nav div,
    .pagination-modern nav[role="navigation"] {
        display: flex;
        justify-content: center;
    }
    .pagination-modern .flex.items-center.justify-between {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .pagination-modern .relative.z-0.inline-flex {
        box-shadow: none;
        gap: 0.5rem;
    }
    .pagination-modern .relative.inline-flex.items-center {
        @apply px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-all;
    }
    .pagination-modern span.relative.inline-flex.items-center {
        @apply border-blue-200 bg-blue-50 text-blue-700 font-semibold;
    }
    .pagination-modern .relative.inline-flex.items-center svg {
        @apply w-4 h-4;
    }
    @media (max-width: 640px) {
        .pagination-modern .relative.z-0.inline-flex {
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.4rem;
        }
    }
</style>
@endsection