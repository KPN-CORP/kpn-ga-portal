@extends('layouts.app')

@section('title', 'Akses Ditolak - GA Portal')

@section('content')
<div class="denied-wrapper">
    <div class="denied-card">
        <!-- Icon Section -->
        <div class="icon-wrapper">
            <div class="icon-circle">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <circle cx="12" cy="16" r="1"/>
                </svg>
            </div>
        </div>

        <!-- Text Content -->
        <h1 class="denied-title">Akses Ditolak</h1>
        <p class="denied-text">
            Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
        </p>

        <!-- Admin Contact -->
        <div class="contact-chip">
            <i class="fas fa-envelope"></i>
            <span>Hub : sudetlin.sugito@kpn-corp.com</span>
        </div>

        <!-- Action Buttons -->
        <div class="action-group">
            <a href="/dashboard" class="btn-primary">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn-ghost">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    /* Main Container */
    .denied-wrapper {
        min-height: calc(100vh - 70px);
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        padding: 20px;
    }

    /* Card */
    .denied-card {
        max-width: 400px;
        width: 100%;
        background: white;
        border-radius: 32px;
        padding: 48px 40px;
        text-align: center;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        transition: transform 0.2s ease;
    }

    .denied-card:hover {
        transform: translateY(-2px);
    }

    /* Icon */
    .icon-wrapper {
        margin-bottom: 32px;
    }

    .icon-circle {
        width: 88px;
        height: 88px;
        background: linear-gradient(135deg, #fee2e2, #fff5f5);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        color: #dc2626;
    }

    .icon-circle svg {
        width: 44px;
        height: 44px;
    }

    /* Text */
    .denied-title {
        font-size: 28px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 12px;
        letter-spacing: -0.5px;
    }

    .denied-text {
        color: #6b7280;
        font-size: 16px;
        line-height: 1.6;
        margin-bottom: 32px;
        max-width: 280px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Contact Chip */
    .contact-chip {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        background: #f3f4f6;
        padding: 12px 24px;
        border-radius: 100px;
        margin-bottom: 40px;
        font-size: 15px;
        color: #374151;
        border: 1px solid #e5e7eb;
        transition: all 0.2s ease;
    }

    .contact-chip:hover {
        background: #e5e7eb;
        border-color: #d1d5db;
    }

    .contact-chip i {
        color: #3b82f6;
        font-size: 14px;
    }

    /* Action Buttons */
    .action-group {
        display: flex;
        gap: 12px;
        justify-content: center;
    }

    .btn-primary, .btn-ghost {
        padding: 12px 24px;
        border-radius: 50px;
        font-size: 15px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
    }

    .btn-primary:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.4);
    }

    .btn-ghost {
        background: transparent;
        color: #4b5563;
        border: 1.5px solid #e5e7eb;
    }

    .btn-ghost:hover {
        background: #f9fafb;
        border-color: #9ca3af;
        color: #111827;
    }

    /* Icons */
    .fas {
        font-size: 14px;
    }

    /* Responsive */
    @media (max-width: 480px) {
        .denied-card {
            padding: 40px 24px;
        }

        .action-group {
            flex-direction: column;
        }

        .denied-title {
            font-size: 24px;
        }

        .contact-chip {
            padding: 10px 20px;
            font-size: 14px;
        }
    }
</style>
@endsection