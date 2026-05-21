@extends('layouts.app-sidebar')

@section('title', 'Admin - Detail Feedback #'.$feedback->id)

@section('head')
<style>
    .chat-container {
        max-height: 550px;
        overflow-y: auto;
        padding: 1rem;
        background: #fcfcfd;
        border-radius: 20px;
    }
    .message-bubble {
        border-radius: 20px;
        padding: 0.9rem 1.2rem;
        margin-bottom: 1rem;
        max-width: 80%;
    }
    .message-user {
        background: #f1f5f9;
        border-bottom-left-radius: 5px;
    }
    .message-admin {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 5px;
    }
    .info-card {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 24px;
        padding: 1.2rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid #4f46e5;
    }
    .btn-toggle {
        border-radius: 40px;
        padding: 6px 20px;
        font-weight: 500;
    }
    .reply-admin-box {
        background: white;
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        padding: 1.2rem;
        margin-top: 1.5rem;
    }
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Tombol back -->
            <div class="mb-3">
                <a href="{{ route('feedbacks.admin.index') }}" class="btn btn-outline-secondary rounded-pill">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke daftar
                </a>
            </div>

            <!-- Info Card -->
            <div class="info-card d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">{{ $feedback->subject }}</h4>
                    <p class="mb-0 text-muted">
                        <i class="fas fa-user me-1"></i> {{ $feedback->user->name }} ({{ $feedback->user->username }})
                        <span class="mx-2">•</span>
                        <i class="far fa-calendar-alt me-1"></i>{{ $feedback->created_at->format('d F Y, H:i') }}
                    </p>
                </div>
                <div>
                    <form action="{{ route('feedbacks.admin.toggle-status', $feedback->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-toggle {{ $feedback->status == 'open' ? 'btn-warning' : 'btn-success' }}">
                            <i class="fas {{ $feedback->status == 'open' ? 'fa-lock' : 'fa-lock-open' }} me-1"></i>
                            {{ $feedback->status == 'open' ? 'Tutup' : 'Buka Kembali' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Chat Container -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-semibold"><i class="fas fa-history me-2"></i>Riwayat Percakapan</h5>
                </div>
                <div class="card-body p-0">
                    <div class="chat-container">
                        @foreach($feedback->replies as $reply)
                            <div class="d-flex {{ $reply->user->isFeedbackAdmin() ? 'justify-content-end' : 'justify-content-start' }}">
                                <div class="message-bubble {{ $reply->user->isFeedbackAdmin() ? 'message-admin' : 'message-user' }}">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong>{{ $reply->user->name }}</strong>
                                        <small class="ms-3 text-muted">{{ $reply->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <p class="mb-0">{{ nl2br(e($reply->message)) }}</p>
                                    @if($reply->user->isFeedbackAdmin())
                                        <div class="text-end mt-1"><small><i class="fas fa-check-double"></i> Admin</small></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Admin Reply Form -->
                <div class="reply-admin-box">
                    <form action="{{ route('feedbacks.admin.reply', $feedback->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><i class="fas fa-reply-all me-1"></i>Balasan Admin</label>
                            <textarea name="message" rows="3" class="form-control rounded-4" placeholder="Ketik balasan Anda di sini..." required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Balasan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection