<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CertbotIssuer
{
    public static function run(string $domain, string $email): bool
    {
        $url = sprintf(
            '%s?domain=%s&email=%s',
            rtrim(env('APACHE_CERTBOT_URL'), '/'),
            urlencode($domain),
            urlencode($email),
        );

        $response = Http::timeout(600)                 // 10-min safety net
                        ->withHeaders([
                            'X-Reload-Secret' => env('APACHE_RELOAD_SECRET'),
                        ])
                        ->get($url);

        $body = $response->body();
        Log::debug('[certbot] CGI response:' . PHP_EOL . $body);

        // Success only if CERTBOT_SUCCESS marker present
        return $response->ok() && Str::contains($body, 'CERTBOT_SUCCESS');
    }
}
