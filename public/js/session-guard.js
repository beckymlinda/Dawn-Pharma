/**
 * Session guard: prevents "419 Page Expired" errors.
 *
 * - Refreshes the CSRF token regularly and whenever the tab becomes active again
 *   (e.g. after the computer slept), and writes it into the page and every form.
 * - Makes every same-origin fetch() that changes data send the current token,
 *   and retries once with a fresh token if the server still answers 419.
 * - If the login itself has expired, shows a clear banner instead of letting
 *   the user fill in a form that can no longer be saved.
 */
(function () {
    if (window.__sessionGuard) return;
    window.__sessionGuard = true;

    var TOKEN_URL = '/session/token';
    var REFRESH_EVERY = 4 * 60 * 1000;           // 4 minutes
    var nativeFetch = window.fetch.bind(window);
    var lastRefresh = Date.now();
    var wasLoggedIn = (document.querySelector('meta[name="session-auth"]') || {}).content === '1';

    function currentToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function applyToken(token) {
        if (!token) return;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        document.querySelectorAll('input[name="_token"]').forEach(function (input) { input.value = token; });
        if (window.jQuery && window.jQuery.ajaxSetup) {
            window.jQuery.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });
        }
    }

    function showExpired() {
        if (document.getElementById('sessionExpiredBanner')) return;
        var bar = document.createElement('div');
        bar.id = 'sessionExpiredBanner';
        bar.setAttribute('role', 'alert');
        bar.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:100000;background:#b91c1c;color:#fff;' +
            'padding:12px 16px;display:flex;gap:12px;align-items:center;justify-content:center;flex-wrap:wrap;' +
            'font:600 14px/1.4 system-ui,sans-serif;box-shadow:0 2px 8px rgba(0,0,0,.2)';
        bar.innerHTML = '<span>Your login session has expired. Changes cannot be saved until you log in again.</span>' +
            '<a href="/login" style="background:#fff;color:#b91c1c;padding:6px 14px;border-radius:6px;text-decoration:none">Log in again</a>';
        document.body.appendChild(bar);
    }

    function refresh() {
        lastRefresh = Date.now();
        return nativeFetch(TOKEN_URL, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.ok ? r.json() : null; })
          .then(function (data) {
              if (!data) return null;
              applyToken(data.token);
              if (wasLoggedIn && !data.authenticated) showExpired();
              return data.token;
          })
          .catch(function () { return null; });   // offline: try again later
    }

    function isSameOrigin(url) {
        try { return new URL(url, window.location.href).origin === window.location.origin; }
        catch (e) { return true; }
    }

    // Put the current token on a request (header, and the body if it carries its own _token)
    function withToken(init, token) {
        init = Object.assign({}, init || {});
        var headers = new Headers(init.headers || {});
        headers.set('X-CSRF-TOKEN', token);
        if (!headers.has('X-Requested-With')) headers.set('X-Requested-With', 'XMLHttpRequest');
        init.headers = headers;
        if (!init.credentials) init.credentials = 'same-origin';
        if (init.body instanceof FormData || init.body instanceof URLSearchParams) {
            if (init.body.has('_token')) init.body.set('_token', token);
        }
        return init;
    }

    window.fetch = function (input, init) {
        var url = typeof input === 'string' ? input : (input && input.url) || '';
        var method = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();
        if (method === 'GET' || method === 'HEAD' || !isSameOrigin(url) || url.indexOf(TOKEN_URL) !== -1) {
            return nativeFetch(input, init);
        }
        return nativeFetch(input, withToken(init, currentToken())).then(function (response) {
            if (response.status !== 419) return response;
            // Token was stale: get a fresh one and try once more
            return refresh().then(function (token) {
                return token ? nativeFetch(input, withToken(init, token)) : response;
            });
        });
    };

    // Keep the session and token fresh
    setInterval(refresh, REFRESH_EVERY);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && Date.now() - lastRefresh > 60 * 1000) refresh();
    });
    window.addEventListener('focus', function () {
        if (Date.now() - lastRefresh > 60 * 1000) refresh();
    });
    // A page restored from the back/forward cache has an old token
    window.addEventListener('pageshow', function (e) { if (e.persisted) refresh(); });
})();
