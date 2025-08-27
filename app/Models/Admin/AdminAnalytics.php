<?php

namespace App\Models\Admin;

use Carbon\Carbon;
use App\Models\Model;

class AdminAnalytics extends Model
{
    protected string $table = 'analytics';

    private $_totalPeriodInDays = 30;
    private Carbon $_currentPeriodStart;
    private Carbon $_currentPeriodEnd;
    private Carbon $_previousPeriodStart;
    private Carbon $_previousPeriodEnd;

    public function __construct()
    {
        parent::__construct();

        $this->_setPeriods();
    }

    /**
     * Set periods
     *
     * @return void
     */
    private function _setPeriods(): void
    {
        $this->_currentPeriodEnd = Carbon::now();
        $this->_currentPeriodStart = Carbon::now()->subDays($this->_totalPeriodInDays);
        $this->_previousPeriodEnd = $this->_currentPeriodStart->copy()->subDay();
        $this->_previousPeriodStart = $this->_previousPeriodEnd->copy()->subDays($this->_totalPeriodInDays);
    }

    /**
     * Get stats for a period
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return array{
     *     unique_visitors: int,
     *     total_visits: int,
     *     pageviews: int,
     *     pages_per_visit: float,
     *     bounce_rate: float,
     *     avg_duration: float
     * }
     */
    private function _getPeriodStats(Carbon $start, Carbon $end): array
    {
        $startDateStr = $start->toDateTimeString();
        $endDateStr = $end->toDateTimeString();

        // Basic counts
        $basicQueryString = "SELECT
                COUNT(DISTINCT visitor_id) as unique_visitors,
                COUNT(DISTINCT session_id) as total_visits,
                COUNT(*) as pageviews
            FROM " . $this->table . "
            WHERE visit_time BETWEEN $startDateStr AND $endDateStr";
        $basic = self::raw()->query($basicQueryString)->first();

        // Pages per visit
        $pagesPerVisit = $basic->total_visits > 0 ? round($basic->pageviews / $basic->total_visits, 2) : 0;

        // Bounce rate (sessions with only 1 page view)
        $bounceRateQueryString = "
            SELECT COUNT(*) as bounce_sessions
            FROM (
                SELECT session_id, COUNT(*) as page_count
                FROM " . $this->table . "
                WHERE visit_time BETWEEN $startDateStr AND $endDateStr
                GROUP BY session_id
                HAVING page_count = 1
            ) bounce
        ";
        $bounces = self::raw()->query($bounceRateQueryString)->first()->bounce_sessions ?? 0;
        $bounceRate = $basic['total_visits'] > 0 ? round(($bounces / $basic['total_visits']) * 100, 1) : 0;

        // Average session duration (simplified - time between first and last page in session)
        $avgDurationQueryString = "
            SELECT AVG(TIMESTAMPDIFF(MINUTE, first_page, last_page)) as avg_duration
            FROM (
                SELECT
                    session_id,
                    MIN(visit_time) as first_page,
                    MAX(visit_time) as last_page
                FROM " . $this->table . "
                WHERE visit_time BETWEEN $startDateStr AND $endDateStr
                GROUP BY session_id
                HAVING COUNT(*) > 1
            ) sessions
        ";
        $avgDuration = self::raw()->query($avgDurationQueryString)->first()->avg_duration ?? 0;

        return [
            'unique_visitors' => (int) $basic->unique_visitors,
            'total_visits' => (int) $basic->total_visits,
            'pageviews' => (int) $basic->pageviews,
            'pages_per_visit' => $pagesPerVisit,
            'bounce_rate' => $bounceRate,
            'avg_duration' => round($avgDuration, 1)
        ];
    }

