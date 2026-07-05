<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Budgetra Admin</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @livewireStyles
</head>
<body class="dashboard-body">
    <div class="dashboard-wrapper">
        <x-admin-sidebar :active="$active ?? ''" />
        <div class="dash-main">
            <div class="app-content">
                @yield('content')
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>
