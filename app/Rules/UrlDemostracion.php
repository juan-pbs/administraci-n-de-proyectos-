<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UrlDemostracion implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $url = parse_url($value);
        $host = strtolower($url['host'] ?? '');
        $appHost = strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?? '');
        $requestHost = app()->runningInConsole() ? '' : strtolower(request()->getHost());
        $ip = trim($host, '[]');
        $public = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        $domain = preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D', $host);
        if (! filter_var($value, FILTER_VALIDATE_URL) || ($url['scheme'] ?? '') !== 'https' || isset($url['user']) || isset($url['pass'])
            || (isset($url['port']) && $url['port'] !== 443) || (! $public && ! $domain)
            || in_array($host, [$appHost, $requestHost], true) || preg_match('/\.(local|localhost|internal|test|invalid)$/D', $host)
            || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) {
            $fail('La demostración debe ser una URL HTTPS pública, sin credenciales ni direcciones internas o del propio sistema.');
        }
    }
}
