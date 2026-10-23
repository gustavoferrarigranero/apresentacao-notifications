@extends('layout')

@section('content')
    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-emerald-300">Ponto de partida</p>
    <h1 class="mt-4 text-4xl font-bold">Notificações multicanal</h1>
    <p class="mt-4 max-w-3xl text-lg text-slate-300">
        Database, Mail, Slack, Web Push e filas já estão instalados. Models, migrations, controllers, request, service e Notification estão prontos para a implementação guiada.
    </p>
    <div class="mt-8 grid gap-4 md:grid-cols-2">
        @foreach (['Database e leitura', 'Mail no log', 'Slack Block Kit', 'Web Push e VAPID', 'Filas por canal', 'Notification::fake'] as $item)
            <div class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/10">{{ $item }}</div>
        @endforeach
    </div>
@endsection
