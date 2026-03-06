<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Check-out Mandiri</title>

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background-color: #f3f4f6; /* gray-100 */
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-md">

        {{-- HEADER --}}
        <div class="text-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">
                Check-out Mandiri
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Apartemen
            </p>
        </div>

        {{-- CARD --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">

            {{-- ALERT SUCCESS --}}
            @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
            @endif

            {{-- ALERT ERROR --}}
            @if(session('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
            @endif

            {{-- ICON --}}
            <div class="flex justify-center mb-4">
                <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>

            {{-- TITLE --}}
            <div class="text-center mb-6">
                <h2 class="text-lg font-semibold text-gray-800">
                    Masukkan Kode Akses
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Kode diberikan oleh HC/GA
                </p>
            </div>

            {{-- FORM --}}
            <form action="{{ route('apartemen.public.verify') }}" method="POST" class="space-y-4">
                @csrf

                <input
                    type="text"
                    name="kode_akses"
                    required
                    maxlength="10"
                    autocomplete="off"
                    autofocus
                    placeholder="XXXXXX"
                    class="w-full text-center text-2xl font-mono uppercase
                           px-4 py-3 rounded-lg border border-gray-300
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

                <button
                    type="submit"
                    class="w-full py-3 rounded-lg text-sm font-semibold
                           bg-blue-600 text-white
                           hover:bg-blue-700 transition">
                    Verifikasi Kode
                </button>
            </form>

            {{-- FOOTNOTE --}}
            <div class="mt-6 text-center text-xs text-gray-400">
                Halaman ini khusus untuk penghuni apartemen
            </div>

        </div>

    </div>

</body>
</html>