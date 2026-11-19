@extends('layouts.app-sidebar')

@section('title', 'Manage Feedback - Admin')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex justify-between items-center mb-5">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Semua Feedback</h1>
            <p class="text-sm text-gray-500">Kelola percakapan dengan pengguna</p>
        </div>
        <div class="bg-gray-100 px-3 py-1 rounded-full text-sm">Total: {{ $feedbacks->count() }}</div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @foreach($feedbacks as $fb)
            @php
                $unreadCount = $fb->replies->where('user_id', '!=', auth()->id())->where('is_read', false)->count();
                $lastReply = $fb->replies->last();
                $lastMessage = $lastReply->message ?? 'Tidak ada pesan';
                $lastTime = $lastReply ? $lastReply->created_at : $fb->created_at;
            @endphp
            <a href="{{ route('feedbacks.admin.show', $fb->id) }}" class="block border-b border-gray-100 hover:bg-gray-50 transition">
                <div class="flex items-center gap-3 p-4">
                    <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user text-gray-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-baseline">
                            <h3 class="font-semibold text-gray-800 truncate">{{ $fb->subject }}</h3>
                            <span class="text-xs text-gray-400">{{ $lastTime->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center mt-0.5">
                            <div class="text-sm text-gray-500 truncate">
                                {{ $fb->user->name }} • {{ Str::limit($lastMessage, 40) }}
                            </div>
                            @if($unreadCount > 0)
                                <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $unreadCount }}</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $fb->status == 'open' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ ucfirst($fb->status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection