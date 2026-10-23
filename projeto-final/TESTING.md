# Roteiro de validação — projeto final

## Preparação

```bash
composer install
npm install
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

## Fluxo Database e filas

1. Acesse `http://127.0.0.1:8000`.
2. Marque o pedido como saiu para entrega.
3. Inspecione `jobs` e observe um job por destinatário e canal.
4. Execute `php artisan queue:work --queue=notifications-database,notifications-mail --stop-when-empty`.
5. Abra a central do cliente e marque a notificação como lida.
6. Confirme que `read_at` mudou.

## Mail

Como `MAIL_MAILER=log`, processe `notifications-mail` e veja `storage/logs/laravel.log`. Confirme assunto, saudação, contexto e link do pedido.

## Slack e Push sem entrega externa

```bash
php artisan test --filter=each_representation
php artisan test --filter=customer_channels
php artisan test --filter=operations_user
```

Esses testes constroem e validam Block Kit, Web Push e seleção condicional sem tokens ou dispositivos reais.

## Credenciais reais opcionais

Para Slack, configure `SLACK_BOT_USER_OAUTH_TOKEN` e `SLACK_BOT_USER_DEFAULT_CHANNEL`. Para Push, execute `php artisan webpush:vapid`, sirva a aplicação em HTTPS e registre uma subscription do navegador na rota `push-subscriptions.store`.

## Verificação final

```bash
php artisan migrate:fresh --seed
php artisan test --compact
vendor/bin/pint --test
npm run build
```
