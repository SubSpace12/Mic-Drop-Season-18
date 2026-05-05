<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fonts (both loaded; only the active one is used via --font-main) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    
    <!-- External CSS -->
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}">
    
    <!-- Scripts -->
    @viteReactRefresh
    @vite(['resources/css/theme.css', 'resources/css/app.css', 'resources/js/app.jsx'])
</head>
@php
    $currentTheme = auth()->check()
        ? (auth()->user()->theme ?? 'emoticon')
        : 'colorland';
@endphp
<body class="font-sans antialiased theme-{{ $currentTheme }}">
    <div
    id="iridescence-bg"
    data-color='[0.749, 0.737, 0.447]'
    data-mouse-react="false"
    style="position: fixed; inset: 0; z-index: 0; pointer-events: none;"
  ></div>
    <div class="min-h-screen">
        @include('layouts.navigation')
        
        <!-- Page Heading -->
        @isset($header)
            <header class="page-header shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="page-header-content">
                        {{ $header }}
                    </div>
                </div>
            </header>
        @endisset
        
        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>
</html>