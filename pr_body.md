## Problem

Login at https://warehouse-mesm.onrender.com/login always returns **419 Page Expired** (session cookie never persisted in browser).

Two root causes:

**1. nginx didn't tell PHP-FPM it was behind HTTPS**
`fastcgi_param HTTPS` was missing, so PHP thought requests were HTTP. With `SESSION_SECURE_COOKIE=true`, Laravel refused to send session cookies over "HTTP" — the session was created server-side but the cookie never reached the browser.

**2. SESSION_DOMAIN blocked by PSL**
`SESSION_DOMAIN=.onrender.com` was set. Since `onrender.com` is on the Public Suffix List, browsers reject cookies with `domain=.onrender.com` as a security measure. Without the session cookie persisting, every POST creates a new anonymous session whose CSRF token doesn't match — hence 419.

## Fixes

- **`docker/nginx/default-render.conf`** — Added `fastcgi_param HTTPS on;` and `fastcgi_param HTTP_X_FORWARDED_PROTO https;` so PHP/Laravel detects HTTPS correctly
- **`render.yaml`** — Removed `SESSION_DOMAIN=.onrender.com` so Laravel defaults to the exact request host (`warehouse-mesm.onrender.com`), which browsers accept
- **`resources/views/errors/419.blade.php`** — Custom 419 error page with a friendly "Session Expired" message and a button back to login

## Testing

Verified with `curl` that the server sends Set-Cookie headers. After removing `SESSION_DOMAIN`, cookies use the correct domain.
