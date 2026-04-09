{{-- resources/views/help/tiket/index.blade.php --}}
@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Help Desk Tickets</h2>
            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full
                         text-xs font-semibold bg-blue-100 text-blue-800">
                Personal Requests Only
            </span>
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('help.tiket.create') }}"
               class="flex-1 sm:flex-none inline-flex items-center justify-center
                      px-4 py-2 bg-blue-600 text-white rounded-lg
                      text-sm font-semibold hover:bg-blue-700 transition">
                + New Request 
            </a>

            <button id="toggleFilterBtn"
                class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700
                       rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
        </div>
    </div>

    {{-- FILTER --}}
    <div id="filterSection" class="bg-white border rounded-xl p-4 hidden">
        <form method="GET" action="{{ route('help.tiket.index') }}"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Judul atau nomor tiket"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="OPEN" {{ request('status')=='OPEN'?'selected':'' }}>OPEN</option>
                    <option value="ON_PROCESS" {{ request('status')=='ON_PROCESS'?'selected':'' }}>ON PROCESS</option>
                    <option value="WAITING" {{ request('status')=='WAITING'?'selected':'' }}>WAITING</option>
                    <option value="DONE" {{ request('status')=='DONE'?'selected':'' }}>DONE</option>
                    <option value="CLOSED" {{ request('status')=='CLOSED'?'selected':'' }}>CLOSED</option>
                    <option value="HELP_GA_CORP" {{ request('status')=='HELP_GA_CORP'?'selected':'' }}>HELP GA CORP</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Prioritas</label>
                <select name="prioritas"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">All Priority</option>
                    <option value="LOW" {{ request('prioritas')=='LOW'?'selected':'' }}>LOW</option>
                    <option value="MEDIUM" {{ request('prioritas')=='MEDIUM'?'selected':'' }}>MEDIUM</option>
                    <option value="HIGH" {{ request('prioritas')=='HIGH'?'selected':'' }}>HIGH</option>
                    <option value="URGENT" {{ request('prioritas')=='URGENT'?'selected':'' }}>URGENT</option>
                </select>
            </div>

            <div class="lg:col-span-3 flex flex-col sm:flex-row gap-2 justify-end">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">
                    Apply
                </button>
                <a href="{{ route('help.tiket.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">No. Tiket</th>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Prioritas</th>
                    <th class="px-4 py-3 text-left hidden sm:table-cell">Tanggal</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($tiket as $item)
                <tr class="hover:bg-gray-50">
                    {{-- No. Tiket --}}
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $item->nomor_tiket }}</div>
                        <div class="text-xs text-gray-500 sm:hidden">
                            {{ $item->created_at->format('d/m/Y') }}
                        </div>
                    </td>

                    {{-- Judul --}}
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ Str::limit($item->judul, 40) }}</div>
                        @if($item->deskripsi)
                        <div class="text-xs text-gray-500 sm:hidden mt-1">
                            {{ Str::limit(strip_tags($item->deskripsi), 30) }}
                        </div>
                        @endif
                    </td>

                    {{-- Kategori --}}
                    <td class="px-4 py-3 hidden md:table-cell capitalize">
                        {{ $item->kategori->nama ?? '-' }}
                    </td>

                    {{-- Status --}}
                    <td class="px-4 py-3">
                        @php
                            $statusColors = [
                                'OPEN' => 'bg-yellow-100 text-yellow-800',
                                'ON_PROCESS' => 'bg-blue-100 text-blue-800',
                                'WAITING' => 'bg-orange-100 text-orange-800',
                                'DONE' => 'bg-green-100 text-green-800',
                                'CLOSED' => 'bg-gray-100 text-gray-800',
                                'HELP_GA_CORP' => 'bg-purple-100 text-purple-800',
                            ];
                            $statusLabels = [
                                'OPEN' => 'OPEN',
                                'ON_PROCESS' => 'ON PROCESS',
                                'WAITING' => 'WAITING',
                                'DONE' => 'DONE',
                                'CLOSED' => 'CLOSED',
                                'HELP_GA_CORP' => 'HELP GA CORP',
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$item->status] }}">
                            {{ $statusLabels[$item->status] ?? $item->status }}
                        </span>
                    </td>

                    {{-- Prioritas --}}
                    <td class="px-4 py-3 hidden lg:table-cell">
                        @php
                            $priorityColors = [
                                'URGENT' => 'bg-red-100 text-red-800',
                                'HIGH' => 'bg-orange-100 text-orange-800',
                                'MEDIUM' => 'bg-blue-100 text-blue-800',
                                'LOW' => 'bg-gray-100 text-gray-800'
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $priorityColors[$item->prioritas] }}">
                            {{ $item->prioritas }}
                        </span>
                    </td>

                    {{-- Tanggal --}}
                    <td class="px-4 py-3 hidden sm:table-cell">
                        {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }}
                    </td>

                    {{-- Action --}}
                    <td class="px-4 py-3">
                        <a href="{{ route('help.tiket.show', $item) }}"
                           class="text-blue-600 font-semibold hover:underline">
                            Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-10 text-center text-gray-500">
                        @if(request()->hasAny(['search', 'status', 'prioritas']))
                        Data tidak ditemukan
                        @else
                        Belum ada tiket
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($tiket->hasPages())
        <div class="mt-4">
            {{ $tiket->links() }}
        </div>
    @endif
</div>

{{-- SCRIPT --}}
<script>
document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden')
})
</script>
@endsection