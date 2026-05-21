@extends('layouts.app-sidebar')

@section('title', 'Manage Feedback - Admin')

@section('head')
<style>
    .admin-table-card {
        border-radius: 24px;
        border: none;
        overflow: hidden;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
    }
    .table thead th {
        background: #f8fafc;
        font-weight: 600;
        color: #1e293b;
        border-bottom: 2px solid #e2e8f0;
        padding: 1rem;
    }
    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
    }
    .status-badge {
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    .status-open { background: #d1fae5; color: #065f46; }
    .status-closed { background: #f1f5f9; color: #475569; }
    .btn-detail {
        border-radius: 40px;
        padding: 6px 16px;
        font-size: 0.8rem;
        transition: all 0.2s;
    }
    .btn-detail:hover {
        transform: translateY(-2px);
    }
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-tasks me-2 text-primary"></i>Semua Feedback</h2>
            <p class="text-muted">Kelola feedback dari seluruh karyawan</p>
        </div>
        <div class="text-muted">
            Total: <strong class="text-primary">{{ $feedbacks->count() }}</strong> feedback
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card admin-table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th><th>User</th><th>Judul</th><th>Status</th><th>Balasan</th><th>Dibuat</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($feedbacks as $fb)
                    <tr>
                        <td class="fw-semibold">#{{ $fb->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="fas fa-user text-muted fa-sm"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $fb->user->name }}</div>
                                    <small class="text-muted">{{ $fb->user->username }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="fw-medium">{{ $fb->subject }}</td>
                        <td>
                            <span class="status-badge {{ $fb->status == 'open' ? 'status-open' : 'status-closed' }}">
                                {{ ucfirst($fb->status) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                                <i class="far fa-comment me-1"></i>{{ $fb->replies->count() }}
                            </span>
                        </td>
                        <td><small>{{ $fb->created_at->format('d/m/Y H:i') }}</small></td>
                        <td>
                            <a href="{{ route('feedbacks.admin.show', $fb->id) }}" class="btn btn-primary btn-detail">
                                <i class="fas fa-eye me-1"></i>Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection