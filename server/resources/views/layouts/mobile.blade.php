<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', "Marshall's Lawn") }} - Mobile</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fef1f3',
                            100: '#fde4e8',
                            200: '#fbc8d1',
                            300: '#f8a0af',
                            400: '#f4657f',
                            500: '#e00a35',
                            600: '#c9092f',
                            700: '#a80828',
                            800: '#8b0721',
                            900: '#73061c',
                            950: '#40020e',
                        }
                    }
                }
            }
        }
    </script>

    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 antialiased">
    {{ $slot }}

    @livewireScripts
</body>
</html>
