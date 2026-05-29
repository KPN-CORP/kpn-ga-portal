{{-- resources/views/feedbacks/admin/show.blade.php --}}
@extends('layouts.app-sidebar')

@section('title', 'Admin - Detail Feedback #'.$feedback->id)

@section('content')
<div class="flex flex-col h-screen max-h-screen bg-gray-100">
    {{-- Header Admin --}}
    <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between sticky top-0 z-10 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ route('feedbacks.admin.index') }}" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h2 class="font-semibold text-gray-800">{{ $feedback->subject }}</h2>
                <div class="text-xs text-gray-500">{{ $feedback->user->name }} ({{ $feedback->user->username }})</div>
            </div>
        </div>
        <form action="{{ route('feedbacks.admin.toggle-status', $feedback->id) }}" method="POST">
            @csrf
            @method('PATCH')
            <button type="submit" class="px-3 py-1.5 rounded-full text-xs font-semibold 
                {{ $feedback->status == 'open' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">
                <i class="fas {{ $feedback->status == 'open' ? 'fa-lock' : 'fa-lock-open' }} mr-1"></i>
                {{ $feedback->status == 'open' ? 'Tutup' : 'Buka' }}
            </button>
        </form>
    </div>

    {{-- Chat Messages --}}
    <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chatMessages">
        @foreach($feedback->replies as $reply)
            @php $isMine = $reply->user_id == auth()->id(); @endphp
            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[75%] {{ $isMine ? 'bg-green-600 text-white' : 'bg-white text-gray-800' }} rounded-2xl px-4 py-2 shadow-sm">
                    <div class="text-xs {{ $isMine ? 'text-green-100' : 'text-gray-500' }} mb-1 flex justify-between gap-3">
                        <span class="font-semibold">{{ $reply->user->name }}</span>
                        <span>{{ $reply->created_at->format('H:i') }}</span>
                    </div>
                    <p class="text-sm leading-relaxed">{{ nl2br(e($reply->message)) }}</p>
                    @if($isMine)
                        <div class="text-right mt-1">
                            @if($reply->is_read)
                                <i class="fas fa-check-double text-xs {{ $isMine ? 'text-green-200' : 'text-gray-400' }}"></i>
                            @else
                                <i class="fas fa-check text-xs {{ $isMine ? 'text-green-200' : 'text-gray-400' }}"></i>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
        <div id="scrollAnchor"></div>
    </div>

    {{-- Input Balasan Admin --}}
    <div class="bg-white border-t border-gray-200 p-3">
        <form action="{{ route('feedbacks.admin.reply', $feedback->id) }}" method="POST" class="flex gap-2">
            @csrf
            <textarea name="message" rows="1" 
                      class="flex-1 resize-none rounded-2xl border-gray-200 focus:border-green-400 focus:ring focus:ring-green-200 text-sm py-2 px-4"
                      placeholder="Ketik balasan sebagai admin..."></textarea>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white rounded-full w-10 h-10 flex items-center justify-center">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
    const container = document.getElementById('chatMessages');
    if(container) container.scrollTop = container.scrollHeight;
</script>
<style>
    textarea {
        min-height: 42px;
        max-height: 120px;
    }
</style>
@endsection