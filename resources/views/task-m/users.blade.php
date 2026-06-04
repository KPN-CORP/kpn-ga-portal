@extends('layouts.app-sidebar')

@section('title', 'Daftar User - Task Monitor')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">👥 Daftar User</h1>
        <p class="text-sm text-gray-500">Pilih user untuk melihat project-task monitor nya</p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item Proses</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item Done</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    @php $stats = \App\Models\Task_M\TaskMonitor::getUserStats($user->id); @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-800">{{ $user->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('task-m.user.projects', $user->id) }}" class="text-indigo-600 hover:underline">
                                {{ $stats['total_projects'] }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('task-m.units', ['status' => 'pending', 'user_id' => $user->id]) }}" class="text-yellow-600 hover:underline">
                                {{ $stats['total_pending'] }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('task-m.units', ['status' => 'done', 'user_id' => $user->id]) }}" class="text-green-600 hover:underline">
                                {{ $stats['total_done'] }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('task-m.user.projects', $user->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                Lihat Project <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection