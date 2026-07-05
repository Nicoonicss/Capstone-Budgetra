<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Budgetra') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @livewireStyles
    @stack('styles')
</head>
<body class="dashboard-body">
    <div class="dashboard-wrapper" id="dashWrapper">
        <x-sidebar :active="$active ?? ''" />
        <div class="dash-main">
            <div class="dash-content">
                @yield('content')
                {{ $slot ?? '' }}
            </div>
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
