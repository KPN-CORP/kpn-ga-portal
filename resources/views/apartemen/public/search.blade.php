<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Cari Penghuni - Check-out Mandiri</title>

    {{-- Tailwind --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background-color: #f3f4f6; /* gray-100 */
        }
    </style>
</head>
<body class="min-h-screen px-4 py-6">

<div class="max-w-4xl mx-auto">

    {{-- HEADER --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                Check-out Mandiri
            </h1>

            @php
                $kodeTampil = '—';
                if (session()->has('access_code_data')) {
                    $kodeTampil = session('access_code_data')->kode_akses;
                } elseif (session()->has('access_code')) {
                    try {
                        $kodeTampil = decrypt(session('access_code'));
                    } catch (\Exception $e) {
                        $kodeTampil = '—';
                    }
                }
            @endphp

            <p class="text-sm text-gray-500 mt-1">
                Kode Akses:
                <span class="font-mono text-gray-800 bg-gray-100 px-2 py-0.5 rounded">
                    {{ $kodeTampil }}
                </span>
            </p>
        </div>

        <a href="{{ route('apartemen.public.logout') }}"
           class="text-sm px-4 py-2 rounded-lg border border-gray-300
                  text-gray-700 hover:bg-gray-100 transition">
            Keluar
        </a>
    </div>

    {{-- CARD --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 md:p-8">

        {{-- ERROR --}}
        @if(session('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- TITLE --}}
        <div class="text-center mb-6">
            <h2 class="text-lg font-semibold text-gray-800">
                Cari Nama Anda
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Masukkan nama atau ID karyawan untuk check-out
            </p>
        </div>

        {{-- SEARCH FORM --}}
        <form action="{{ route('apartemen.public.find') }}" method="GET" class="mb-6">
            <div class="flex flex-col sm:flex-row gap-3">
                <input
                    type="text"
                    name="search"
                    required
                    minlength="3"
                    autofocus
                    value="{{ request('search') }}"
                    placeholder="Nama / ID Karyawan"
                    class="flex-1 px-4 py-3 rounded-lg border border-gray-300
                           focus:outline-none focus:ring-2 focus:ring-blue-500
                           focus:border-blue-500">

                <button
                    type="submit"
                    class="px-6 py-3 rounded-lg text-sm font-semibold
                           bg-blue-600 text-white hover:bg-blue-700 transition">
                    Cari
                </button>
            </div>
        </form>

        {{-- RESULT --}}
        @if(request('search'))
            @include('apartemen.public.search-result')
        @endif

    </div>

</div>

</body>
</html>