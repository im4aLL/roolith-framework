<?php

namespace App\Utils\Admin;

use App\Core\Storage;
use App\Models\Admin\AdminAnalytics;
use Carbon\Carbon;

class AnalyticsTracker
{
    private string $_visitor_key = 'analytics_visitor';
    private string $_session_key = 'analytics_session';

    /**
     * Set visitor id
     *
     * @return string
     */
    private function _setVisitorId(): string
    {
        $visitorId = Storage::getCookie($this->_visitor_key);

        if (!$visitorId) {
            $visitorId = $this->_generateId();

            Storage::setCookie($this->_visitor_key, $visitorId, Carbon::now()->addYears(2));
        }

        return $visitorId;
    }

    /**
     * Set session id
     *
     * @return string
     */
    private function _setSessionId(): string
    {
        $sessionId = Storage::getSession($this->_session_key);

        if (!$sessionId) {
            $sessionId = $this->_generateId();

            Storage::setSession($this->_session_key, $sessionId);
        }

        return $sessionId;
    }

    /**
     * Generate unique id
     *
     * @return string
     */
    private function _generateId(): string
    {
        return md5(uniqid(rand(), true));
    }

    /**
     * Parse user agent
     *
     * @param string $userAgent
     * @return array{device: string, os: string, browser: string}
     */
    private function _parseUserAgent(string $userAgent): array
    {
        $device = 'Desktop';
        $os = 'Unknown';
        $browser = 'Unknown';

        // Device detection
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            $device = str_contains($userAgent, 'iPad') ? 'Tablet' : 'Mobile';
        }

        // OS detection
        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/', $userAgent)) {
            $os = 'iOS';
        }

        // Browser detection (specific before generic!)
        if (stripos($userAgent, 'OPR') !== false || stripos($userAgent, 'Opera') !== false) {
            $browser = 'Opera';
        } elseif (stripos($userAgent, 'YaBrowser') !== false) {
            $browser = 'Yandex';
        } elseif (stripos($userAgent, 'Edg') !== false) {
            $browser = 'Edge';
        } elseif (stripos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($userAgent, 'Safari') !== false && stripos($userAgent, 'Chrome') === false) {
            // Safari but not Chrome (since Chrome UA also contains Safari)
            $browser = 'Safari';
        } elseif (stripos($userAgent, 'Chrome') !== false) {
            $browser = 'Chrome';
        }

        return compact('device', 'os', 'browser');
    }

    /**
     * Track and save data to analytics
     *
     * @return void
     */
    public function track(): void
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceInfo = $this->_parseUserAgent($userAgent);

        $data = [
            'visitor_id' => $this->_setVisitorId(),
            'session_id' => $this->_setSessionId(),
            'page_url' => $_SERVER['REQUEST_URI'] ?? '/',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'country' => CountryDetector::getUserCountryCode(),
            'device' => $deviceInfo['device'],
            'os' => $deviceInfo['os'],
            'browser' => $deviceInfo['browser'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $userAgent
        ];

        AdminAnalytics::orm()->insert($data);
    }
}
