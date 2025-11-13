<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ config('app.name', 'Laravel') }}</title>
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
<!-- Scripts -->
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
@import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300;400;500;600;700&display=swap');
* {
font-family: 'Consolas', 'Monaco', 'Roboto Mono', 'Courier New', monospace;
        }
body {
background: linear-gradient(135deg, #1e1e1e 0%, #252526 50%, #2d2d30 100%);
background-attachment: fixed;
min-height: 100vh;
        }
/* Code decoration */
body::before,
body::after {
content: '>';
position: fixed;
font-size: 2rem;
color: #4ec9b0;
opacity: 0.1;
animation: terminal-blink 3s ease-in-out infinite;
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
animation-delay: 1.5s;
content: '//';
        }
@keyframes terminal-blink {
            0%, 100% {
opacity: 0.1;
            }
            50% {
opacity: 0.2;
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
<header class="shadow" style="background: linear-gradient(135deg, #0e639c 0%, #1177bb 50%, #1c88d1 100%); border-bottom: 2px solid #569cd6;">
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
<div style="color: #d4d4d4; font-weight: 700;">
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