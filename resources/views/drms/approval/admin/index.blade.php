@extends('layouts.app_car_sidebar')

@section('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endsection

@section('content')
<style>[x-cloak] { display: none !important; }</style>
@php
    // Tab Dipantau (request BU sendiri yang di-forward ke BU lain) dipilih lewat ?tab=monitor,
    // tab status lainnya lewat ?status=...
    $isMonitor = request('tab') === 'monitor' && (($forwardedSummary['total'] ?? 0) > 0);

    $statusLabels = [
        'pending_l1'     => 'Pending L1',
        'approved_l1'    => 'Pending Admin',
        'approved_admin' => 'Disetujui',
        'completed'      => 'Selesai',
        'rejected_l1'    => 'Ditolak L1',
        'rejected_admin' => 'Ditolak Admin',
        'cancelled'      => 'Dibatalkan',
    ];
    $statusColors = [
        'pending_l1'     => 'bg-yellow-100 text-yellow-800',
        'approved_l1'    => 'bg-blue-100 text-blue-800',
        'approved_admin' => 'bg-green-100 text-green-800',
        'completed'      => 'bg-gray-100 text-gray-800',
        'rejected_l1'    => 'bg-red-100 text-red-800',
        'rejected_admin' => 'bg-red-100 text-red-800',
        'cancelled'      => 'bg-gray-200 text-gray-600',
    ];
    // Kolom "Jenis" (transport_type) baru terisi setelah admin memproses request,
    // jadi hanya ditampilkan di tab Disetujui dan Selesai.
    $showJenis = in_array($activeStatus, ['disetujui', 'selesai'], true);

    // Warna badge jumlah per tab.
    $tabBadge = [
        'pending_l1'    => 'bg-yellow-100 text-yellow-800',
        'pending_admin' => 'bg-blue-100 text-blue-800',
        'disetujui'     => 'bg-green-100 text-green-800',
        'selesai'       => 'bg-gray-200 text-gray-700',
        'ditolak'       => 'bg-red-100 text-red-700',
        'dibatalkan'    => 'bg-gray-200 text-gray-600',
    ];
    $tabHints = [
        'pending_l1'    => 'Menunggu persetujuan atasan (L1). Hanya bisa dilihat, belum perlu tindakan admin.',
        'pending_admin' => 'Menunggu tindakan admin GA: Proses, Tolak, atau Forward.',
        'disetujui'     => 'Sudah disetujui admin dan belum selesai. Bisa Selesaikan, Ganti Driver, atau kembalikan ke Pending.',
        'selesai'       => 'Perjalanan yang sudah diselesaikan.',
        'ditolak'       => 'Ditolak oleh atasan (L1) atau admin GA. Hanya bisa dilihat.',
        'dibatalkan'    => 'Dibatalkan oleh pemohon. Hanya bisa dilihat.',
    ];

    // Data untuk modal Detail (dipakai semua baris, satu definisi saja).
    $buildDetail = function ($req) {
        $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : null;
        $fmtDateTime = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y H:i') : null;
        return [
            'request_no' => $req->request_no,
            'requester' => ['name' => optional($req->requester)->name],
            'created_at' => $fmtDateTime($req->created_at),
            'trip_type' => $req->trip_type,
            'distance_type' => $req->distance_type,
            'usage_date' => $fmtDate($req->usage_date),
            'start_time' => $req->start_time,
            'return_date' => $fmtDate($req->return_date),
            'end_time' => $req->end_time,
            'pickup_location' => $req->pickup_location,
            'pickup_maps_link' => $req->pickup_maps_link,
            'destination' => $req->destination,
            'destination_maps_link' => $req->destination_maps_link,
            'purpose' => $req->purpose,
            'attachments' => $req->attachmentsForJs(),
            'status' => $req->status,
            'approver_l1' => $req->approverL1 ? ['name' => $req->approverL1->name, 'approved_l1_at' => $fmtDateTime($req->approved_l1_at)] : null,
            'admin' => $req->admin ? ['name' => $req->admin->name, 'approved_admin_at' => $fmtDateTime($req->approved_admin_at)] : null,
            'rejection_reason' => $req->rejection_reason,
            'transport_type' => $req->transport_type,
            'driver' => $req->driver ? ['name' => $req->driver->name, 'phone' => $req->driver->phone] : null,
            'vehicle' => $req->vehicle ? ['type' => $req->vehicle->type, 'plate_number' => $req->vehicle->plate_number] : null,
            'vouchers' => $req->vouchers->map(fn ($v) => ['code' => $v->code, 'type' => $v->type, 'nominal' => $v->nominal, 'beban_bu' => optional($v->inputBusinessUnit)->nama_bisnis_unit])->values(),
            'merged_into' => $req->merged_into_id ? ['request_no' => optional($req->mergedInto)->request_no] : null,
            'completed_at' => $fmtDateTime($req->completed_at),
        ];
    };
@endphp

<div class="space-y-4 md:space-y-6 text-sm text-gray-800 font-sans max-w-full overflow-x-hidden" x-data="approvalAdminModal()">
    {{-- Notifikasi hasil aksi (Tolak / Forward / Ganti Driver / Selesaikan) --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Approval Admin</h1>
            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                Persetujuan GA{{ $isSuper ? ' · Superadmin (semua BU)' : '' }}
            </span>
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
            @if($canExport && !$isMonitor)
            <a href="{{ route('drms.approval.admin.export', request()->query()) }}"
               class="flex-1 sm:flex-none px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 transition text-center">
                📥 Export Excel
            </a>
            @endif
            @unless($isMonitor)
            <button type="button" @click="filterOpen = !filterOpen" class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
            @endunless
        </div>
    </div>

    {{-- Filter Section (berlaku untuk semua tab status) --}}
    @unless($isMonitor)
    <div id="filterSection" x-show="filterOpen" x-cloak class="bg-white border rounded-xl p-4">
        <form method="GET" action="{{ route('drms.approval.admin.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Tab status yang sedang dibuka ikut terbawa saat filter di-apply. --}}
            <input type="hidden" name="status" value="{{ $activeStatus }}">
            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no. request / pemohon / tujuan" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Tanggal Dari</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Tanggal Sampai</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            @if($isSuper)
            {{-- Filter BU hanya untuk superadmin (di server, parameter bu dari admin biasa diabaikan). --}}
            <div>
                <label class="text-sm font-medium text-gray-600">Business Unit</label>
                <select name="bu" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Semua BU</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ (string) request('bu') === (string) $bu->id_bisnis_unit ? 'selected' : '' }}>{{ $bu->nama_bisnis_unit }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="sm:col-span-2 lg:col-span-4 flex flex-col sm:flex-row gap-2 justify-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">Apply</button>
                <a href="{{ route('drms.approval.admin.index', ['status' => $activeStatus]) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 text-center">Reset</a>
            </div>
        </form>
    </div>
    @endunless

    {{-- TAB STATUS (link biasa, server-side, filter ikut terbawa).
         HP: grid 3 kolom tanpa geser samping. Desktop: tab garis bawah. --}}
    <div class="grid grid-cols-3 gap-2 md:flex md:gap-1 md:border-b md:border-gray-200">
        @foreach($statusTabs as $key => $tab)
            @php $isActive = !$isMonitor && $activeStatus === $key; @endphp
            <a href="{{ route('drms.approval.admin.index', array_merge(request()->except(['status', 'page', 'tab', 'monitor_page']), ['status' => $key])) }}"
               class="flex flex-col items-center justify-center gap-1 rounded-lg border px-2 py-2 text-xs font-semibold text-center transition
                      md:flex-row md:gap-0 md:rounded-none md:border-0 md:border-b-2 md:px-4 md:py-2.5 md:text-sm md:-mb-px
                      {{ $isActive ? 'bg-blue-600 border-blue-600 text-white md:bg-transparent md:text-blue-600' : 'bg-white border-gray-200 text-gray-600 md:bg-transparent md:border-transparent md:text-gray-500 md:hover:text-gray-700' }}">
                <span>{{ $tab['label'] }}</span>
                <span class="md:ml-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $tabCounts[$key] > 0 ? ($tabBadge[$key] ?? 'bg-gray-100 text-gray-600') : 'bg-gray-100 text-gray-400' }}">{{ $tabCounts[$key] }}</span>
            </a>
        @endforeach
        @if(($forwardedSummary['total'] ?? 0) > 0)
            <a href="{{ route('drms.approval.admin.index', ['tab' => 'monitor']) }}"
               class="flex flex-col items-center justify-center gap-1 rounded-lg border px-2 py-2 text-xs font-semibold text-center transition
                      md:flex-row md:gap-0 md:rounded-none md:border-0 md:border-b-2 md:px-4 md:py-2.5 md:text-sm md:-mb-px
                      {{ $isMonitor ? 'bg-blue-600 border-blue-600 text-white md:bg-transparent md:text-blue-600' : 'bg-white border-gray-200 text-gray-600 md:bg-transparent md:border-transparent md:text-gray-500 md:hover:text-gray-700' }}">
                <span>🔀 Dipantau<span class="hidden md:inline"> (Dialihkan ke BU Lain)</span></span>
                <span class="md:ml-1 px-2 py-0.5 rounded-full text-xs {{ ($forwardedSummary['stale'] ?? 0) > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' }}">{{ $forwardedSummary['active'] ?? 0 }}</span>
            </a>
        @endif
    </div>

    {{-- ==================== DAFTAR REQUEST PER STATUS ==================== --}}
    @unless($isMonitor)
    <div class="bg-white border rounded-xl overflow-hidden shadow-sm">
        <div class="bg-gray-50 px-6 py-3 border-b">
            <h2 class="font-semibold text-gray-700">{{ $statusTabs[$activeStatus]['label'] }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ $tabHints[$activeStatus] ?? '' }}</p>
            @if($monthScopeLabel)
                <p class="text-xs text-blue-600 mt-0.5">📅 Menampilkan bulan {{ $monthScopeLabel }}. Isi filter Tanggal untuk periode lain.</p>
            @endif
        </div>

        {{-- Desktop / tablet: tabel --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">No. Request</th>
                        <th class="px-4 py-3 text-left">Pemohon</th>
                        <th class="px-4 py-3 text-left hidden md:table-cell">BU</th>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Perjalanan</th>
                        @if($showJenis)
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Jenis</th>
                        @endif
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($requests as $req)
                    @php
                        $detailData = $buildDetail($req);
                        $startDateTime = \Carbon\Carbon::parse($req->usage_date)->format('d/m/Y') . ' ' . $req->start_time;
                        $returnDate = $req->return_date ? \Carbon\Carbon::parse($req->return_date)->format('d/m/Y') . ' ' . $req->end_time : null;
                        $buName = optional($req->currentBusinessUnit)->nama_bisnis_unit
                            ?? optional(optional(optional($req->requester)->drmsProfile)->businessUnit)->nama_bisnis_unit
                            ?? '-';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono">{{ $req->request_no }}</td>
                        <td class="px-4 py-3">{{ optional($req->requester)->name ?? '-' }}</td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <span class="px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-800">{{ $buName }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($req->trip_type === 'round_trip')
                                {{ $startDateTime }}
                                @if($returnDate)
                                    <br><span class="text-xs text-gray-500">kembali: {{ $returnDate }}</span>
                                @endif
                            @else
                                {{ $startDateTime . ' - ' . $req->end_time }}
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">{{ $req->pickup_location }} → {{ $req->destination }}</td>
                        @if($showJenis)
                        <td class="px-4 py-3 hidden lg:table-cell">
                            {{ $req->transport_type ? ucfirst(str_replace('_', ' ', $req->transport_type)) : '-' }}
                            @if($req->merged_into_id)
                                <span class="block text-xs text-purple-600 font-semibold mt-0.5">🔗 Gabung ke {{ optional($req->mergedInto)->request_no ?? ('#'.$req->merged_into_id) }}</span>
                            @endif
                        </td>
                        @endif
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$req->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$req->status] ?? $req->status }}
                            </span>
                            @if($req->status === 'completed' && $req->completed_at)
                                <div class="text-[11px] text-gray-500 mt-1">{{ \Carbon\Carbon::parse($req->completed_at)->format('d/m/Y H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @include('drms.approval.admin._row_actions', ['req' => $req, 'detailData' => $detailData])
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $showJenis ? 8 : 7 }}" class="px-4 py-10 text-center text-gray-500">Tidak ada permintaan pada tab ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- HP: kartu per request --}}
        <div class="md:hidden divide-y">
            @forelse($requests as $req)
            @php
                $detailData = $buildDetail($req);
                $startDateTime = \Carbon\Carbon::parse($req->usage_date)->format('d/m/Y') . ' ' . $req->start_time;
                $returnDate = $req->return_date ? \Carbon\Carbon::parse($req->return_date)->format('d/m/Y') . ' ' . $req->end_time : null;
                $buName = optional($req->currentBusinessUnit)->nama_bisnis_unit
                    ?? optional(optional(optional($req->requester)->drmsProfile)->businessUnit)->nama_bisnis_unit
                    ?? '-';
            @endphp
            <div class="p-4 space-y-2 break-words min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <span class="font-mono font-semibold text-gray-800 break-all">{{ $req->request_no }}</span>
                    <span class="shrink-0 px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$req->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $statusLabels[$req->status] ?? $req->status }}
                    </span>
                </div>
                <div class="text-gray-700">{{ optional($req->requester)->name ?? '-' }}</div>
                <div>
                    <span class="px-2 py-0.5 rounded-full text-xs bg-purple-100 text-purple-800">{{ $buName }}</span>
                </div>
                <div class="text-xs text-gray-600">
                    📅
                    @if($req->trip_type === 'round_trip')
                        {{ $startDateTime }}@if($returnDate) &middot; kembali {{ $returnDate }}@endif
                    @else
                        {{ $startDateTime . ' - ' . $req->end_time }}
                    @endif
                </div>
                <div class="text-xs text-gray-600">📍 {{ $req->pickup_location }} → {{ $req->destination }}</div>
                @if($showJenis)
                    <div class="text-xs text-gray-600">
                        🚗 {{ $req->transport_type ? ucfirst(str_replace('_', ' ', $req->transport_type)) : '-' }}
                        @if($req->merged_into_id)
                            <span class="text-purple-600 font-semibold">&middot; 🔗 Gabung ke {{ optional($req->mergedInto)->request_no ?? ('#'.$req->merged_into_id) }}</span>
                        @endif
                    </div>
                @endif
                @if($req->status === 'completed' && $req->completed_at)
                    <div class="text-[11px] text-gray-500">Selesai {{ \Carbon\Carbon::parse($req->completed_at)->format('d/m/Y H:i') }}</div>
                @endif
                <div class="pt-1">
                    @include('drms.approval.admin._row_actions', ['req' => $req, 'detailData' => $detailData])
                </div>
            </div>
            @empty
            <div class="px-4 py-10 text-center text-gray-500">Tidak ada permintaan pada tab ini.</div>
            @endforelse
        </div>

        @if($requests->hasPages())
        <div class="px-6 py-3 border-t">
            {{ $requests->links() }}
        </div>
        @endif
    </div>
    @endunless

    {{-- ==================== DIPANTAU: REQUEST BU SENDIRI YANG DI-FORWARD KE BU LAIN (READ-ONLY) ==================== --}}
    @if($isMonitor)
    <div class="bg-white border rounded-xl overflow-hidden shadow-sm" x-data="{ showResolved: {{ request('monitor_page') ? 'true' : 'false' }} }">
        <div class="bg-gray-50 px-6 py-3 border-b">
            <h2 class="font-semibold text-gray-700">🔀 Request BU Anda yang Dialihkan ke BU Lain</h2>
            <p class="text-xs text-gray-400 mt-0.5">Ini permintaan dari user BU Anda yang admin-nya alihkan ke Business Unit lain (karena BU sendiri gak ada mobil, dsb). Read-only — cuma buat pantau statusnya, aksi (setujui/tolak/dsb) tetap di tangan BU yang menangani.</p>
        </div>

        {{-- Ringkasan singkat, biar gak perlu scroll buat tau gambaran umum --}}
        <div class="px-6 py-3 border-b bg-white flex flex-wrap gap-2 text-xs">
            <span class="px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-800 font-medium">⏳ {{ $forwardedSummary['active'] }} masih diproses BU tujuan</span>
            @if($forwardedSummary['stale'] > 0)
                <span class="px-2.5 py-1 rounded-full bg-red-100 text-red-700 font-medium">⚠️ {{ $forwardedSummary['stale'] }} sudah ≥2 hari belum ditindak</span>
            @endif
            <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 font-medium">📦 {{ $forwardedSummary['resolved'] }} sudah selesai (riwayat)</span>
        </div>

        {{-- ===== BAGIAN 1: MASIH PERLU DIPANTAU (approved_l1, belum ditindak BU tujuan) ===== --}}
        {{-- Ini yang paling penting, jadi selalu tampil semua tanpa pagination -- jumlahnya
             biasanya kecil karena begitu BU tujuan menindak, otomatis pindah ke bagian "Riwayat". --}}
        @if($forwardedAwayRequests->isEmpty())
            <p class="px-6 py-6 text-center text-gray-500 text-sm">🎉 Tidak ada yang masih menunggu tindakan BU tujuan saat ini.</p>
        @else
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">No. Request</th>
                        <th class="px-4 py-3 text-left">Pemohon</th>
                        <th class="px-4 py-3 text-left">Perjalanan</th>
                        <th class="px-4 py-3 text-left">Dialihkan ke BU</th>
                        <th class="px-4 py-3 text-left">Menunggu Sejak</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($forwardedAwayRequests as $req)
                    <tr class="{{ $req->isStale ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-3 font-medium">{{ $req->request_no }}</td>
                        <td class="px-4 py-3">{{ $req->requester->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $req->pickup_location }} → {{ $req->destination }}</td>
                        <td class="px-4 py-3">{{ $req->currentBusinessUnit->nama_bisnis_unit ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-gray-500">{{ $req->forwarded_at ? $req->forwarded_at->format('d/m/Y H:i') : '-' }}</span>
                            @if($req->isStale)
                                <span class="block mt-1 text-xs text-red-600 font-medium">⚠️ Sudah {{ $req->forwarded_at->diffInDays(now()) }} hari belum ditindak</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden divide-y">
            @foreach($forwardedAwayRequests as $req)
            <div class="p-4 {{ $req->isStale ? 'bg-red-50' : '' }}">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-semibold">{{ $req->request_no }}</p>
                        <p class="text-xs text-gray-500">{{ $req->requester->name ?? '-' }}</p>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">⏳ Menunggu</span>
                </div>
                <p class="text-xs text-gray-600 mt-2">{{ $req->pickup_location }} → {{ $req->destination }}</p>
                <p class="text-xs text-gray-500 mt-1">Dialihkan ke: <span class="font-medium">{{ $req->currentBusinessUnit->nama_bisnis_unit ?? '-' }}</span></p>
                <p class="text-xs text-gray-400 mt-1">Menunggu sejak: {{ $req->forwarded_at ? $req->forwarded_at->format('d/m/Y H:i') : '-' }}</p>
                @if($req->isStale)
                    <p class="text-xs text-red-600 font-medium mt-1">⚠️ Sudah {{ $req->forwarded_at->diffInDays(now()) }} hari belum ditindak BU tujuan</p>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- ===== BAGIAN 2: RIWAYAT YANG SUDAH SELESAI (collapsed by default + pagination) ===== --}}
        @if($forwardedSummary['resolved'] > 0)
        <div class="border-t">
            <button type="button" @click="showResolved = !showResolved"
                    class="w-full flex items-center justify-between px-6 py-3 text-sm font-medium text-gray-600 hover:bg-gray-50">
                <span>📦 Riwayat yang sudah selesai ({{ $forwardedSummary['resolved'] }})</span>
                <span x-text="showResolved ? '▲ Sembunyikan' : '▼ Tampilkan'"></span>
            </button>
            <div x-show="showResolved" x-cloak>
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">No. Request</th>
                                <th class="px-4 py-3 text-left">Pemohon</th>
                                <th class="px-4 py-3 text-left">Perjalanan</th>
                                <th class="px-4 py-3 text-left">Ditangani BU</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Terakhir Update</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($forwardedResolvedRequests as $req)
                            @php
                                $statusBadge = match($req->status) {
                                    'approved_admin' => ['bg-green-100 text-green-800', '✅ Disetujui BU Tujuan'],
                                    'rejected_admin' => ['bg-red-100 text-red-800', '❌ Ditolak BU Tujuan'],
                                    'completed' => ['bg-blue-100 text-blue-800', '🏁 Selesai'],
                                    default => ['bg-gray-100 text-gray-600', $req->status],
                                };
                                $lastUpdate = $req->approved_admin_at ?? $req->updated_at;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $req->request_no }}</td>
                                <td class="px-4 py-3">{{ $req->requester->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $req->pickup_location }} → {{ $req->destination }}</td>
                                <td class="px-4 py-3">{{ $req->currentBusinessUnit->nama_bisnis_unit ?? '-' }}</td>
                                <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span></td>
                                <td class="px-4 py-3 text-gray-500">{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->format('d/m/Y H:i') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="md:hidden divide-y">
                    @foreach($forwardedResolvedRequests as $req)
                    @php
                        $statusBadge = match($req->status) {
                            'approved_admin' => ['bg-green-100 text-green-800', '✅ Disetujui BU Tujuan'],
                            'rejected_admin' => ['bg-red-100 text-red-800', '❌ Ditolak BU Tujuan'],
                            'completed' => ['bg-blue-100 text-blue-800', '🏁 Selesai'],
                            default => ['bg-gray-100 text-gray-600', $req->status],
                        };
                        $lastUpdate = $req->approved_admin_at ?? $req->updated_at;
                    @endphp
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold">{{ $req->request_no }}</p>
                                <p class="text-xs text-gray-500">{{ $req->requester->name ?? '-' }}</p>
                            </div>
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-2">{{ $req->pickup_location }} → {{ $req->destination }}</p>
                        <p class="text-xs text-gray-500 mt-1">Ditangani: <span class="font-medium">{{ $req->currentBusinessUnit->nama_bisnis_unit ?? '-' }}</span></p>
                        <p class="text-xs text-gray-400 mt-1">Update: {{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    @endforeach
                </div>

                @if($forwardedResolvedRequests->hasPages())
                <div class="px-6 py-3 border-t">
                    {{ $forwardedResolvedRequests->appends(array_merge(request()->query(), ['tab' => 'monitor']))->links() }}
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- MODAL REJECT --}}
    <div x-show="rejectModalOpen" x-cloak style="display: none;" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-4">Tolak Permintaan</h2>
            <form :action="`{{ url('drms/approval/admin') }}/${rejectRequestId}/reject`" method="POST">
                @csrf
                @method('PUT')
                <textarea name="rejection_reason" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Masukkan alasan penolakan..." required></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm">Tolak</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL FORWARD --}}
    <div x-show="forwardModalOpen" x-cloak style="display: none;" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-4">Alihkan ke Business Unit Lain</h2>
            <form :action="`{{ url('drms/approval/admin') }}/${forwardRequestId}/forward`" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Dibebankan ke BU</label>
                    <select name="target_business_unit_id" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                        <option value="">-- Pilih BU --</option>
                        @foreach($businessUnits as $bu)
                            <option value="{{ $bu->id_bisnis_unit }}">{{ $bu->nama_bisnis_unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                    <textarea name="note" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Tambahkan catatan untuk admin BU tujuan..."></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="forwardModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm">Forward</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL KEMBALIKAN KE PENDING --}}
    <div x-show="returnModalOpen" x-cloak style="display: none;" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-1">Pending Admin</h2>
            <p class="text-xs text-gray-500 mb-4">
                <span class="font-semibold font-mono" x-text="returnRequestNo"></span> akan kembali ke daftar Pending Admin.
                Driver, kendaraan, dan voucher yang sudah diberikan dilepas. Hanya bisa selama jam perjalanan belum berakhir.
            </p>
            <form :action="`{{ url('drms/approval/admin') }}/${returnRequestId}/return-to-pending`" method="POST">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Alasan <span class="text-red-500">*</span></label>
                <textarea name="note" rows="3" required maxlength="500" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Contoh: salah pilih driver, perlu diproses ulang"></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="returnModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-gray-700 text-white rounded-lg text-sm">Kembalikan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL GANTI DRIVER (BARU) --}}
    <div x-show="swapModalOpen" x-cloak style="display: none;" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-1">Ganti Driver &amp; Kendaraan</h2>
            <p class="text-xs text-gray-500 mb-4">
                Saat ini: <span class="font-semibold" x-text="swapCurrentDriver"></span>
                &middot; <span class="font-semibold" x-text="swapCurrentVehicle"></span>
            </p>
            <form :action="`{{ url('drms/approval/admin') }}/${swapRequestId}/swap-driver`" method="POST"
                  onsubmit="return validateSwapForm(this)">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Driver</label>
                    <select name="new_driver_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Tetap pakai driver sekarang (default) --</option>
                        @foreach($availableDrivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->name }} ({{ $driver->phone ?? '-' }}) - {{ ucfirst($driver->status) }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Biarkan kosong (default) kalau driver-nya tidak perlu diganti, hanya kendaraannya saja.</p>
                    @if($availableDrivers->isEmpty())
                        <p class="text-xs text-yellow-600 mt-1">Tidak ada driver pengganti tersedia.</p>
                    @endif
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kendaraan</label>
                    <select name="new_vehicle_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Tetap pakai kendaraan sekarang (default) --</option>
                        @foreach($availableVehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->type }} - {{ $vehicle->plate_number }} ({{ $vehicle->capacity }} kursi) - {{ ucfirst($vehicle->status) }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Biarkan kosong (default) kalau kendaraan tidak perlu diganti, hanya driver-nya saja.</p>
                    @if($availableVehicles->isEmpty())
                        <p class="text-xs text-yellow-600 mt-1">Tidak ada kendaraan pengganti tersedia.</p>
                    @endif
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alasan (opsional)</label>
                    <textarea name="reason" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Contoh: driver lama sakit mendadak"></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="swapModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DETAIL --}}
    <div x-show="detailModalOpen" x-cloak style="display: none;" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Detail Permintaan</h3>
            <table class="w-full text-sm border-collapse">
                <tbody>
                    <tr class="border-b border-gray-100"><td class="py-2 w-1/3 text-gray-500 font-medium">No. Request</td><td class="py-2 font-medium" x-text="detailItem.request_no"></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Pemohon</td><td class="py-2"><span x-text="detailItem.requester?.name ?? '-'"></span><span x-show="detailItem.created_at" class="text-gray-400 text-xs ml-1" x-text="'(' + detailItem.created_at + ')'"></span></td></tr>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Tipe Perjalanan</td><td class="py-2" x-text="detailItem.trip_type === 'round_trip' ? 'Pulang Pergi' : 'Sekali Jalan'"></td></tr>
                    <template x-if="detailItem.distance_type"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Jenis Dinas</td><td class="py-2" x-text="detailItem.distance_type === 'jarak_jauh' ? 'Dinas Jarak Jauh' : 'Dinas Jarak Dekat'"></td></tr></template>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Tanggal &amp; Jam</td>
                        <td class="py-2">
                            <template x-if="detailItem.trip_type === 'round_trip' && detailItem.return_date">
                                <span x-text="detailItem.usage_date + ' ' + detailItem.start_time + ' sampai ' + detailItem.return_date + ' ' + detailItem.end_time"></span>
                            </template>
                            <template x-if="detailItem.trip_type !== 'round_trip' || !detailItem.return_date">
                                <span x-text="detailItem.usage_date + ' ' + detailItem.start_time + ' - ' + detailItem.end_time"></span>
                            </template>
                        </td>
                    </tr>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Lokasi Penjemputan</td><td class="py-2" x-text="detailItem.pickup_location"></td></tr>
                    <template x-if="detailItem.pickup_maps_link"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Link Maps Penjemputan</td><td class="py-2"><a :href="detailItem.pickup_maps_link" target="_blank" class="text-blue-600 underline">Buka Maps</a></td></tr></template>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Tujuan</td><td class="py-2" x-text="detailItem.destination"></td></tr>
                    <template x-if="detailItem.destination_maps_link"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Link Maps Tujuan</td><td class="py-2"><a :href="detailItem.destination_maps_link" target="_blank" class="text-blue-600 underline">Buka Maps</a></td></tr></template>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Keperluan</td><td class="py-2" x-text="detailItem.purpose ?? '-'"></td></tr>
                    <template x-if="detailItem.attachments && detailItem.attachments.length > 0"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium align-top">Lampiran</td><td class="py-2">
                        <template x-for="a in detailItem.attachments" :key="a.url"><div class="mb-1"><a :href="a.url" target="_blank" rel="noopener" class="text-blue-600 underline" x-text="'📎 ' + a.name"></a></div></template>
                    </td></tr></template>
                    <tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Status</td>
                        <td class="py-2">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="{
                                'bg-yellow-100 text-yellow-800': detailItem.status === 'pending_l1',
                                'bg-blue-100 text-blue-800': detailItem.status === 'approved_l1',
                                'bg-red-100 text-red-800': detailItem.status === 'rejected_l1' || detailItem.status === 'rejected_admin',
                                'bg-green-100 text-green-800': detailItem.status === 'approved_admin',
                                'bg-gray-100 text-gray-800': detailItem.status === 'completed',
                                'bg-gray-200 text-gray-600': detailItem.status === 'cancelled'
                            }" x-text="{
                                'pending_l1': 'Pending L1',
                                'approved_l1': 'Disetujui Atasan',
                                'rejected_l1': 'Ditolak Atasan',
                                'approved_admin': 'Disetujui GA',
                                'rejected_admin': 'Ditolak GA',
                                'completed': 'Selesai',
                                'cancelled': 'Dibatalkan'
                            }[detailItem.status] || detailItem.status"></span>
                        </td>
                    </tr>
                    <template x-if="detailItem.approver_l1"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Proses L1</td><td class="py-2" x-text="detailItem.approver_l1.name + (detailItem.approver_l1.approved_l1_at ? ' (' + detailItem.approver_l1.approved_l1_at + ')' : '')"></td></tr></template>
                    <template x-if="detailItem.admin"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Proses GA</td><td class="py-2" x-text="detailItem.admin.name + (detailItem.admin.approved_admin_at ? ' (' + detailItem.admin.approved_admin_at + ')' : '')"></td></tr></template>
                    <template x-if="detailItem.status === 'completed' && detailItem.completed_at"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Waktu Selesai</td><td class="py-2 font-semibold text-blue-700" x-text="detailItem.completed_at"></td></tr></template>
                    <template x-if="detailItem.rejection_reason"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Catatan</td><td class="py-2 text-red-600" x-text="detailItem.rejection_reason"></td></tr></template>
                    <template x-if="detailItem.transport_type"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Jenis Transportasi</td><td class="py-2" x-text="detailItem.transport_type == 'company_driver' ? 'Driver Perusahaan' : (detailItem.transport_type == 'voucher' ? 'Voucher' : 'Rental')"></td></tr></template>
                    <template x-if="detailItem.driver"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Driver</td><td class="py-2" x-text="detailItem.driver.name + ' (' + (detailItem.driver.phone || '-') + ')'"></td></tr></template>
                    <template x-if="detailItem.vehicle"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Kendaraan</td><td class="py-2" x-text="detailItem.vehicle.type + ' - ' + detailItem.vehicle.plate_number"></td></tr></template>
                    <template x-if="detailItem.vouchers && detailItem.vouchers.length > 0"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium align-top">Voucher</td><td class="py-2">
                        <template x-for="v in detailItem.vouchers" :key="v.code">
                            <div class="mb-1">
                                <div x-text="v.code + ' (' + v.type + ') Rp ' + new Intl.NumberFormat('id-ID').format(v.nominal)"></div>
                                <template x-if="v.beban_bu"><div class="text-sm text-gray-600" x-text="'Beban BU: ' + v.beban_bu"></div></template>
                            </div>
                        </template>
                    </td></tr></template>
                    <template x-if="detailItem.merged_into"><tr class="border-b border-gray-100"><td class="py-2 text-gray-500 font-medium">Gabung Trip</td><td class="py-2 text-purple-600 font-semibold" x-text="'🔗 ' + (detailItem.merged_into.request_no ?? '-')"></td></tr></template>
                </tbody>
            </table>
            <div class="mt-6 flex justify-end">
                <button type="button" @click="detailModalOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function validateSwapForm(form) {
    const driverVal = form.querySelector('select[name="new_driver_id"]').value;
    const vehicleVal = form.querySelector('select[name="new_vehicle_id"]').value;
    if (!driverVal && !vehicleVal) {
        alert('Pilih driver dan/atau kendaraan pengganti. Biarkan salah satunya kosong kalau memang mau tetap pakai yang sekarang, tapi tidak boleh keduanya kosong.');
        return false;
    }
    return true;
}

