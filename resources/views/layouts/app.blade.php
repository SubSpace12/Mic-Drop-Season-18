<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet" />
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap');

        * {
            font-family: 'Nunito', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #fff0f6 0%, #ffe0f0 50%, #ffd5eb 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        /* Floating sparkles decoration */
        body::before,
        body::after {
            content: '✧';
            position: fixed;
            font-size: 2rem;
            color: #ffb3e6;
            opacity: 0.3;
            animation: float 6s ease-in-out infinite;
            z-index: 0;
        }

        body::before {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        body::after {
            top: 60%;
            right: 10%;
            animation-delay: 3s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .min-h-screen {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen">
        @include('layouts.navigation')
        <!-- Page Heading -->
        @isset($header)
        <header class="shadow" style="background: linear-gradient(135deg, #ff9ed8 0%, #ffb3e6 50%, #ffc9f0 100%); border-bottom: 3px solid #ff69b4;">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div style="color: white; font-weight: 800; text-shadow: 2px 2px 4px rgba(255, 105, 180, 0.3);">
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