/* Portal service worker — root install for WAMP subdirectory scope (localhost + production) */
self.addEventListener('install', function(event) {
  self.skipWaiting();
});

self.addEventListener('activate', function(event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function(event) {
  var data = {};
  try {
    if (event.data) {
      data = event.data.json();
    }
  } catch (e) {
    data = { body: event.data ? event.data.text() : '' };
  }

  var title = data.title || 'Office Portal';
  var options = {
    body: data.body || '',
    icon: data.icon || '',
    badge: data.badge || '',
    tag: data.tag || 'portal-default',
    renotify: true,
    data: {
      url: data.url || '/'
    }
  };
  if (data.requireInteraction) {
    options.requireInteraction = true;
  }

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  var targetUrl = '/';
  if (event.notification.data && event.notification.data.url) {
    targetUrl = event.notification.data.url;
  }

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
      var i;
      for (i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if (client.url.indexOf(targetUrl) !== -1 && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});

self.addEventListener('fetch', function(event) {
  return;
});
