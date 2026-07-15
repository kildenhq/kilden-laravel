<?php

declare(strict_types=1);

namespace Kilden\Laravel;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Renders the web SDK loader for the @kildenScript Blade directive: the
 * official async snippet (stub queue, so calls made before the bundle loads
 * are never dropped), kilden.init() with the PUBLIC write key, the identity
 * token callback when the /kilden/identity route is registered, and an
 * identify() for the authenticated user.
 */
class FrontendSnippet
{
    private const CLOUD_HOST = 'https://ingest.kilden.io';

    private const STUB_METHODS = 'init track identify setPersonProperties reset register unregister '
        . 'getDistinctId getSessionId optOut optIn hasOptedOut setIdentityToken flush use removePlugin '
        . 'startSessionRecording stopSessionRecording getReplayId group isFeatureEnabled getFeatureFlag '
        . 'onFeatureFlags';

    public static function render(): string
    {
        $writeKey = (string) config('kilden.frontend.write_key');

        if ($writeKey === '' || ! (bool) config('kilden.enabled', true)) {
            return '';
        }

        $loader = self::loader((string) config('kilden.frontend.cdn', 'https://cdn.kilden.io/kilden.iife.js'));
        $init = sprintf('kilden.init(%s%s);', self::js($writeKey), self::initOptions());
        $identify = self::identify();

        return "<script>\n{$loader}\n  {$init}{$identify}\n</script>";
    }

    private static function loader(string $cdn): string
    {
        $methods = self::STUB_METHODS;
        $src = self::js($cdn);

        return <<<JS
              !(function (w, d) {
                if (w.kilden) return;
                function stub(o, names) {
                  o._q = [];
                  names.split(' ').forEach(function (m) {
                    o[m] = function () { o._q.push([m].concat([].slice.call(arguments))); };
                  });
                  return o;
                }
                var k = (w.kilden = stub({}, '{$methods}'));
                k.flags = stub({}, 'isFeatureEnabled getFeatureFlag getAllFlags onFeatureFlags reload override');
                k.messenger = stub({}, 'open close show hide toggle showNewMessage on off update');
                var s = d.createElement('script');
                s.async = true;
                s.src = {$src};
                d.head.appendChild(s);
              })(window, document);
            JS;
    }

    /**
     * The init options object: configured extras, apiHost only when it
     * differs from Kilden Cloud (the SDK default), and the identity token
     * callback — a function, so the object is assembled by hand instead of
     * one json_encode.
     */
    private static function initOptions(): string
    {
        $pairs = [];

        /** @var array<string, mixed> $options */
        $options = (array) config('kilden.frontend.options', []);

        $host = (string) config('kilden.host', self::CLOUD_HOST);
        if ($host !== self::CLOUD_HOST && ! array_key_exists('apiHost', $options)) {
            $options['apiHost'] = $host;
        }

        foreach ($options as $key => $value) {
            $pairs[] = self::js((string) $key) . ':' . self::js($value);
        }

        if (Route::has('kilden.identity')) {
            // CSRF via the XSRF-TOKEN cookie, read at call time: a token
            // rendered into the page can go stale (login, logout), the
            // cookie cannot.
            $url = self::js(route('kilden.identity', [], false));
            $pairs[] = '"getIdentityToken":async function () {'
                . ' var m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);'
                . ' var r = await fetch(' . $url . ', { method: "POST", credentials: "same-origin",'
                . ' headers: { "X-XSRF-TOKEN": m ? decodeURIComponent(m[1]) : "", "Accept": "application/json" } });'
                . ' if (!r.ok) return null;'
                . ' return (await r.json()).token;'
                . ' }';
        }

        return $pairs === [] ? '' : ', {' . implode(',', $pairs) . '}';
    }

    private static function identify(): string
    {
        $user = Auth::user();
        if ($user === null) {
            return '';
        }

        return "\n  kilden.identify(" . self::js((string) $user->getAuthIdentifier()) . ');';
    }

    /**
     * JSON is valid JS. JSON_HEX_TAG escapes "<" and ">", so no value —
     * however hostile — can close the rendered <script> tag.
     */
    private static function js(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    }
}
