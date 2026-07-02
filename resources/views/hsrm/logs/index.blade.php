@extends('layouts.hsrm-app')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')

@section('content')
<div class="bg-white rounded-lg shadow-sm border overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="bg-gray-50"><th class="p-3">User</th><th class="p-3">Action</th><th class="p-3">Module</th><th class="p-3">Time</th></tr></thead>
        <tbody>
            @forelse($logs as $log)
            <tr class="border-t">
                <td class="p-3">{{ $log->user->name ?? 'System' }}</td>
                <td class="p-3">{{ $log->action }}</td>
                <td class="p-3">{{ $log->module }}</td>
                <td class="p-3">{{ $log->created_at->format('d M Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="p-4 text-center text-gray-500">No logs.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection