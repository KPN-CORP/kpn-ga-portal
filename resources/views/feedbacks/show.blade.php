@extends('layouts.app-sidebar')

@section('title', $feedback->subject)

@section('head')
<style>
    .chat-container {
        max-height: 500px;
        overflow-y: auto;
        padding-right: 5px;
    }
    .message-bubble {
        border-radius: 24px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
        transition: all 0.2s;
    }
    .message-user {
        background: #f1f5f9;
        border-bottom-left-radius: 8px;
    }
    .message-admin {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        border-bottom-right-radius: 8px;
    }
    .message-admin small, .message-admin .text-muted {
        color: rgba(255,255,255,0.8) !important;
    }
    .avatar-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #475569;
    }
    .avatar-admin {
        background: #4f46e5;
        color: white;
    }
    .reply-box {
        background: #f8fafc;
        border-radius: 24px;
        padding: 1.5rem;
        margin-top: 1.5rem;
    }
    .btn-send {
        background: #4f46e5;
        border-radius: 40px;
        padding: 10px 24px;
    }
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 class="fw-bold mb-1">{{ $feedback->subject }}</h3>
                            <div class="mt-2">
                                <span class="badge {{ $feedback->status == 'open' ? 'bg-success' : 'bg-secondary' }} rounded-pill px-3 py-2">
                                    <i class="fas {{ $feedback->status == 'open' ? 'fa-circle' : 'fa-lock' }} me-1 fa-xs"></i>
                                    {{ ucfirst($feedback->status) }}
                                </span>
                                <span class="badge bg-light text-dark rounded-pill px-3 py-2 ms-2">
                                    <i class="far fa-clock me-1"></i>{{ $feedback->created_at->format('d M Y, H:i') }}
                                </span>
                            </div>
                        </div>
                        <a href="{{ route('feedbacks.index') }}" class="btn btn-outline-secondary rounded-pill">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            <!-- Chat Messages -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-semibold"><i class="fas fa-comments me-2"></i>Percakapan</h5>
                </div>
                <div class="card-body p-4 chat-container">
                    @foreach($feedback->replies as $reply)
                        <div class="d-flex {{ $reply->user->isFeedbackAdmin() ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                            @if(!$reply->user->isFeedbackAdmin())
                            <div class="avatar-icon me-3 flex-shrink-0">
                                {{ substr($reply->user->name, 0, 1) }}
                            </div>
                            @endif
                            <div class="message-bubble {{ $reply->user->isFeedbackAdmin() ? 'message-admin' : 'message-user' }}" style="max-width: 75%;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong>{{ $reply->user->name }}</strong>
                                    <small class="ms-3 text-muted">{{ $reply->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                                <p class="mb-0">{{ nl2br(e($reply->message)) }}</p>
                                @if($reply->user->isFeedbackAdmin())
                                    <div class="text-end mt-1">
                                        <small><i class="fas fa-check-double"></i> Admin</small>
                                    </div>
                                @endif
                            </div>
                            @if($reply->user->isFeedbackAdmin())
                            <div class="avatar-icon avatar-admin ms-3 flex-shrink-0">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Reply Form (if open) -->
                @if($feedback->status == 'open')
                <div class="reply-box">
                    <form action="{{ route('feedbacks.reply', $feedback->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tulis balasan Anda</label>
                            <textarea name="message" rows="3" class="form-control rounded-4" placeholder="Ketik pesan Anda di sini..." required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-send text-white">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Balasan
                            </button>
                        </div>
                    </form>
                </div>
                @else
                <div class="alert alert-secondary rounded-4 m-4 text-center">
                    <i class="fas fa-lock me-2"></i>Feedback ini sudah ditutup. Tidak dapat menambah balasan baru.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection