@extends('layouts.app_memos')
@section('title', 'Kelola Tim e-Memo')
@section('content')
<div class="w-full px-2 md:px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">Kelola Tim e-Memo</h2>
            <p class="text-sm text-gray-500">Buat tim, tunjuk admin, dan atur anggotanya. Hanya superadmin yang bisa mengelola halaman ini.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('memo-number-settings.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg shadow-sm">⚙️ Setting Nomor Memo</a>
            <a href="{{ route('memo-teams.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm">+ Tim Baru</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 text-green-700 text-sm px-4 py-2 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($teams as $team)
        <a href="{{ route('memo-teams.show', $team) }}" class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition block">
            <h3 class="font-bold text-lg mb-1">{{ $team->team_name }}</h3>
            <p class="text-xs text-gray-500 mb-3">{{ $team->members_count }} anggota</p>
            <div class="flex flex-wrap gap-1">
                @forelse($team->admins as $admin)
                    <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-full">👤 {{ $admin->name }}</span>
                @empty
                    <span class="text-xs text-gray-400 italic">Belum ada admin</span>
                @endforelse
            </div>
        </a>
        @empty
        <div class="col-span-full text-center text-gray-400 py-12">Belum ada tim. Klik "+ Tim Baru" untuk membuat.</div>
        @endforelse
    </div>
</div>
@endsection
