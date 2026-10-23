# Notificações Multicanal no Laravel — projeto final

Laboratório de delivery com uma única `OrderStatusChanged` representada nos canais Database, Mail, Slack e Web Push. Inclui preferências, central de notificações, leitura, filas por canal, consistência transacional e testes sem provedores externos.

## Executar

```bash
composer install
npm install
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Em outro terminal, processe as filas locais:

```bash
php artisan queue:work --queue=notifications-database,notifications-mail,notifications-ops,notifications-push
```

Sem credenciais de Slack e Web Push, use os testes e os métodos de representação para demonstrar payloads sem fazer entregas externas. O Mail usa o driver `log` por padrão.

## Qualidade

```bash
php artisan test --compact
vendor/bin/pint --test
npm run build
```
