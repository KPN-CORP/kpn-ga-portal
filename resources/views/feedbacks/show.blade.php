{{-- resources/views/feedbacks/show.blade.php --}}
@extends('layouts.app-sidebar')

@section('title', $feedback->subject)

@section('content')
<div class="flex flex-col h-screen max-h-screen bg-gray-100">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 sticky top-0 z-10 shadow-sm">
        <a href="{{ route('feedbacks.index') }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="font-semibold text-gray-800">{{ $feedback->subject }}</h2>
            <div class="flex gap-2 text-xs mt-0.5">
                <span class="text-gray-500">{{ $feedback->user->name }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs {{ $feedback->status == 'open' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                    {{ ucfirst($feedback->status) }}
                </span>
            </div>
        </div>
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

    {{-- Input Form (jika open) --}}
    @if($feedback->status == 'open')
    <div class="bg-white border-t border-gray-200 p-3">
        <form action="{{ route('feedbacks.reply', $feedback->id) }}" method="POST" class="flex gap-2">
            @csrf
            <textarea name="message" rows="1" 
                      class="flex-1 resize-none rounded-2xl border-gray-200 focus:border-green-400 focus:ring focus:ring-green-200 text-sm py-2 px-4"
                      placeholder="Ketik pesan..."></textarea>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white rounded-full w-10 h-10 flex items-center justify-center">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
    @else
    <div class="bg-gray-100 text-center py-3 text-sm text-gray-500 border-t">
        <i class="fas fa-lock mr-1"></i> Feedback sudah ditutup
    </div>
    @endif
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