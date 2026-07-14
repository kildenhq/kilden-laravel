<p align="center">
  <img src="https://raw.githubusercontent.com/kildenhq/kilden-sdk-php/main/.github/assets/hero.png" alt="Kilden PHP SDK" width="800">
</p>

# kilden/laravel

[![Packagist](https://img.shields.io/packagist/v/kilden/laravel)](https://packagist.org/packages/kilden/laravel)
[![license](https://img.shields.io/packagist/l/kilden/laravel)](LICENSE)

[Kilden](https://kilden.io) is a customer data platform — analytics,
campaigns and session replay on one event pipeline. This package wraps the
[PHP SDK](https://github.com/kildenhq/kilden-sdk-php) for Laravel: a
configured singleton, a facade, queued delivery, and the identity endpoint
your frontend needs for identity verification.

Requires PHP 8.2+ and Laravel 11–13. For plain PHP (7.4+), use
[`kilden/kilden-php`](https://github.com/kildenhq/kilden-sdk-php) directly.

## Install

```sh
composer require kilden/laravel:@alpha kilden/kilden-php:@alpha
```

(Both `@alpha` flags are needed while we ship prereleases — Composer ignores
stability flags on transitive requirements. They go away at 0.1.0.)

```sh
php artisan vendor:publish --tag=kilden-config
```

Set your **secret** write key — never the public `wk_` one, which belongs in
the browser SDK:

```env
KILDEN_WRITE_KEY=sk_...
```

## Track from anywhere

```php
use Kilden\Laravel\Facades\Kilden;

Kilden::track($user->id, 'order_completed', ['revenue' => 99.9, 'currency' => 'CLP']);
Kilden::identify($user->id, ['plan' => 'pro', 'email' => $user->email]);
```

Events flush automatically at the end of the request (and on queue workers,
when the job finishes). With `KILDEN_QUEUE=true`, calls dispatch a job
instead of sending inline — the timestamp is stamped at call time, so
nothing shifts.

## Identity verification

The browser SDK can prove who its events belong to — but only your backend
can sign that proof. One route makes it work:

```php
// routes/web.php
use Kilden\Laravel\KildenRoutes;

KildenRoutes::identity();   // POST /kilden/identity, behind your auth middleware
```

```env
KILDEN_IDENTITY_SECRET=...   # from your Kilden project settings
KILDEN_IDENTITY_KID=k1
```

The route signs a short-lived token for `auth()->user()` and returns
`{ distinct_id, token, traits }` — the web SDK refreshes against it
automatically. To attach signed traits:

```php
KildenRoutes::traitsUsing(fn ($user) => ['plan' => $user->plan]);
```

**Only ever sign the authenticated user.** Signing an id taken from request
input lets anyone impersonate anyone — with a "verified" stamp on top.

## Feature flags

```php
if (Kilden::isEnabled('new_checkout', $user->id, ['default' => false])) {
    // ...
}

$variant = Kilden::getFeatureFlag('pricing_test', $user->id, [
    'person_properties' => ['plan' => $user->plan],
]);
```

## Testing

```php
use Kilden\Laravel\Facades\Kilden;

Kilden::fake();

// ... run code that tracks ...

Kilden::assertTracked('order_completed');
Kilden::assertNothingTracked();
```

With `KILDEN_WRITE_KEY` unset (or `KILDEN_ENABLED=false`) the client is a
silent no-op, so test and local environments need no configuration at all.

## About this repository

Read-only subtree split of
[`kilden-sdk-php/packages/laravel`](https://github.com/kildenhq/kilden-sdk-php/tree/main/packages/laravel)
— issues and pull requests go there. Behavior is governed by the
[server SDK spec](https://github.com/kildenhq/kilden-sdk-spec).

- [docs.kilden.io](https://docs.kilden.io)
- [Discussions](https://github.com/kildenhq/kilden-sdk-php/discussions)

## License

[MIT](LICENSE)
