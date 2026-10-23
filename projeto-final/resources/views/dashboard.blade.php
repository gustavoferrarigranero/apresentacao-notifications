@extends('layout')

@section('content')
    <h1 class="text-4xl font-bold">Notificações multicanal</h1>
    <p class="mt-3 text-slate-300">Atualize o pedido, processe as filas e confira o histórico por destinatário.</p>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        @foreach ($orders as $order)
            <article class="rounded-2xl bg-white/10 p-6 ring-1 ring-white/10">
                <p class="text-sm text-emerald-300">Pedido #{{ $order->id }}</p>
                <h2 class="mt-2 text-2xl font-semibold">{{ $order->status->label() }}</h2>
                <p class="mt-2 text-slate-300">Cliente: {{ $order->customer->name }}</p>
                <p class="text-slate-300">Total: R$ {{ number_format($order->total_cents / 100, 2, ',', '.') }}</p>
                <form method="POST" action="{{ route('orders.status', $order) }}" class="mt-6">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="out_for_delivery">
                    <button class="rounded-xl bg-emerald-400 px-4 py-3 font-semibold text-slate-950">Marcar como saiu para entrega</button>
                </form>
            </article>
        @endforeach

        <section class="rounded-2xl bg-white/10 p-6 ring-1 ring-white/10">
            <h2 class="text-2xl font-semibold">Destinatários</h2>
            <div class="mt-4 space-y-3">
                @foreach ($users as $user)
                    <a href="{{ route('notifications.index', $user) }}" class="block rounded-xl bg-slate-900 p-4 hover:bg-slate-800">
                        <span class="font-semibold">{{ $user->name }}</span>
                        <span class="block text-sm text-slate-400">{{ $user->is_operations ? 'Operação e Slack' : 'Cliente' }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
@endsection
