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
               class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
                <i class="fas fa-plus mr-1.5"></i> Kirim Dokumen
            </a>
            <a href="{{ route('track-r.export', request()->query()) }}"
               class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2.5 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 transition shadow-sm">
                <i class="fas fa-download mr-1.5"></i> CSV
            </a>
        </div>
    </div>

    {{-- FILTER & PENCARIAN --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nomor atau judul..."
                       class="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="flex gap-2 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Dari</label>
                    <input type="date" name="from" value="{{ request('from') }}"
                           class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Sampai</label>
                    <input type="date" name="to" value="{{ request('to') }}"
                           class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select name="status" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="dikirim" @selected(request('status') == 'dikirim')>Dikirim</option>
                    <option value="diterima" @selected(request('status') == 'diterima')>Diterima</option>
                    <option value="ditolak" @selected(request('status') == 'ditolak')>Ditolak</option>
                    <option value="diteruskan" @selected(request('status') == 'diteruskan')>Diteruskan</option>
                </select>
            </div>

            <div class="flex gap-2 items-end">
                <button type="submit"
                        class="px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
                    <i class="fas fa-filter mr-1"></i> Terapkan
                </button>
                @if(request('search') || request('status') || request('from') || request('to'))
                <a href="{{ route('track-r.index') }}"
                   class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center">
                    <i class="fas fa-times mr-1"></i> Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ========== DESKTOP TABLE ========== --}}
    <div class="hidden sm:block bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">
                        <th class="px-5 py-3.5 text-left">No Dokumen</th>
                        <th class="px-5 py-3.5 text-left">Judul</th>
                        <th class="px-5 py-3.5 text-left">Pengirim</th>
                        <th class="px-5 py-3.5 text-left">Tgl Kirim</th>
                        <th class="px-5 py-3.5 text-left">Penerima</th>
                        <th class="px-5 py-3.5 text-left">Status</th>
                        <th class="px-5 py-3.5 text-left">Update</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($documents as $doc)
                        @php
                            $userStatus = $doc->statusForUser(auth()->user());
                            $otherRecipients = $doc->recipients->where('id', '!=', $doc->penerima_id);
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition">
                            {{-- No Dokumen --}}
                            <td class="px-5 py-4 font-mono font-semibold text-gray-800 whitespace-nowrap">
                                {{ $doc->nomor_dokumen }}
                            </td>
                            {{-- Judul --}}
                            <td class="px-5 py-4 max-w-[220px]">
                                <div class="font-medium text-gray-700 truncate" title="{{ $doc->judul }}">
                                    {{ $doc->judul }}
                                </div>
                            </td>
                            {{-- Pengirim --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                        <i class="fas fa-user text-blue-600 text-xs"></i>
                                    </div>
                                    <span class="text-gray-700">{{ $doc->pengirim->name ?? '-' }}</span>
                                </div>
                            </td>
                            {{-- Tgl Kirim --}}
                            <td class="px-5 py-4 text-xs text-gray-600 whitespace-nowrap">
                                {{ $doc->created_at->format('d/m/Y H:i') }}
                            </td>
                            {{-- Penerima --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                                        <i class="fas fa-user-check text-green-600 text-xs"></i>
                                    </div>
                                    <div class="leading-tight">
                                        <span class="text-gray-700">{{ $doc->penerima->name ?? '-' }}</span>
                                        @if($otherRecipients->count() > 0)
                                            <span class="text-xs text-gray-500 ml-1 bg-gray-100 px-1.5 py-0.5 rounded-full cursor-default"
                                                  title="{{ $otherRecipients->pluck('name')->implode(', ') }}">
                                                +{{ $otherRecipients->count() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            {{-- Status --}}
                            <td class="px-5 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold border {{ $userStatus['color'] }}">
                                    {{ $userStatus['label'] }}
                                </span>
                            </td>
                            {{-- Update --}}
                            <td class="px-5 py-4 text-xs text-gray-600 whitespace-nowrap">
                                {{ $doc->updated_at->format('d/m/Y H:i') }}
                            </td>
                            {{-- Aksi --}}
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('track-r.show', $doc->id) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-xs font-semibold hover:bg-blue-100 transition">
                                    <i class="fas fa-eye text-xs"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-16 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-3 text-gray-300 block"></i>
                                <p class="font-medium">Tidak ada dokumen ditemukan</p>
                                <p class="text-xs text-gray-400 mt-1">Coba ubah filter atau kirim dokumen baru.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========== MOBILE LIST ========== --}}
    <div class="block sm:hidden space-y-2">
        @forelse($documents as $doc)
            @php
                $userStatus = $doc->statusForUser(auth()->user());
                $otherRecipients = $doc->recipients->where('id', '!=', $doc->penerima_id);
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:border-blue-300 transition space-y-2">
                {{-- No Dokumen + Status (inline) --}}
                <div class="flex justify-between items-start">
                    <div class="font-mono font-bold text-gray-800">
                        {{ $doc->nomor_dokumen }}
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border {{ $userStatus['color'] }}">
                        {{ $userStatus['label'] }}
                    </span>
                </div>

                {{-- Judul --}}
                <div class="font-medium text-gray-800 text-sm leading-snug">
                    {{ $doc->judul }}
                </div>

                {{-- Pengirim --}}
                <div class="flex items-center gap-2 text-xs">
                    <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-user text-blue-600 text-xs"></i>
                    </div>
                    <span class="text-gray-700 font-medium">{{ $doc->pengirim->name ?? '-' }}</span>
                </div>

                {{-- Tgl Kirim + Update --}}
                <div class="flex gap-3 text-xs text-gray-500">
                    <span><i class="far fa-calendar-alt mr-1"></i> {{ $doc->created_at->format('d/m/Y H:i') }}</span>
                    <span><i class="far fa-clock mr-1"></i> {{ $doc->updated_at->format('H:i') }}</span>
                </div>

                {{-- Penerima --}}
                <div class="flex items-center gap-2 text-xs">
                    <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-user-check text-green-600 text-xs"></i>
                    </div>
                    <span class="text-gray-700">{{ $doc->penerima->name ?? '-' }}</span>
                    @if($otherRecipients->count() > 0)
                        <span class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded-full">
                            +{{ $otherRecipients->count() }}
                        </span>
                    @endif
                </div>

                {{-- Tombol Detail --}}
                <div class="flex justify-end pt-1">
                    <a href="{{ route('track-r.show', $doc->id) }}"
                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-xs font-semibold hover:bg-blue-100 transition">
                        <i class="fas fa-eye"></i> Detail
                    </a>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center text-gray-500">
                <i class="fas fa-inbox text-3xl mb-2 text-gray-300 block"></i>
                <p>Tidak ada dokumen ditemukan</p>
            </div>
        @endforelse
    </div>

    {{-- PAGINATION --}}
    @if($documents->hasPages())
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm text-gray-600">
        <div>Menampilkan {{ $documents->firstItem() }} – {{ $documents->lastItem() }} dari {{ $documents->total() }} dokumen</div>
        <div class="mt-2 sm:mt-0">{{ $documents->links() }}</div>
    </div>
    @elseif($documents->count() > 0)
    <div class="text-center text-sm text-gray-500 py-1">Total {{ $documents->count() }} dokumen</div>
    @endif
</div>
@endsection