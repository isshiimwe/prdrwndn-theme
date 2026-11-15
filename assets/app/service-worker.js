/*
 * PRDRWNDN app service worker - version __VERSION__
 *
 * The installed app is /app/ (served by the theme).
 * This worker:
 *   1. Opens the app screen (/app/) instantly from a saved copy and refreshes
 *      that copy in the background.
 *   2. Sends the old app address (/app.html) to /app/.
 *   3. Keeps a copy of pages you have visited, so the app still
 *      opens with no signal (cart, checkout and account are never kept).
 *
 * WordPress writes this file to the site root from the theme copy
 * (assets/app/service-worker.js) via Tools > PRDRWNDN App.
 * Edit the theme copy, not the file on the server.
 */
var CACHE = 'prd-pages-__VERSION__';
var MAX_PAGES = 30;
var SKIP = /^\/(cart|checkout|my-account|wp-admin|wp-login|wp-json|wc-api|wc-auth)(\/|\.|$)/;

var OFFLINE = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">' +
  '<meta name="viewport" content="width=device-width,initial-scale=1">' +
  '<title>Offline - PRDRWNDN</title><style>' +
  'body{margin:0;min-height:100vh;display:grid;place-items:center;background:#F7F3EC;color:#141714;' +
  'font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;text-align:center;padding:24px}' +
  '.s{height:5px;width:120px;margin:0 auto 22px;background:linear-gradient(90deg,#20AA54 0 33.3%,#FFD200 0 66.6%,#20A0D0 0)}' +
  'h1{font-size:22px;margin:0 0 8px}p{margin:0 0 20px;color:#3B403A}' +
  'button{font:inherit;font-weight:600;border:0;border-radius:999px;padding:12px 24px;background:#1E3B2F;color:#F7F3EC}' +
  '</style></head><body><main><div class="s"></div><h1>You are offline</h1>' +
  '<p>Check your connection, then try again.</p>' +
  '<button onclick="location.reload()">Try again</button></main></body></html>';

var SHELL = '/app/';

self.addEventListener('install', function (event) {
  self.skipWaiting();
  // Save the app screen now, so the next launch opens instantly.
  event.waitUntil(caches.open(CACHE).then(function (c) {
    return fetch(SHELL, { credentials: 'same-origin', cache: 'no-store' }).then(function (r) { if (r.ok) return c.put(SHELL, r); });
  }).catch(function () {}));
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(keys.filter(function (k) { return k !== CACHE; }).map(function (k) { return caches.delete(k); }));
    }).then(function () { return self.clients.claim(); })
  );
});

function keepCopy(request, response) {
  caches.open(CACHE).then(function (cache) {
    return cache.put(request, response).then(function () { return cache.keys(); }).then(function (keys) {
      if (keys.length > MAX_PAGES) return cache.delete(keys[0]);
    });
  }).catch(function () {});
}

self.addEventListener('fetch', function (event) {
  var request = event.request;
  if (request.method !== 'GET' || request.mode !== 'navigate') return;

  var url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Phones that installed the old app still open /app.html: send them to the new app.
  if (url.pathname === '/app.html') {
    event.respondWith(Response.redirect('/app/', 302));
    return;
  }

  // The app screen: open the saved copy right away, fetch a fresh one in the background.
  // (The app itself checks the store's version and reloads if the saved copy was old.)
  if (url.pathname === '/app/' || url.pathname === '/app') {
    var fresh = fetch(request).then(function (response) {
      if (response.ok && response.type === 'basic') {
        var copy = response.clone();
        caches.open(CACHE).then(function (c) { return c.put(SHELL, copy); });
      }
      return response;
    });
    event.respondWith(caches.match(SHELL).then(function (hit) {
      if (hit) { event.waitUntil(fresh.catch(function () {})); return hit; }
      return fresh.catch(function () { return new Response(OFFLINE, { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }); });
    }));
    return;
  }

  // Cart, checkout, account, admin and anything with a query string:
  // always straight to the network, never stored.
  if (SKIP.test(url.pathname) || url.search) return;

  event.respondWith(
    fetch(request).then(function (response) {
      if (response.ok && response.type === 'basic') keepCopy(request, response.clone());
      return response;
    }).catch(function () {
      return caches.match(request).then(function (hit) {
        return hit || caches.match('/');
      }).then(function (hit) {
        return hit || new Response(OFFLINE, { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } });
      });
    })
  );
});
