<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- DEFAULT TITLE -->
    <title>@yield('title', 'GA Portal')</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('KPN123.png') }}" type="image/x-icon">

    <!-- Untuk CSS global -->
    @yield('head')
</head>
<body>
    @yield('content')

    <!-- Scripts -->
    @yield('scripts')
</body>
</html>