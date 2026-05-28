<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/maatregelen-tool.css') }}?v=16">
    @stack('styles')
</head>
<body class="mt-body">
    <a class="mt-skip" href="#mt-main">Naar inhoud</a>

    @if (trim($__env->yieldContent('hide_header')) !== '1')
        <header class="mt-header">
            <div class="mt-header__inner">
                <a href="{{ route('maatregelen.home') }}" class="mt-brand">
                    <span class="mt-brand__title">{{ config('app.name') }}</span>
                    <span class="mt-brand__sub">Klimaatadaptieve maatregelen</span>
                </a>
                <span class="mt-header__badge">Bijlage E</span>
            </div>
        </header>
    @endif

    @if (trim($__env->yieldContent('full_bleed')) === '1')
        <main id="mt-main" class="mt-main mt-main--full-bleed">
            @yield('content')
        </main>
    @else
        <main id="mt-main" class="mt-main">
            <div class="mt-container">
                @yield('content')
            </div>
        </main>
    @endif
    @stack('scripts')
</body>
</html>
