<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Delivery Notifications Lab' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <nav class="border-b border-white/10 bg-slate-900/80">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('dashboard') }}" class="font-bold text-emerald-300">Delivery Notifications Lab</a>
            <a href="{{ url('/api/health') }}" class="text-sm text-slate-300">Health</a>
        </div>
    </nav>
    <main class="mx-auto max-w-6xl px-6 py-10">
        @if (session('status'))
            <p class="mb-6 rounded-xl bg-emerald-400/15 p-4 text-emerald-200">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
</body>
</html>