    /**
     * Get lifetime stats
     *
     * @return array{
     *     unique_visitors: int,
     *     total_visits: int,
     *     pageviews: int,
     *     days_tracking: int,
     *     avg_visitors_per_day: float,
     *     avg_visits_per_day: float,
     *     avg_pageviews_per_day: float,
     *     first_visit: string,
     *     last_visit: string
     * }
     */
    private function _getLifetimeStats(): array
    {
        // Basic counts for all time
        $queryString = "
            SELECT
                COUNT(DISTINCT visitor_id) as unique_visitors,
                COUNT(DISTINCT session_id) as total_visits,
                COUNT(*) as pageviews,
                MIN(visit_time) as first_visit,
                MAX(visit_time) as last_visit
            FROM " . $this->table . "
        ";
        $basic = self::raw()->query($queryString)->first();

        // Calculate days since first visit
        $firstVisit = Carbon::parse($basic->first_visit);
        $now = Carbon::now();
        $daysSinceStart = $firstVisit->diffInDays($now) + 1; // +1 to include today

        return [
            'unique_visitors' => (int) $basic->unique_visitors,
            'total_visits' => (int) $basic->total_visits,
            'pageviews' => (int) $basic->pageviews,
            'days_tracking' => $daysSinceStart,
            'avg_visitors_per_day' => $daysSinceStart > 0 ? round($basic['unique_visitors'] / $daysSinceStart, 1) : 0,
            'avg_visits_per_day' => $daysSinceStart > 0 ? round($basic['total_visits'] / $daysSinceStart, 1) : 0,
            'avg_pageviews_per_day' => $daysSinceStart > 0 ? round($basic['pageviews'] / $daysSinceStart, 1) : 0,
            'first_visit' => $firstVisit->toDateTimeString(),
            'last_visit' => $basic['last_visit'] ? Carbon::parse($basic['last_visit'])->toDateTimeString() : null,
        ];
    }

    /**
     * Calculate change
     *
     * @param integer|float $old
     * @param integer|float $new
     * @return float
     */
    private function _calculateChange(int|float $old, int|float $new): float
    {
        return match (true) {
            $old == 0 => $new > 0 ? 100 : 0,
            default => round((($new - $old) / $old) * 100, 1)
        };
    }

    /**
     * Get overview stats
     *
     * @return array{
     *     current: array{
     *         unique_visitors: int,
     *         total_visits: int,
     *         pageviews: int,
     *         pages_per_visit: float,
     *         bounce_rate: float,
     *         avg_duration: float
     *     },
     *     previous: array{
     *         unique_visitors: int,
     *         total_visits: int,
     *         pageviews: int,
     *         pages_per_visit: float,
     *         bounce_rate: float,
     *         avg_duration: float
     *     },
     *     lifetime: array{
     *         unique_visitors: int,
     *         total_visits: int,
     *         pageviews: int,
     *         days_tracking: int,
     *         avg_visitors_per_day: float,
     *         avg_visits_per_day: float,
     *         avg_pageviews_per_day: float,
     *         first_visit: string,
     *         last_visit: string
     *     },
     *     changes: array{
     *         unique_visitors: float,
     *         total_visits: float,
     *         pageviews: float,
     *         bounce_rate: float,
     *         avg_duration: float,
     *         pages_per_visit: float
     *     }
     * }
     */
    public function getOverviewStats(): array
    {
        $current = $this->_getPeriodStats($this->_currentPeriodStart, $this->_currentPeriodEnd);
        $previous = $this->_getPeriodStats($this->_previousPeriodStart, $this->_previousPeriodEnd);
        $lifetime = $this->_getLifetimeStats();

        return [
            'current' => $current,
            'previous' => $previous,
            'lifetime' => $lifetime,
            'changes' => [
                'unique_visitors' => $this->_calculateChange($previous['unique_visitors'], $current['unique_visitors']),
                'total_visits' => $this->_calculateChange($previous['total_visits'], $current['total_visits']),
                'pageviews' => $this->_calculateChange($previous['pageviews'], $current['pageviews']),
                'bounce_rate' => $this->_calculateChange($previous['bounce_rate'], $current['bounce_rate']),
                'avg_duration' => $this->_calculateChange($previous['avg_duration'], $current['avg_duration']),
                'pages_per_visit' => $this->_calculateChange($previous['pages_per_visit'], $current['pages_per_visit'])
            ]
        ];
    }

    /**
     * Get top pages
     *
     * @param int $limit
     * @return array{
     *     page_url: string,
     *     pageviews: int,
     *     unique_visitors: int
     * }
     */
    public function getTopPages(int $limit = 10): array
    {
        $queryString = "
            SELECT
                page_url,
                COUNT(*) as pageviews,
                COUNT(DISTINCT visitor_id) as unique_visitors
            FROM " . $this->table . "
            WHERE visit_time BETWEEN " . $this->_currentPeriodStart->toDateTimeString() . " AND " . $this->_currentPeriodEnd->toDateTimeString() . "
            GROUP BY page_url
            ORDER BY pageviews DESC
            LIMIT $limit
        ";

        return self::raw()->query($queryString)->get();
    }

