<?php

namespace App\Services;

use App\Config\Database;
use PDO;

class AnalyticsLogger
{
    public static function logAccess()
    {
        // Ignorar logs em chamadas de API ou arquivos estáticos se necessário
        if (strpos($_SERVER['REQUEST_URI'], '/assets') !== false) {
            return;
        }

        $ip = self::getIpAddress();
        $deviceInfo = self::getDeviceInfo();
        $location = self::getLocation($ip); // Cuidado: Isso pode adicionar latência (timeout curto configurado)

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO access_logs (ip_address, city, region, country, device_type, os, browser, page_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $ip,
                $location['city'] ?? null,
                $location['region'] ?? null,
                $location['country'] ?? null,
                $deviceInfo['type'],
                $deviceInfo['os'],
                $deviceInfo['browser'],
                $_SERVER['REQUEST_URI'] ?? '/'
            ]);
        } catch (\Exception $e) {
            // Silencioso: Se falhar o log, o site não pode cair
            error_log("Analytics Error: " . $e->getMessage());
        }
    }

    private static function getIpAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private static function getDeviceInfo()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $type = 'desktop';
        $os = 'Unknown';
        $browser = 'Unknown';

        // Detect Type
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
            $type = 'mobile';
        } elseif (preg_match('/ipad|playbook|silk/i', $userAgent)) {
            $type = 'tablet';
        }

        // Simple OS Detect
        if (preg_match('/windows nt/i', $userAgent))
            $os = 'Windows';
        elseif (preg_match('/macintosh|mac os x/i', $userAgent))
            $os = 'Mac OS';
        elseif (preg_match('/linux/i', $userAgent))
            $os = 'Linux';
        elseif (preg_match('/android/i', $userAgent))
            $os = 'Android';
        elseif (preg_match('/iphone|ipad|ipod/i', $userAgent))
            $os = 'iOS';

        // Simple Browser Detect
        if (preg_match('/MSIE/i', $userAgent) && !preg_match('/Opera/i', $userAgent))
            $browser = 'Internet Explorer';
        elseif (preg_match('/Firefox/i', $userAgent))
            $browser = 'Firefox';
        elseif (preg_match('/Chrome/i', $userAgent))
            $browser = 'Chrome';
        elseif (preg_match('/Safari/i', $userAgent))
            $browser = 'Safari';
        elseif (preg_match('/Opera/i', $userAgent))
            $browser = 'Opera';

        return ['type' => $type, 'os' => $os, 'browser' => $browser];
    }

    private static function getLocation($ip)
    {
        // Ignorar IP local
        if ($ip == '127.0.0.1' || $ip == '::1')
            return null;

        // Tentar obter localização via API (Timeout curto de 1s para não travar o site)
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,regionName,city";

        $ctx = stream_context_create(['http' => ['timeout' => 1]]);

        try {
            $json = @file_get_contents($url, false, $ctx);
            if ($json) {
                $data = json_decode($json, true);
                if (isset($data['status']) && $data['status'] == 'success') {
                    return [
                        'city' => $data['city'],
                        'region' => $data['regionName'],
                        'country' => $data['country']
                    ];
                }
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
}
