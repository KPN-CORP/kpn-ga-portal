<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Gambar - Intervention Image v3</title>
    
    <!-- Bootstrap 5 (biar rapi) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">📸 Upload & Resize Gambar</h5>
                    </div>
                    <div class="card-body">

                        {{-- Tampilkan pesan sukses --}}
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            
                            {{-- Tampilkan hasil gambar yang sudah di-resize --}}
                            @if(session('image_path'))
                                <div class="text-center mb-3">
                                    <p class="text-muted small">Hasil Resize (800x600):</p>
                                    <img src="{{ asset(session('image_path')) }}" 
                                         class="img-fluid rounded border" 
                                         alt="Hasil Upload"
                                         style="max-height: 400px;">
                                </div>
                            @endif
                        @endif

                        {{-- Tampilkan pesan error --}}
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        {{-- Form Upload --}}
                        <form action="{{ route('image.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="image" class="form-label fw-bold">Pilih Gambar (Max 2MB)</label>
                                <input type="file" 
                                       class="form-control @error('image') is-invalid @enderror" 
                                       id="image" 
                                       name="image" 
                                       accept="image/*"
                                       required>
                                
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                
                                <div class="form-text">Format yang didukung: JPG, PNG, GIF, WebP.</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                🚀 Upload & Proses
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (untuk alert dismiss) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>