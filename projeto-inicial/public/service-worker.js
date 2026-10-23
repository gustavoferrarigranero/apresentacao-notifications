self.addEventListener('push', event => {
    const payload = event.data?.json() ?? {};
    event.waitUntil(self.registration.showNotification(payload.title ?? 'Delivery', payload));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data?.url ?? '/'));
});
