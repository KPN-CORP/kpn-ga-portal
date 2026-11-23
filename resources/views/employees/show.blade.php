@extends('layouts.app-sidebar')

@section('title','Employee Profile')

@section('content')
<div class="max-w-[95%] mx-auto pb-14">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 py-5 mb-8">
        <div>
            <h1 class="text-lg font-semibold text-gray-800">
                Employee Profile
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Employee ID : {{ $employee->id_pelanggan }}
            </p>
        </div>

        <a href="{{ route('employees.index') }}"
           class="text-sm text-blue-600 hover:underline mt-3 sm:mt-0">
            ← Back to Employees
        </a>
    </div>

    <!-- MAIN PANEL -->
    <div class="bg-white rounded-2xl shadow-[0_4px_16px_rgba(0,0,0,0.06)] divide-y divide-gray-100">

        <!-- BASIC INFORMATION -->
        <section class="px-8 lg:px-16 py-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="section-title">Basic Information</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-x-16 gap-y-8">
                <div class="md:col-span-4">
                    <p class="label">Full Name</p>
                    <p class="value">{{ $employee->nama_pelanggan }}</p>
                </div>

                <div class="md:col-span-4">
                    <p class="label">Username</p>
                    <p class="value">{{ $employee->username_pelanggan }}</p>
                </div>

                <div class="md:col-span-4">
                    <p class="label">Role</p>
                    <p class="value">{{ $employee->role_akses }}</p>
                </div>
            </div>
        </section>

        <!-- CONTACT INFORMATION -->
        <section class="px-8 lg:px-16 py-8">
            <h2 class="section-title mb-6">Contact Information</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-16 gap-y-8">
                <div>
                    <p class="label">Email</p>
                    <a href="mailto:{{ $employee->email_pelanggan }}"
                       class="value text-blue-600 hover:underline">
                        {{ $employee->email_pelanggan }}
                    </a>
                </div>

                <div>
                    <p class="label">Phone</p>
                    <a href="tel:{{ $employee->no_hp_pelanggan }}"
                       class="value text-blue-600 hover:underline">
                        {{ $employee->no_hp_pelanggan }}
                    </a>
                </div>

                <div>
                    <p class="label">Active Floor</p>
                    <p class="value">{{ $employee->lantai_aktif ?? '-' }}</p>
                </div>
            </div>
        </section>

        <!-- JOB INFORMATION -->
        <section class="px-8 lg:px-16 py-8">
            <h2 class="section-title mb-6">Job Information</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-16 gap-y-8">
                <div>
                    <p class="label">Business Unit</p>
                    <p class="value">{{ $employee->bisnis_unit ?? '-' }}</p>
                </div>

                <div>
                    <p class="label">Department</p>
                    <p class="value">{{ $employee->departemen ?? '-' }}</p>
                </div>

                <div>
                    <p class="label">Person In Charge (PIC)</p>
                    <p class="value">{{ $employee->pic ?? '-' }}</p>
                </div>

                <div>
                    <p class="label">Login ID</p>
                    <p class="value">{{ $employee->id_login ?? '-' }}</p>
                </div>
            </div>
        </section>

        <!-- ACCOUNT INFORMATION -->
        <section class="px-8 lg:px-16 py-8">
            <h2 class="section-title mb-6">Account Information</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-16 gap-y-8">
                <div>
                    <p class="label">Created At</p>
                    <p class="value">
                        {{ \Carbon\Carbon::parse($employee->created_at)->format('d M Y H:i') }}
                    </p>
                    <p class="sub">
                        {{ \Carbon\Carbon::parse($employee->created_at)->diffForHumans() }}
                    </p>
                </div>

                <div>
                    <p class="label">Last Updated</p>
                    <p class="value">
                        {{ \Carbon\Carbon::parse($employee->updated_at)->format('d M Y H:i') }}
                    </p>
                    <p class="sub">
                        {{ \Carbon\Carbon::parse($employee->updated_at)->diffForHumans() }}
                    </p>
                </div>
            </div>
        </section>

    </div>
</div>

{{-- STYLE --}}
<style>
.section-title{
    @apply text-sm font-semibold text-gray-700 tracking-wide;
}
.label{
    @apply text-[11px] text-gray-500 uppercase mb-1;
}
.value{
    @apply text-sm font-medium text-gray-900 leading-relaxed;
}
.sub{
    @apply text-xs text-gray-400 mt-1;
}
</style>
@endsection