    /**
     * Get top sources
     *
     * @param int $limit
     * @return array{
     *     source: string,
     *     visits: int,
     *     unique_visitors: int
     * }
     */
    public function getTopSources(int $limit = 10): array
    {
        $queryString = "
            SELECT
                CASE
                    WHEN referrer = '' OR referrer IS NULL THEN 'Direct'
                    WHEN referrer LIKE '%google%' THEN 'Google'
                    WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                    WHEN referrer LIKE '%twitter%' THEN 'Twitter'
                    ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(referrer, '/', 3), '//', -1)
                END as source,
                COUNT(*) as visits,
                COUNT(DISTINCT visitor_id) as unique_visitors
            FROM " . $this->table . "
            WHERE visit_time BETWEEN " . $this->_currentPeriodStart->toDateTimeString() . " AND " . $this->_currentPeriodEnd->toDateTimeString() . "
            GROUP BY source
            ORDER BY visits DESC
            LIMIT $limit
        ";

        return self::raw()->query($queryString)->get();
    }

    /**
     * Get location stats
     *
     * @param int $limit
     * @return array{
     *     country: string,
     *     pageviews: int,
     *     unique_visitors: int,
     *     visits: int
     * }
     */
    public function getLocationStats(int $limit = 10): array
    {
        $queryString = "
            SELECT
                country,
                COUNT(*) as pageviews,
                COUNT(DISTINCT visitor_id) as unique_visitors,
                COUNT(DISTINCT session_id) as visits
            FROM analytics
            WHERE visit_time BETWEEN " . $this->_currentPeriodStart->toDateTimeString() . " AND " . $this->_currentPeriodEnd->toDateTimeString() . "
            GROUP BY country
            ORDER BY pageviews DESC
            LIMIT $limit
        ";

        return self::raw()->query($queryString)->get();
    }

    /**
     * Get device stats
     *
     * @return array{
     *     device: string,
     *     os: string,
     *     browser: string,
     *     pageviews: int,
     *     unique_visitors: int
     * }
     */
    public function getDeviceStats(): array
    {
        $queryString = "
            SELECT
                device,
                os,
                browser,
                COUNT(*) as pageviews,
                COUNT(DISTINCT visitor_id) as unique_visitors
            FROM analytics
            WHERE visit_time BETWEEN " . $this->_currentPeriodStart->toDateTimeString() . " AND " . $this->_currentPeriodEnd->toDateTimeString() . "
            GROUP BY device, os, browser
            ORDER BY pageviews DESC
        ";

        return self::raw()->query($queryString)->get();
    }

    public function getDailyTrends(): array
    {
        $queryString = "
            SELECT
                DATE(visit_time) as date,
                COUNT(*) as pageviews,
                COUNT(DISTINCT visitor_id) as unique_visitors,
                COUNT(DISTINCT session_id) as visits
            FROM analytics
            WHERE visit_time BETWEEN " . $this->_currentPeriodStart->toDateTimeString() . " AND " . $this->_currentPeriodEnd->toDateTimeString() . "
            GROUP BY DATE(visit_time)
            ORDER BY date
        ";

        return self::raw()->query($queryString)->get();
    }

    /**
     * Get period name
     *
     * @return string
     */
    public function getPeriodName(): string
    {
        return $this->_currentPeriodStart->format('M j') . ' - ' . $this->_currentPeriodEnd->format('M j, Y');
    }

    /**
     * Get stats for a custom period
     *
     * @param string $period
     * @return array
     */
    // $todayStats = $analytics->getCustomPeriodStats('today');
    // $monthStats = $analytics->getCustomPeriodStats('this_month');
    public function getCustomPeriodStats(string $period): array
    {
        [$start, $end] = match ($period) {
            'today' => [Carbon::today(), Carbon::now()],
            'yesterday' => [Carbon::yesterday(), Carbon::yesterday()->endOfDay()],
            'last_7_days' => [Carbon::now()->subDays(7), Carbon::now()],
            'last_30_days' => [Carbon::now()->subDays(30), Carbon::now()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()],
            'last_month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            'this_year' => [Carbon::now()->startOfYear(), Carbon::now()],
            default => [Carbon::now()->subDays(30), Carbon::now()]
        };

        return $this->_getPeriodStats($start, $end);
    }

    /**
     * Get hourly stats for a day
     *
     * @param Carbon $date
     * @return array
     */
    // $analytics->getHourlyTrends(Carbon::yesterday());
    public function getHourlyTrends(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();

        $queryString = "
            SELECT
                HOUR(visit_time) as hour,
                COUNT(*) as pageviews,
                COUNT(DISTINCT visitor_id) as unique_visitors,
                COUNT(DISTINCT session_id) as visits
            FROM " . $this->table . "
            WHERE DATE(visit_time) = " . $date->toDateString() . "
            GROUP BY HOUR(visit_time)
            ORDER BY hour
        ";

        return self::raw()->query($queryString)->get();
    }
}
