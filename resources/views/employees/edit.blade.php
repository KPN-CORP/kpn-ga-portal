@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Employee: {{ $employee->nama_pelanggan }}</h1>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h6 class="m-0 font-weight-bold">Edit Employee Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Current Profile Image -->
                            <div class="col-12 mb-4 text-center">
                                @if($employee->gambar)
                                    <img src="{{ Storage::url($employee->gambar) }}" 
                                         alt="{{ $employee->nama_pelanggan }}" 
                                         class="rounded-circle mb-2" 
                                         style="width: 100px; height: 100px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-2" 
                                         style="width: 100px; height: 100px;">
                                        <i class="fas fa-user text-white fa-2x"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Personal Information -->
                            <div class="col-md-6 mb-3">
                                <label for="nama_pelanggan" class="form-label">Full Name *</label>
                                <input type="text" class="form-control @error('nama_pelanggan') is-invalid @enderror" 
                                       id="nama_pelanggan" name="nama_pelanggan" 
                                       value="{{ old('nama_pelanggan', $employee->nama_pelanggan) }}" required>
                                @error('nama_pelanggan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="username_pelanggan" class="form-label">Username *</label>
                                <input type="text" class="form-control @error('username_pelanggan') is-invalid @enderror" 
                                       id="username_pelanggan" name="username_pelanggan" 
                                       value="{{ old('username_pelanggan', $employee->username_pelanggan) }}" required>
                                @error('username_pelanggan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password (Leave blank to keep current)</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Minimum 6 characters</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email_pelanggan" class="form-label">Email *</label>
                                <input type="email" class="form-control @error('email_pelanggan') is-invalid @enderror" 
                                       id="email_pelanggan" name="email_pelanggan" 
                                       value="{{ old('email_pelanggan', $employee->email_pelanggan) }}" required>
                                @error('email_pelanggan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="no_hp_pelanggan" class="form-label">Phone Number *</label>
                                <input type="text" class="form-control @error('no_hp_pelanggan') is-invalid @enderror" 
                                       id="no_hp_pelanggan" name="no_hp_pelanggan" 
                                       value="{{ old('no_hp_pelanggan', $employee->no_hp_pelanggan) }}" required>
                                @error('no_hp_pelanggan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Company Information -->
                            <div class="col-md-6 mb-3">
                                <label for="bisnis_unit" class="form-label">Business Unit</label>
                                <input type="text" class="form-control @error('bisnis_unit') is-invalid @enderror" 
                                       id="bisnis_unit" name="bisnis_unit" 
                                       value="{{ old('bisnis_unit', $employee->bisnis_unit) }}">
                                @error('bisnis_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="departemen" class="form-label">Department</label>
                                <input type="text" class="form-control @error('departemen') is-invalid @enderror" 
                                       id="departemen" name="departemen" 
                                       value="{{ old('departemen', $employee->departemen) }}">
                                @error('departemen')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="pic" class="form-label">Person In Charge (PIC)</label>
                                <input type="text" class="form-control @error('pic') is-invalid @enderror" 
                                       id="pic" name="pic" value="{{ old('pic', $employee->pic) }}">
                                @error('pic')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="lantai_aktif" class="form-label">Active Floor</label>
                                <input type="text" class="form-control @error('lantai_aktif') is-invalid @enderror" 
                                       id="lantai_aktif" name="lantai_aktif" 
                                       value="{{ old('lantai_aktif', $employee->lantai_aktif) }}">
                                @error('lantai_aktif')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="id_login" class="form-label">Login ID</label>
                                <input type="number" class="form-control @error('id_login') is-invalid @enderror" 
                                       id="id_login" name="id_login" 
                                       value="{{ old('id_login', $employee->id_login) }}">
                                @error('id_login')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Profile Image Update -->
                            <div class="col-md-6 mb-3">
                                <label for="gambar" class="form-label">Update Profile Image</label>
                                <input type="file" class="form-control @error('gambar') is-invalid @enderror" 
                                       id="gambar" name="gambar" accept="image/*">
                                @error('gambar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($employee->gambar)
                                    <div class="form-text">
                                        Current file: {{ basename($employee->gambar) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i>Update Employee
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection