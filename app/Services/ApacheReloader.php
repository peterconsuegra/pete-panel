<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ApacheReloader
{
    public static function graceful(): bool
    {
        $resp = Http::withHeaders([
                    'X-Reload-Secret' => env('APACHE_RELOAD_SECRET'),
                ])->get(env('APACHE_RELOAD_URL'));     // ← GET, not post

        return $resp->ok() && trim($resp->body()) === 'Reloaded';
    }
}