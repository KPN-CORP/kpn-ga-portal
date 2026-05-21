@extends('layouts.app-sidebar')

@section('title', 'Buat Feedback Baru')

@section('head')
<style>
    .form-card {
        background: white;
        border-radius: 28px;
        box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
        border: none;
        overflow: hidden;
    }
    .form-header {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        padding: 1.5rem;
        color: white;
    }
    .form-label {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    .form-control, .form-select {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        padding: 0.75rem 1rem;
        transition: all 0.2s;
    }
    .form-control:focus, .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 4px #4f46e520;
    }
    .btn-submit {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        border-radius: 16px;
        padding: 12px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px #10b98180;
    }
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card form-card">
                <div class="form-header">
                    <h3 class="mb-1 fw-bold"><i class="fas fa-edit me-2"></i>Form Feedback Baru</h3>
                    <p class="mb-0 opacity-75">Sampaikan ide, kritik, atau saran Anda untuk kemajuan bersama</p>
                </div>
                <div class="card-body p-4 p-lg-5">
                    <form action="{{ route('feedbacks.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="subject" class="form-label">Judul Feedback</label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                                   id="subject" name="subject" placeholder="Contoh: Usulan perbaikan sistem absensi" 
                                   value="{{ old('subject') }}" required>
                            @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="message" class="form-label">Pesan / Detail</label>
                            <textarea class="form-control @error('message') is-invalid @enderror" 
                                      id="message" name="message" rows="6" 
                                      placeholder="Tuliskan saran, masukan, atau keluhan Anda secara detail..." required>{{ old('message') }}</textarea>
                            @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-submit text-white flex-grow-1">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Feedback
                            </button>
                            <a href="{{ route('feedbacks.index') }}" class="btn btn-outline-secondary rounded-4 px-4">
                                <i class="fas fa-times me-1"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection