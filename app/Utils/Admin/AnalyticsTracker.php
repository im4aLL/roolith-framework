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
    private function _generateId()
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
            $device = preg_match('/iPad/', $userAgent) ? 'Tablet' : 'Mobile';
        }

        // OS detection
        if (preg_match('/Windows/', $userAgent)) $os = 'Windows';
        elseif (preg_match('/Mac/', $userAgent)) $os = 'macOS';
        elseif (preg_match('/Linux/', $userAgent)) $os = 'Linux';
        elseif (preg_match('/Android/', $userAgent)) $os = 'Android';
        elseif (preg_match('/iOS|iPhone|iPad/', $userAgent)) $os = 'iOS';

        // Browser detection
        if (preg_match('/Chrome/', $userAgent)) $browser = 'Chrome';
        elseif (preg_match('/Firefox/', $userAgent)) $browser = 'Firefox';
        elseif (preg_match('/Safari/', $userAgent)) $browser = 'Safari';
        elseif (preg_match('/Edge/', $userAgent)) $browser = 'Edge';

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
