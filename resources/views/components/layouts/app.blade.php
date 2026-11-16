{{-- Page layout: header, main content, footer. --}}
@props(['title' => null, 'active' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.brand') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-button focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold">Aller au contenu</a>

    <x-header :active="$active" />

    <main id="contenu" class="flex-1">
        {{ $slot }}
    </main>

    <x-footer />

    @livewireScripts
</body>
</html>
