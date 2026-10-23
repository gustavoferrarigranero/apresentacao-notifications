@extends('layout')

@section('content')
    <p class="text-sm text-emerald-300">Pedido #{{ $order->id }}</p>
    <h1 class="mt-2 text-4xl font-bold">{{ $order->status->label() }}</h1>
    <dl class="mt-8 space-y-4 rounded-2xl bg-white/10 p-6">
        <div><dt class="text-slate-400">Cliente</dt><dd>{{ $order->customer->name }}</dd></div>
        <div><dt class="text-slate-400">Endereço</dt><dd>{{ $order->delivery_address }}</dd></div>
        <div><dt class="text-slate-400">Total</dt><dd>R$ {{ number_format($order->total_cents / 100, 2, ',', '.') }}</dd></div>
    </dl>
@endsection
