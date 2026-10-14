<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * The certificate a site serves now, read from the Apache container over TLS with the site's
 * name (SNI), the way a browser gets it. Works the same whatever certbot's files say.
 */
class SslCertificate
{
    /**
     * When the certificate Apache serves for $domain expires, or null when there is none for it
     * (Apache answers an unknown name with its default certificate, which does not count).
     */
    public static function expiresAt(string $domain): ?Carbon
    {
        $domain = strtolower(trim($domain));
        if ($domain === '') {
            return null;
        }

        $host    = env('APACHE_TLS_HOST') ?: (parse_url((string) env('APACHE_CERTBOT_URL'), PHP_URL_HOST) ?: 'apache');
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'peer_name'         => $domain,
                'SNI_enabled'       => true,
                // Read it even when it has expired: that is when it matters most.
                'verify_peer'       => false,
                'verify_peer_name'  => false,
            ],
        ]);

        $client = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        if (! $client) {
            Log::warning('[ssl] could not read the certificate.', ['domain' => $domain, 'host' => $host, 'error' => $errstr]);
            return null;
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $cert = $params['options']['ssl']['peer_certificate'] ?? null;
        $info = $cert ? openssl_x509_parse($cert) : false;
        if (! $info || empty($info['validTo_time_t']) || ! self::covers($info, $domain)) {
            return null;
        }

        return Carbon::createFromTimestampUTC($info['validTo_time_t']);
    }

    /**
     * Whether a parsed certificate (openssl_x509_parse) names $domain: its common name or one of
     * its DNS alternative names, a wildcard covering one label.
     */
    public static function covers(array $info, string $domain): bool
    {
        $names = [];
        if (! empty($info['subject']['CN'])) {
            $names[] = (string) $info['subject']['CN'];
        }
        foreach (explode(',', (string) ($info['extensions']['subjectAltName'] ?? '')) as $entry) {
            $entry = trim($entry);
            if (stripos($entry, 'DNS:') === 0) {
                $names[] = substr($entry, 4);
            }
        }

        $domain = strtolower($domain);
        foreach ($names as $name) {
            $name = strtolower(trim($name));
            if ($name === $domain) {
                return true;
            }
            if (str_starts_with($name, '*.') && substr_count($domain, '.') === substr_count($name, '.')
                && str_ends_with($domain, substr($name, 1))) {
                return true;
            }
        }

        return false;
    }
}
