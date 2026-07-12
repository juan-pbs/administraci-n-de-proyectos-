@props(['title' => 'Administracion de proyectos'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }}</title>
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/utvm/utvm-icon-150.png') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/utvm/utvm-icon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('assets/utvm/utvm-icon.png') }}">
        <meta name="theme-color" content="#15529A">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        {{ $slot }}
    </body>
</html>
