@extends('layouts.app-sidebar')

@section('title', 'Feedback Saya')

@section('head')
<style>
    .feedback-card {
        transition: all 0.3s ease;
        border: none;
        border-radius: 20px;
        background: linear-gradient(145deg, #ffffff 0%, #f8f9ff 100%);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
    }
    .feedback-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 30px -12px rgba(0,0,0,0.15);
    }
    .status-badge {
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .status-open { background: #10b981; color: white; }
    .status-closed { background: #6b7280; color: white; }
    .reply-count {
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 50px;
        padding: 2px 10px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .btn-create {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        border: none;
        border-radius: 12px;
        padding: 10px 24px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-create:hover {
        transform: scale(1.02);
        box-shadow: 0 10px 20px -5px #4f46e580;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold" style="color: #1e293b;">
                <i class="fas fa-comment-dots me-2 text-primary"></i>Feedback Saya
            </h2>
            <p class="text-muted">Kelola saran dan masukan Anda untuk perusahaan</p>
        </div>
        <a href="{{ route('feedbacks.create') }}" class="btn btn-create text-white">
            <i class="fas fa-plus me-2"></i>Buat Feedback Baru
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($feedbacks->count())
        <div class="row g-4">
            @foreach($feedbacks as $fb)
                <div class="col-md-6 col-lg-4">
                    <div class="card feedback-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="status-badge {{ $fb->status == 'open' ? 'status-open' : 'status-closed' }}">
                                    <i class="fas {{ $fb->status == 'open' ? 'fa-circle' : 'fa-lock' }} me-1 fa-xs"></i>
                                    {{ ucfirst($fb->status) }}
                                </span>
                                <span class="reply-count">
                                    <i class="far fa-comment me-1"></i>{{ $fb->replies->count() }} balasan
                                </span>
                            </div>
                            <h5 class="card-title fw-semibold mb-2">{{ $fb->subject }}</h5>
                            <p class="card-text text-muted small mb-3">
                                {{ Str::limit($fb->replies->first()->message ?? 'Tidak ada pesan', 80) }}
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    {{ $fb->created_at->format('d M Y') }}
                                </small>
                                <a href="{{ route('feedbacks.show', $fb->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    Lihat <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="mb-4">
                <i class="fas fa-inbox fa-4x text-muted"></i>
            </div>
            <h4 class="fw-semibold text-secondary">Belum ada feedback</h4>
            <p class="text-muted">Mulai berikan saran atau masukan Anda sekarang.</p>
            <a href="{{ route('feedbacks.create') }}" class="btn btn-primary rounded-pill px-4 mt-2">
                <i class="fas fa-pen me-2"></i>Buat Feedback
            </a>
        </div>
    @endif
</div>
@endsection