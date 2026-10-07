/**
 * Service worker: lets the POS screen open without internet.
 *
 * Only two things are cached:
 *   - the POS page itself (/pos/new, /pos/create): network first, the saved
 *     copy is used only when the server cannot be reached;
 *   - static files (scripts, styles, fonts, images): served from the cache and
 *     refreshed in the background.
 * Other pages and all data requests always go to the network. The saved POS
 * page is deleted on logout, or when the server says the user is logged out,
 * so the next person on this computer cannot open it.
 */

const PAGE_CACHE = 'pos-page-v1';
const ASSET_CACHE = 'pos-assets-v1';
const POS_PAGES = ['/pos/new', '/pos/create'];
const ASSET_HOSTS = ['cdn.jsdelivr.net'];
const ASSET_PATHS = ['/js/', '/css/', '/assets/', '/build/', '/storage/settings/', '/favicon'];

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keep = [PAGE_CACHE, ASSET_CACHE];
        for (const key of await caches.keys()) {
            if (!keep.includes(key)) await caches.delete(key);
        }
        await self.clients.claim();
    })());
});

function isPosPage(url) {
    return url.origin === self.location.origin && POS_PAGES.includes(url.pathname);
}

function isAsset(url) {
    if (ASSET_HOSTS.includes(url.hostname)) return true;
    return url.origin === self.location.origin && ASSET_PATHS.some((p) => url.pathname.startsWith(p));
}

async function forgetPosPage() {
    await caches.delete(PAGE_CACHE);
}

const OFFLINE_HTML = `<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Offline</title><body style="font-family:system-ui,sans-serif;max-width:32rem;margin:15vh auto;padding:0 1rem;text-align:center">
<h1>No connection</h1><p>The POS screen has not been saved on this device yet. Open it once while online, then it will also open without internet.</p>
<p><button onclick="location.reload()" style="padding:.6rem 1.2rem">Try again</button></p></body>`;

async function posPage(request) {
    try {
        const response = await fetch(request);
        const landedOnLogin = response.redirected && new URL(response.url).pathname === '/login';
        if (landedOnLogin) {
            await forgetPosPage();
        } else if (response.ok) {
            const cache = await caches.open(PAGE_CACHE);
            await cache.put(request, response.clone());
        }
        return response;
    } catch (e) {
        const cached = await caches.match(request, { cacheName: PAGE_CACHE })
            || await caches.match(new Request(POS_PAGES[0]), { cacheName: PAGE_CACHE })
            || await caches.match(new Request(POS_PAGES[1]), { cacheName: PAGE_CACHE });
        return cached || new Response(OFFLINE_HTML, { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } });
    }
}

async function asset(request) {
    const cache = await caches.open(ASSET_CACHE);
    try {
        // Network first, so an updated file is used as soon as it is deployed
        const response = await fetch(request);
        // opaque (cross-origin, status 0) responses from the CDN are fine to keep
        if (response.ok || response.type === 'opaque') cache.put(request, response.clone());
        return response;
    } catch (e) {
        // Offline: this exact file, or failing that any cached version of it
        const cached = await cache.match(request) || await cache.match(request, { ignoreSearch: true });
        if (cached) return cached;
        throw e;
    }
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method === 'POST' && url.origin === self.location.origin && url.pathname === '/logout') {
        event.waitUntil(forgetPosPage());
        return;
    }
    if (request.method !== 'GET') return;

    if (request.mode === 'navigate' && isPosPage(url)) {
        event.respondWith(posPage(request));
    } else if (isAsset(url)) {
        event.respondWith(asset(request));
    }
});