function approvalAdminModal() {
    return {
        filterOpen: (function() {
            const p = new URLSearchParams(window.location.search);
            return p.has('search') || p.has('date_from') || p.has('date_to') || p.has('bu');
        })(),
        rejectModalOpen: false,
        rejectRequestId: null,
        forwardModalOpen: false,
        forwardRequestId: null,
        returnModalOpen: false,
        returnRequestId: null,
        returnRequestNo: '',
        swapModalOpen: false,
        swapRequestId: null,
        swapCurrentDriver: '-',
        swapCurrentVehicle: '-',
        detailModalOpen: false,
        detailItem: {},
        openRejectModal(id) {
            this.rejectRequestId = id;
            this.rejectModalOpen = true;
        },
        openForwardModal(id) {
            this.forwardRequestId = id;
            this.forwardModalOpen = true;
        },
        openReturnModal(id, requestNo) {
            this.returnRequestId = id;
            this.returnRequestNo = requestNo || '';
            this.returnModalOpen = true;
        },
        openSwapModal(id, currentDriver, currentVehicle) {
            this.swapRequestId = id;
            this.swapCurrentDriver = currentDriver || '-';
            this.swapCurrentVehicle = currentVehicle || '-';
            this.swapModalOpen = true;
        },
        openDetailModal(item) {
            this.detailItem = item;
            this.detailModalOpen = true;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Cegah klik ganda pada form aksi (Tolak/Forward/Ganti Driver/Selesaikan):
    // begitu disubmit, tombolnya langsung dikunci & teksnya ganti jadi "Memproses...".
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            const btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.dataset.originalText = btn.innerHTML;
                btn.disabled = true;
                btn.classList.add('opacity-60', 'cursor-not-allowed');
                btn.innerHTML = 'Memproses...';
            }
        });
    });
});
</script>
@endsection