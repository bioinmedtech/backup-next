# Yandex Metrica maps and Webvisor

The production virtual host `/etc/nginx/sites-available/bioinmed.ru` overrides
`X-Frame-Options: SAMEORIGIN` inherited from the shared Certbot include for PHP
pages. Do not change the shared Certbot file: it affects other sites.

Public PHP pages use `Content-Security-Policy: frame-ancestors` allowing the site
itself, HTTPS Metrica interfaces and HTTPS Webvisor hosts. Admin, API and internal
paths retain same-origin framing. HSTS and nosniff are explicitly preserved because
location-level `add_header` directives replace inherited nginx headers.

Official domain list: https://yandex.ru/support/metrica/ru/code/install-counter-csp

Place these maps at HTTP scope (before the server blocks in the virtual host):

```nginx
# Allow Yandex Metrica maps on public pages; keep admin/API pages same-origin.
# https://yandex.ru/support/metrica/ru/code/install-counter-csp
map $uri $bioinmed_frame_ancestors {
    default "frame-ancestors 'self' https://metrika.yandex.ru https://analytics.yandex.by https://analytics.yandex.com https://analytics.yandex.com.tr https://analytics.yandex.kz https://analytics.yandex.ru https://metr.yandex.by https://metr.yandex.com https://metr.yandex.com.tr https://metr.yandex.kz https://metr.yandex.ru https://metrica.ya.ru https://metrica.yandex https://metrica.yandex.by https://metrica.yandex.com https://metrica.yandex.com.tr https://metrica.yandex.kz https://metrica.yandex.ru https://metrika.ya.ru https://metrika.yandex https://metrika.yandex.by https://metrika.yandex.com https://metrika.yandex.com.tr https://metrika.yandex.kz https://metrika.yandex.uz https://webvisor.com https://*.webvisor.com;";
    ~^/(admin|api|internal)(/|$) "frame-ancestors 'self';";
}

map $uri $bioinmed_frame_options {
    default "";
    ~^/(admin|api|internal)(/|$) SAMEORIGIN;
}

```

Use these headers inside the existing PHP location, retaining its FastCGI settings:

```nginx
    location ~ \.php$ {
        # Override inherited SAMEORIGIN while retaining the other security headers.
        add_header Strict-Transport-Security "max-age=63072000; includeSubdomains; preload" always;
        add_header X-Content-Type-Options nosniff always;
        add_header X-Frame-Options $bioinmed_frame_options always;
        add_header Content-Security-Policy $bioinmed_frame_ancestors always;
}
```

Validate with `nginx -t`, then reload nginx. Verify response headers for `/`,
`/prices`, `/blog/` and `/admin/`. Public pages must allow the Metrica origins;
admin pages must still return SAMEORIGIN and/or `frame-ancestors 'self'`.
