@extends('layouts.hsrm-app')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')

@section('content')
{{-- FILTER (gaya sama dengan hsrm/equipments & certificates) --}}
<form method="GET" action="{{ route('hsrm.logs.index') }}" class="flex flex-wrap gap-2 items-center w-full mb-4">
    <input type="text" name="search" placeholder="Search name / NIK..." aria-label="Search name or NIK" value="{{ request('search') }}"
           class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 flex-1 min-w-[160px] w-full sm:w-auto">

    @include('hsrm.partials.area-filter', ['areas' => $areas, 'listId' => 'area-datalist-logs'])

    <select name="module" aria-label="Module" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-full sm:w-auto">
        <option value="">All Modules</option>
        <option value="certificate" {{ request('module') == 'certificate' ? 'selected' : '' }}>Certificate</option>
        <option value="equipment" {{ request('module') == 'equipment' ? 'selected' : '' }}>Equipment</option>
    </select>
    <select name="action" aria-label="Action" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-full sm:w-auto">
        <option value="">All Actions</option>
        @foreach($actions as $key => $label)
            <option value="{{ $key }}" {{ request('action') == $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <select name="user_id" aria-label="User" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-full sm:w-auto">
        <option value="">All Users</option>
        <option value="system" {{ request('user_id') == 'system' ? 'selected' : '' }}>System (automatic)</option>
        @foreach($users as $u)
            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
        @endforeach
    </select>
    <div class="flex gap-2 w-full sm:w-auto">
        <input type="date" name="from" title="From date" aria-label="From date" value="{{ request('from') }}"
               class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 flex-1 min-w-0">
        <input type="date" name="to" title="To date" aria-label="To date" value="{{ request('to') }}"
               class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 flex-1 min-w-0">
    </div>

    <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">Filter</button>
    <a href="{{ route('hsrm.logs.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Clear</a>
</form>

@php
    // Tampilan Item (link ke record bila masih ada) dipakai di tabel & kartu.
    $itemLink = function ($log) {
        $label = e($log->item_label ?? ('#' . $log->record_id));
        if ($log->action !== 'delete' && in_array($log->module, ['certificate', 'equipment'], true)) {
            $url = route($log->module === 'certificate' ? 'hsrm.certificates.show' : 'hsrm.equipments.show', $log->record_id);
            return '<a class="text-blue-700 hover:underline" href="' . e($url) . '">' . $label . '</a>';
        }
        return $label;
    };
@endphp

{{-- TABEL (Desktop & Tablet) --}}
<div class="hidden md:block bg-white rounded-xl soft-shadow border soft-border overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 text-left text-xs uppercase text-gray-600">
                <th class="p-3">Time</th>
                <th class="p-3">User</th>
                <th class="p-3">Action</th>
                <th class="p-3">Module</th>
                <th class="p-3">Item</th>
                <th class="p-3">Area</th>
                <th class="p-3">Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                @php $ev = $log->event; $changes = $log->changes; @endphp
                <tr class="border-t align-top">
                    <td class="p-3 whitespace-nowrap">{{ $log->created_at ? $log->created_at->format('d M Y H:i') : '-' }}</td>
                    <td class="p-3 whitespace-nowrap">{{ $log->user->name ?? 'System' }}</td>
                    <td class="p-3 whitespace-nowrap">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ev['badge'] }}">{{ $ev['title'] }}</span>
                    </td>
                    <td class="p-3 whitespace-nowrap">{{ ucfirst($log->module) }}</td>
                    <td class="p-3">{!! $itemLink($log) !!}</td>
                    <td class="p-3 whitespace-nowrap">{{ $log->area->nama_area ?? '-' }}</td>
                    <td class="p-3">
                        @if($log->reason)
                            <p>Reason: {{ $log->reason }}</p>
                        @endif
                        @if(count($changes))
                            <details>
                                <summary class="cursor-pointer text-blue-700">View changes ({{ count($changes) }})</summary>
                                <table class="mt-2 text-xs border-collapse">
                                    @foreach($changes as $c)
                                        <tr>
                                            <td class="pr-3 py-1 border-b">{{ $c['field'] }}</td>
                                            <td class="pr-3 py-1 border-b text-red-800 line-through">{{ $c['before'] }}</td>
                                            <td class="py-1 border-b text-green-800 font-semibold">{{ $c['after'] }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </details>
                        @elseif(!$log->reason)
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-4 text-center text-gray-500">No logs.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- KARTU (HP) --}}
<div class="md:hidden space-y-3">
    @forelse($logs as $log)
        @php $ev = $log->event; $changes = $log->changes; @endphp
        <div class="bg-white rounded-xl soft-shadow border soft-border p-4 text-sm">
            <div class="flex items-start justify-between gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ev['badge'] }}">{{ $ev['title'] }}</span>
                <span class="text-xs text-gray-500 whitespace-nowrap">{{ $log->created_at ? $log->created_at->format('d M Y H:i') : '-' }}</span>
            </div>
            <div class="mt-2 font-semibold break-words">{!! $itemLink($log) !!}</div>
            <div class="mt-1 text-xs text-gray-600">
                {{ $log->user->name ?? 'System' }} · {{ ucfirst($log->module) }} · {{ $log->area->nama_area ?? '-' }}
            </div>
            @if($log->reason)
                <p class="mt-2">Reason: {{ $log->reason }}</p>
            @endif
            @if(count($changes))
                <details class="mt-2">
                    <summary class="cursor-pointer text-blue-700">View changes ({{ count($changes) }})</summary>
                    <div class="mt-2 space-y-2">
                        @foreach($changes as $c)
                            <div class="text-xs">
                                <div class="font-semibold text-gray-700">{{ $c['field'] }}</div>
                                <div class="text-red-800 line-through break-words">{{ $c['before'] }}</div>
                                <div class="text-green-800 font-semibold break-words">{{ $c['after'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    @empty
        <div class="bg-white rounded-xl border p-4 text-center text-gray-500">No logs.</div>
    @endforelse
</div>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection
