# Roteiro completo da aula — do inicial ao final

Este projeto já contém a stack e os artefatos repetitivos. O roteiro implementa o mesmo comportamento de `../projeto-final` sem gastar a aula criando arquivos básicos.

## 1. Validar o ambiente

```bash
composer install
npm install
php artisan migrate:fresh --seed
npm run build
php artisan test --compact
php artisan serve
```

Acesse `http://127.0.0.1:8000` e `http://127.0.0.1:8000/api/health`.

Confirme os canais instalados:

```bash
composer show laravel/slack-notification-channel
composer show laravel-notification-channels/webpush
php artisan migrate:status
```

As migrations de pedidos, notificações Database, push subscriptions, jobs e preferências já existem.

## 2. Entender o domínio pronto

Confira:

- `OrderStatus`: enum com preparação, saiu para entrega e entregue;
- `Order`: pertence ao cliente e converte `status` para o enum;
- `User`: possui preferências, papel operacional, `Notifiable` e `HasPushSubscriptions`;
- `OrderFactory` e `DatabaseSeeder`: criam cliente, operação e pedido de demonstração.

Execute:

```bash
php artisan tinker --execute="dump(App\\Models\\Order::with('customer')->first()->toArray());"
```

## 3. Selecionar canais na Notification

Em `app/Notifications/OrderStatusChanged.php`, mantenha construtor e imports e substitua `via()`:

```php
public function via(object $notifiable): array
{
    $channels = ['database'];

    if ($notifiable->wants_email) {
        $channels[] = 'mail';
    }

    if ($notifiable->wants_push && $notifiable->pushSubscriptions()->exists()) {
        $channels[] = WebPushChannel::class;
    }

    if ($notifiable->is_operations) {
        $channels[] = 'slack';
    }

    return $channels;
}
```

Adicione:

```php
use NotificationChannels\WebPush\WebPushChannel;
```

Database permanece obrigatório para auditoria. Mail respeita preferência. Push exige preferência e dispositivo. Slack atende somente operação.

## 4. Tornar a entrega assíncrona e consistente

No construtor:

```php
public function __construct(public readonly Order $order)
{
    $this->afterCommit();
}
```

Adicione as filas independentes:

```php
public function viaQueues(): array
{
    return [
        'database' => 'notifications-database',
        'mail' => 'notifications-mail',
        'slack' => 'notifications-ops',
        WebPushChannel::class => 'notifications-push',
    ];
}
```

`ShouldQueue` já está aplicado. `afterCommit()` impede que workers recebam uma mensagem sobre uma transação revertida. Filas independentes isolam lentidão e falhas.

## 5. Implementar Database

Na Notification:

```php
public function toDatabase(object $notifiable): array
{
    return [
        'version' => 1,
        'order_id' => $this->order->id,
        'status' => $this->order->status->value,
        'message' => "O pedido #{$this->order->id} {$this->order->status->label()}.",
        'url' => route('orders.show', $this->order),
    ];
}

public function toArray(object $notifiable): array
{
    return $this->toDatabase($notifiable);
}
```

O payload persiste identificadores, texto pronto, ação e versão. Ele não serializa o modelo inteiro.

## 6. Implementar Mail

Adicione o import:

```php
use Illuminate\Notifications\Messages\MailMessage;
```

Implemente:

```php
public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('Pedido a caminho')
        ->greeting("Olá, {$notifiable->name}")
        ->line("O pedido #{$this->order->id} saiu para entrega.")
        ->action('Acompanhar pedido', route('orders.show', $this->order))
        ->line('Você receberá uma atualização na entrega.');
}
```

O projeto usa `MAIL_MAILER=log`, permitindo demonstrar conteúdo sem SMTP.

## 7. Implementar Slack com Block Kit

Imports:

```php
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;
```

Método:

```php
public function toSlack(object $notifiable): SlackMessage
{
    return (new SlackMessage)
        ->text('Pedido saiu para entrega')
        ->headerBlock('Entrega em andamento')
        ->sectionBlock(function (SectionBlock $block): void {
            $block->text("Pedido #{$this->order->id}");
            $block->field("*Status:*\n{$this->order->status->label()}")->markdown();
            $block->field("*Cliente:*\n{$this->order->customer->name}")->markdown();
        });
}
```

`User::routeNotificationForSlack()` já direciona para `slack_channel` ou para o canal padrão de `config/services.php`.

Para entrega real, configure:

```dotenv
SLACK_BOT_USER_OAUTH_TOKEN=xoxb-token-do-app
SLACK_BOT_USER_DEFAULT_CHANNEL=#delivery-operations
```

Não use credenciais reais durante testes automatizados.

## 8. Implementar Web Push

Imports:

```php
use NotificationChannels\WebPush\WebPushMessage;
```

Método:

```php
public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
{
    return (new WebPushMessage)
        ->title('Pedido a caminho')
        ->body("O pedido #{$this->order->id} saiu para entrega.")
        ->action('Acompanhar', 'track_order')
        ->data([
            'url' => route('orders.show', $this->order),
            'order_id' => $this->order->id,
        ]);
}
```

O `public/service-worker.js` já trata `push` e `notificationclick`. Gere chaves somente quando quiser testar um navegador real:

```bash
php artisan webpush:vapid
```

Push exige HTTPS fora de localhost, permissão do usuário e subscription válida.

## 9. Implementar o caso de uso

Substitua `OrderStatusService::change()`:

```php
public function change(Order $order, OrderStatus $status): Order
{
    return DB::transaction(function () use ($order, $status): Order {
        $order->update(['status' => $status]);
        $order->refresh()->load('customer');
        $order->customer->notify(new OrderStatusChanged($order));
        Notification::send(
            User::query()->where('is_operations', true)->get(),
            new OrderStatusChanged($order),
        );

        return $order;
    });
}
```

Imports:

```php
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
```

Uma transação muda o status e dispara uma intenção para cliente e operação.

## 10. Validar a entrada e conectar o controller

Em `UpdateOrderStatusRequest`:

```php
public function authorize(): bool
{
    return true;
}

public function rules(): array
{
    return ['status' => ['required', 'in:out_for_delivery,delivered']];
}
```

Implemente `OrderStatusController`:

```php
public function __invoke(
    UpdateOrderStatusRequest $request,
    Order $order,
    OrderStatusService $service,
): RedirectResponse {
    $service->change($order, OrderStatus::from($request->validated('status')));

    return back()->with('status', 'Status atualizado e notificações enfileiradas.');
}
```

Use os imports correspondentes de `OrderStatus`, request, model, service e `RedirectResponse`.

## 11. Central de notificações

Implemente `NotificationCenterController`:

```php
public function index(User $user): View
{
    return view('notifications.index', [
        'user' => $user,
        'notifications' => $user->notifications()->latest()->paginate(20),
    ]);
}

public function read(User $user, string $notification): RedirectResponse
{
    $user->notifications()->findOrFail($notification)->markAsRead();

    return back();
}
```

A busca parte da relação do usuário. Assim, um usuário não marca notificações de outro como lidas.

## 12. Subscription de Push

Implemente `PushSubscriptionController`:

```php
public function __invoke(Request $request, User $user): JsonResponse
{
    $data = $request->validate([
        'endpoint' => ['required', 'url'],
        'keys.auth' => ['required', 'string'],
        'keys.p256dh' => ['required', 'string'],
    ]);

    $user->updatePushSubscription(
        $data['endpoint'],
        $data['keys']['p256dh'],
        $data['keys']['auth'],
    );
    $user->update(['wants_push' => true]);

    return response()->json(['message' => 'Dispositivo registrado.'], 201);
}
```

## 13. Rotas

Use as rotas do projeto final:

```php
Route::get('/', fn () => view('dashboard', [
    'orders' => Order::query()->with('customer')->latest()->get(),
    'users' => User::query()->orderBy('name')->get(),
]))->name('dashboard');

Route::get('/orders/{order}', fn (Order $order) => view('orders.show', [
    'order' => $order->load('customer'),
]))->name('orders.show');

Route::patch('/orders/{order}/status', OrderStatusController::class)->name('orders.status');
Route::get('/users/{user}/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
Route::patch('/users/{user}/notifications/{notification}/read', [NotificationCenterController::class, 'read'])->name('notifications.read');
Route::post('/users/{user}/push-subscriptions', PushSubscriptionController::class)->name('push-subscriptions.store');
```

Mantenha o health check e adicione os imports usados. Para ganhar tempo na aula, copie as views prontas e concentre a explicação no backend:

```bash
cp ../projeto-final/resources/views/dashboard.blade.php resources/views/dashboard.blade.php
cp ../projeto-final/resources/views/notifications/index.blade.php resources/views/notifications/index.blade.php
cp ../projeto-final/resources/views/orders/show.blade.php resources/views/orders/show.blade.php
```

## 14. Demonstrar as filas

Recrie os dados, inicie a aplicação e altere o pedido:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Depois observe e processe os jobs:

```bash
php artisan tinker --execute="dump(DB::table('jobs')->get()->toArray());"
php artisan queue:work --queue=notifications-database,notifications-mail --stop-when-empty
```

Abra a central do cliente. O Mail ficará em `storage/logs/laravel.log`. Não processe Slack e Push sem credenciais válidas.

## 15. Criar os testes da apresentação

Copie a suíte de referência e leia cada cenário antes de executar:

```bash
cp ../projeto-final/tests/Feature/MultichannelNotificationTest.php tests/Feature/MultichannelNotificationTest.php
rm tests/Feature/StarterProjectTest.php
```

A suíte comprova:

- health com quatro canais;
- preferências do cliente e existência de subscription;
- canais Database e Slack para operação;
- mudança de status e destinatários;
- payload de cada representação;
- leitura segura na central;
- filas separadas por canal.

Execute:

```bash
php artisan test --compact
vendor/bin/pint --test
npm run build
```

## 16. Comparar com o resultado final

```bash
diff -ru --exclude=vendor --exclude=node_modules --exclude=storage --exclude=public/build . ../projeto-final
```

Critérios de conclusão:

- uma única Notification representa a mudança;
- `via()` respeita papel, preferência e subscription;
- Database mantém histórico legível e estado de leitura;
- Mail contém contexto e ação segura;
- Slack usa Block Kit para operação;
- Push contém mensagem curta e URL do pedido;
- cada canal externo possui fila própria;
- `afterCommit()` protege consistência;
- testes não dependem de provedores externos.
