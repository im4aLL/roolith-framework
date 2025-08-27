<?php

namespace App\Utils\Admin;

class CountryDetector
{
    public static $defaultCountry = 'CA';

    /**
     * Get user country code
     *
     * @param null|string $ip
     * @return string
     */
    public static function getUserCountryCode($ip = null): string
    {
        if (!$ip) {
            $ip = getIpAddress();
        }

        // Skip local/private IPs
        if (self::_isLocalIP($ip)) {
            return self::$defaultCountry;
        }

        // Try multiple services
        $services = [
            "http://ipapi.co/{$ip}/country/",
            "http://ip-api.com/line/{$ip}?fields=countryCode"
        ];

        foreach ($services as $url) {
            $country = self::_fetchCountryCode($url);
            if ($country) {
                return $country;
            }
        }

        // Fallback to language detection
        return self::_getCountryFromLanguage();
    }

    /**
     * Fetch country code
     *
     * @param string $url
     * @return null|string
     */
    private static function _fetchCountryCode(string $url): null|string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && strlen(trim($response)) == 2) {
            return strtoupper(trim($response));
        }

        return null;
    }

    /**
     * Is local IP
     *
     * @param string $ip
     * @return boolean
     */
    private static function _isLocalIP(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1']) ||
            filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Get country code from language
     *
     * @return string
     */
    private static function _getCountryFromLanguage(): string
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $countryMap = [
                'en' => 'US',
                'fr' => 'FR',
                'es' => 'ES',
                'de' => 'DE',
                'it' => 'IT',
                'pt' => 'PT',
                'nl' => 'NL',
                'sv' => 'SE',
                'da' => 'DK',
                'fi' => 'FI',
                'no' => 'NO',
                'pl' => 'PL',
                'cs' => 'CZ',
                'sk' => 'SK',
                'hu' => 'HU',
                'ro' => 'RO',
                'bg' => 'BG',
                'el' => 'GR',
                'ru' => 'RU',
                'tr' => 'TR',
                'ar' => 'SA',
                'zh' => 'CN',
                'ja' => 'JP',
                'ko' => 'KR',
                'hi' => 'IN',
                'id' => 'ID',
                'th' => 'TH',
                'vi' => 'VN',
            ];
            if (isset($countryMap[$language])) {
                return $countryMap[$language];
            }
        }
        return self::$defaultCountry; // Default fallback
    }
}
