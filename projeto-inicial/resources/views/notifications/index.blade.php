@extends('layout')

@section('content')
    <h1 class="text-3xl font-bold">Central de {{ $user->name }}</h1>
    <p class="mt-2 text-slate-300">{{ $user->unreadNotifications()->count() }} não lidas de {{ $user->notifications()->count() }} notificações.</p>

    <div class="mt-8 space-y-4">
        @forelse ($notifications as $notification)
            <article class="rounded-2xl p-5 ring-1 ring-white/10 {{ $notification->read_at ? 'bg-white/5' : 'bg-emerald-400/10' }}">
                <p class="font-semibold">{{ $notification->data['message'] }}</p>
                <p class="mt-1 text-sm text-slate-400">Canal database · payload v{{ $notification->data['version'] }}</p>
                <div class="mt-4 flex gap-4">
                    <a href="{{ $notification->data['url'] }}" class="text-emerald-300">Abrir pedido</a>
                    @unless ($notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', [$user, $notification->id]) }}">
                            @csrf
                            @method('PATCH')
                            <button class="text-slate-300">Marcar como lida</button>
                        </form>
                    @endunless
                </div>
            </article>
        @empty
            <p class="rounded-2xl bg-white/5 p-6 text-slate-300">Nenhuma notificação processada. Execute os workers das filas após mudar o status.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $notifications->links() }}</div>
@endsection
