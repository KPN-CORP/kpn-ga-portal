@extends('layouts.app-sidebar')

@section('title', 'Feedback Saya')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Feedback Saya</h1>
            <p class="text-sm text-gray-500">Percakapan dengan Admin</p>
        </div>
        <a href="{{ route('feedbacks.create') }}" 
           class="bg-green-600 hover:bg-green-700 text-white rounded-full p-3 shadow-md transition">
            <i class="fas fa-plus text-lg"></i>
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    @if($feedbacks->count())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            @foreach($feedbacks as $fb)
                @php
                    $unreadCount = $fb->replies->where('user_id', '!=', auth()->id())->where('is_read', false)->count();
                    $lastReply = $fb->replies->last();
                    $lastMessage = $lastReply->message ?? 'Tidak ada pesan';
                    $lastTime = $lastReply ? $lastReply->created_at : $fb->created_at;
                @endphp
                <a href="{{ route('feedbacks.show', $fb->id) }}" class="block border-b border-gray-100 hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3 p-4">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user text-gray-500 text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-baseline">
                                <h3 class="font-semibold text-gray-800 truncate">{{ $fb->subject }}</h3>
                                <span class="text-xs text-gray-400">{{ $lastTime->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex justify-between items-center mt-0.5">
                                <p class="text-sm text-gray-500 truncate">
                                    {{ Str::limit($lastMessage, 50) }}
                                </p>
                                @if($unreadCount > 0)
                                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $unreadCount }}</span>
                                @elseif($fb->status == 'open')
                                    <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">Open</span>
                                @else
                                    <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full">Closed</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-16 bg-gray-50 rounded-2xl">
            <i class="fas fa-inbox text-5xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">Belum ada feedback</p>
            <a href="{{ route('feedbacks.create') }}" class="inline-block mt-3 bg-green-600 text-white px-4 py-2 rounded-full text-sm">Buat Feedback</a>
        </div>
    @endif
</div>
@endsection