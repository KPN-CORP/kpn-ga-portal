@extends('layouts.app')

@section('title', 'Notifikasi')

@section('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
        padding: 15px;
    }
    .notif-container { max-width: 720px; margin: 0 auto; }
    .notif-header {
        background: linear-gradient(90deg, #2c3e50, #4a6491);
        border-radius: 15px;
        padding: 20px;
        color: #fff;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .notif-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .notif-filter a {
        text-decoration: none;
        color: #64748b;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 6px 14px;
        border-radius: 20px;
    }
    .notif-filter a.active {
        background: #3498db;
        color: #fff;
    }
    .notif-row {
        display: flex;
        gap: 14px;
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: inherit;
    }
    .notif-row:hover { background: #f8fafc; }
    .notif-row.unread { background: #eef6ff; }
    .notif-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        flex-shrink: 0;
    }
    .notif-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: .02em;
    }
    .notif-message { color: #334155; font-size: 0.92rem; margin: 2px 0; }
    .notif-time { color: #94a3b8; font-size: 0.78rem; }
    .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
    .back-link { color: #fff; opacity: .9; text-decoration: none; font-size: .85rem; }
    .back-link:hover { color: #fff; opacity: 1; }
</style>
@endsection

@section('content')
<div class="notif-container">
    <div class="notif-header">
        <div>
            <a href="{{ route('dashboard') }}" class="back-link"><i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard</a>
            <h1 class="h4 fw-bold mb-0 mt-2">Notifikasi</h1>
        </div>
        @if($unreadCount > 0)
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm btn-light fw-semibold">
                <i class="fas fa-check-double me-1"></i> Tandai Semua Dibaca
            </button>
        </form>
        @endif
    </div>

    <div class="d-flex gap-2 mb-3 notif-filter">
        <a href="{{ route('notifications.index') }}" class="{{ $activeFilter === 'all' ? 'active' : '' }}">Semua</a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="{{ $activeFilter === 'unread' ? 'active' : '' }}">
            Belum Dibaca @if($unreadCount > 0) ({{ $unreadCount }}) @endif
        </a>
    </div>

    <div class="notif-card">
        @forelse($presented as $notif)
            <a href="{{ route('notifications.read', $notif['id']) }}" class="notif-row {{ $notif['is_read'] ? '' : 'unread' }}">
                <div class="notif-icon" style="background: {{ $notif['color'] }};">
                    <i class="fas {{ $notif['icon'] }}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="notif-label">{{ $notif['label'] }}</div>
                    <div class="notif-message">{{ $notif['message'] }}</div>
                    <div class="notif-time">{{ $notif['time_ago'] }}</div>
                </div>
            </a>
        @empty
            <div class="empty-state">
                <i class="fas fa-bell-slash fa-2x mb-3"></i>
                <div>Belum ada notifikasi.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
