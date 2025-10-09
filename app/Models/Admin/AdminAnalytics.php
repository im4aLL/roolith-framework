<?php

namespace App\Models\Admin;

use App\Core\Storage;
use Carbon\Carbon;
use App\Models\Model;

class AdminAnalytics extends Model
{
    protected string $table = "analytics";

    private Carbon|null $_currentPeriodStart = null;
    private Carbon|null $_currentPeriodEnd = null;
    private Carbon|null $_previousPeriodStart = null;
    private Carbon|null $_previousPeriodEnd = null;
    public array $periods = [
        "today",
        "yesterday",
        "last_7_days",
        "this_month",
        "last_month",
        "last_6_months",
        "this_year",
        "lifetime",
    ];
    public string $defaultPeriod = "this_month";
    private string $_periodSessionKey = "analytics_period";

    public function __construct()
    {
        if (!Storage::hasSession($this->_periodSessionKey)) {
            $this->setPeriod($this->defaultPeriod);
        } else {
            $data = Storage::getSession($this->_periodSessionKey);

            if ($data["currentPeriodEnd"] && $data["currentPeriodStart"]) {
                $this->_currentPeriodEnd = Carbon::parse($data["currentPeriodEnd"]);
                $this->_currentPeriodStart = Carbon::parse($data["currentPeriodStart"]);
                $this->_previousPeriodEnd = Carbon::parse($data["previousPeriodEnd"]);
                $this->_previousPeriodStart = Carbon::parse($data["previousPeriodStart"]);
            } else {
                $this->_currentPeriodEnd = null;
                $this->_currentPeriodStart = null;
                $this->_previousPeriodEnd = null;
                $this->_previousPeriodStart = null;
            }
        }
    }

    /**
     * Set period
     *
     * @param string $period
     * @return array{
     *     currentPeriodStart: string,
     *     currentPeriodEnd: string,
     *     previousPeriodStart: string,
     *     previousPeriodEnd: string
     * }
     */
    public function setPeriod(string $period): array
    {
        if (!in_array($period, $this->periods)) {
            $period = $this->defaultPeriod;
        }

        [$start, $end] = match ($period) {
            "today" => [Carbon::today(), Carbon::now()],
            "yesterday" => [Carbon::yesterday(), Carbon::yesterday()->endOfDay()],
            "last_7_days" => [Carbon::now()->subDays(7), Carbon::now()],
            "this_month" => [Carbon::now()->startOfMonth(), Carbon::now()],
            "last_month" => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            "last_6_months" => [Carbon::now()->subMonths(6)->startOfMonth(), Carbon::now()],
            "this_year" => [Carbon::now()->startOfYear(), Carbon::now()],
            "lifetime" => [null, null],
            default => [Carbon::now()->subDays(30), Carbon::now()],
        };

        $this->_currentPeriodEnd = $end;
        $this->_currentPeriodStart = $start;

        if ($period === "today" || $period === "yesterday") {
            $this->_previousPeriodEnd = $this->_currentPeriodStart->copy()->subDay()->endOfDay();
            $this->_previousPeriodStart = $this->_previousPeriodEnd->copy()->startOfDay();
        } elseif ($period === "last_7_days") {
            $this->_previousPeriodEnd = $this->_currentPeriodStart->copy()->subDay()->endOfDay();
            $this->_previousPeriodStart = $this->_previousPeriodEnd->copy()->subDays(7)->startOfDay();
        } elseif ($period === "this_month" || $period === "last_month") {
            $this->_previousPeriodEnd = $this->_currentPeriodStart->copy()->subMonth()->endOfMonth();
            $this->_previousPeriodStart = $this->_previousPeriodEnd->copy()->startOfMonth();
        } elseif ($period === "last_6_months") {
            $this->_previousPeriodEnd = $this->_currentPeriodStart->copy()->subDay()->endOfDay();
            $this->_previousPeriodStart = $this->_previousPeriodEnd->copy()->subMonths(6)->startOfDay();
        } elseif ($period === "this_year") {
            $this->_previousPeriodEnd = $this->_currentPeriodStart->copy()->subYear()->endOfYear();
            $this->_previousPeriodStart = $this->_previousPeriodEnd->copy()->startOfYear();
        } elseif ($period === "lifetime") {
            $this->_previousPeriodEnd = null;
            $this->_previousPeriodStart = null;
        }

        $data = [
            "currentPeriodStart" => $this->_currentPeriodStart?->toDateTimeString(),
            "currentPeriodEnd" => $this->_currentPeriodEnd?->toDateTimeString(),
            "previousPeriodStart" => $this->_previousPeriodStart?->toDateTimeString(),
            "previousPeriodEnd" => $this->_previousPeriodEnd?->toDateTimeString(),
            "periodName" => $period,
        ];

        Storage::setSession($this->_periodSessionKey, $data);

        return $data;
    }

    /**
     * Set period by start and end date
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    public function setPeriodByDate(Carbon $start, Carbon $end): array
    {
        $this->_currentPeriodStart = $start;
        $this->_currentPeriodEnd = $end;

        $diffInDays = $this->_currentPeriodStart->diffInDays($this->_currentPeriodEnd);

        $this->_previousPeriodEnd = $this->_currentPeriodStart->copy()->subDays($diffInDays);
        $this->_previousPeriodStart = $this->_previousPeriodEnd->copy()->subDays($diffInDays);

        $data = [
            "currentPeriodStart" => $this->_currentPeriodStart->toDateTimeString(),
            "currentPeriodEnd" => $this->_currentPeriodEnd->toDateTimeString(),
            "previousPeriodStart" => $this->_previousPeriodStart->toDateTimeString(),
            "previousPeriodEnd" => $this->_previousPeriodEnd->toDateTimeString(),
            "periodName" => "custom",
        ];

        Storage::setSession($this->_periodSessionKey, $data);

        return $data;
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
            FROM {$this->table}
            WHERE visit_time BETWEEN '$startDateStr' AND '$endDateStr'";
        $basic = self::raw()->query($basicQueryString)->first();

        // Pages per visit
        $pagesPerVisit = $basic->total_visits > 0 ? round($basic->pageviews / $basic->total_visits, 2) : 0;

        // Bounce rate (sessions with only 1-page view)
        $bounceRateQueryString = "
            SELECT COUNT(*) as bounce_sessions
            FROM (
                SELECT session_id, COUNT(*) as page_count
                FROM {$this->table}
                WHERE visit_time BETWEEN '$startDateStr' AND '$endDateStr'
                GROUP BY session_id
                HAVING page_count = 1
            ) bounce
        ";
        $bounces = self::raw()->query($bounceRateQueryString)->first()->bounce_sessions ?? 0;
        $bounceRate = $basic->total_visits > 0 ? round(($bounces / $basic->total_visits) * 100, 1) : 0;

        // Average session duration (simplified - time between first and last page in session)
        $avgDurationQueryString = "
            SELECT AVG(TIMESTAMPDIFF(SECOND, first_page, last_page)) as avg_duration
            FROM (
                SELECT
                    session_id,
                    MIN(visit_time) as first_page,
                    MAX(visit_time) as last_page
                FROM {$this->table}
                WHERE visit_time BETWEEN '$startDateStr' AND '$endDateStr'
                GROUP BY session_id
                HAVING COUNT(*) > 1
            ) sessions
        ";
        $avgDuration = self::raw()->query($avgDurationQueryString)->first()->avg_duration ?? 0;

        return [
            "unique_visitors" => (int) $basic->unique_visitors,
            "total_visits" => (int) $basic->total_visits,
            "pageviews" => (int) $basic->pageviews,
            "pages_per_visit" => $pagesPerVisit,
            "bounce_rate" => $bounceRate,
            "avg_duration" => round($avgDuration, 1),
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
    public function getLifetimeStats(): array
    {
        // Basic counts for all time
        $queryString = "
            SELECT
                COUNT(DISTINCT visitor_id) as unique_visitors,
                COUNT(DISTINCT session_id) as total_visits,
                COUNT(*) as pageviews,
                MIN(visit_time) as first_visit,
                MAX(visit_time) as last_visit
            FROM {$this->table}
        ";
        $basic = self::raw()->query($queryString)->first();

        // Calculate days since the first visit
        $firstVisit = Carbon::parse($basic->first_visit);
        $now = Carbon::now();
        $daysSinceStart = $firstVisit->diffInDays($now) + 1; // +1 to include today

        return [
            "unique_visitors" => (int) $basic->unique_visitors,
            "total_visits" => (int) $basic->total_visits,
            "pageviews" => (int) $basic->pageviews,
            "days_tracking" => $daysSinceStart,
            "avg_visitors_per_day" => $daysSinceStart > 0 ? round($basic->unique_visitors / $daysSinceStart, 1) : 0,
            "avg_visits_per_day" => $daysSinceStart > 0 ? round($basic->total_visits / $daysSinceStart, 1) : 0,
            "avg_pageviews_per_day" => $daysSinceStart > 0 ? round($basic->pageviews / $daysSinceStart, 1) : 0,
            "first_visit" => $firstVisit->toDateTimeString(),
            "last_visit" => $basic->last_visit ? Carbon::parse($basic->last_visit)->toDateTimeString() : null,
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
        // Handle a special case: no previous value
        if ($old == 0) {
            return $new > 0 ? 100.0 : 0.0;
        }

        // Calculate percentage change
        $difference = $new - $old;
        $percentChange = ($difference / $old) * 100;

        return round($percentChange, 1);
    }

    /**
     * Get overview stats
     *
     * @return array|bool
     */
    public function getOverviewStats(): array|bool
    {
        if (!$this->_currentPeriodStart && !$this->_currentPeriodEnd) {
            return false;
        }

        $current = $this->_getPeriodStats($this->_currentPeriodStart, $this->_currentPeriodEnd);
        $previous = $this->_getPeriodStats($this->_previousPeriodStart, $this->_previousPeriodEnd);

        return [
            "current" => $current,
            "previous" => $previous,
            "changes" => [
                "unique_visitors" => $this->_calculateChange($previous["unique_visitors"], $current["unique_visitors"]),
                "total_visits" => $this->_calculateChange($previous["total_visits"], $current["total_visits"]),
                "pageviews" => $this->_calculateChange($previous["pageviews"], $current["pageviews"]),
                "bounce_rate" => $this->_calculateChange($previous["bounce_rate"], $current["bounce_rate"]),
                "avg_duration" => $this->_calculateChange($previous["avg_duration"], $current["avg_duration"]),
                "pages_per_visit" => $this->_calculateChange($previous["pages_per_visit"], $current["pages_per_visit"]),
            ],
            "start_date" => $this->_currentPeriodStart->toDateTimeString(),
            "end_date" => $this->_currentPeriodEnd->toDateTimeString(),
        ];
    }

    /**
     * Get top pages
     *
     * @param int $limit
     * @return array
     */
    public function getTopPages(int $limit = 10): array
    {
        $queryString = "
            SELECT
                page_url,
                COUNT(*) as pageviews,
                COUNT(DISTINCT visitor_id) as unique_visitors
            FROM {$this->table}
            {$this->_getVisitTimeCondition()}
            GROUP BY page_url
            ORDER BY pageviews DESC
            LIMIT $limit
        ";

        $data = self::raw()->query($queryString)->get();

        return [
            "data" => $data,
            "start_date" => $this->_currentPeriodStart?->toDateTimeString(),
            "end_date" => $this->_currentPeriodEnd?->toDateTimeString(),
        ];
    }

    /**
     * Get top sources
     *
     * @param int $limit
     * @return array
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
            FROM {$this->table}
            {$this->_getVisitTimeCondition()}
            GROUP BY source
            ORDER BY visits DESC
            LIMIT $limit
        ";

        $data = self::raw()->query($queryString)->get();

        return [
            "data" => $data,
            "start_date" => $this->_currentPeriodStart?->toDateTimeString(),
            "end_date" => $this->_currentPeriodEnd?->toDateTimeString(),
        ];
    }

    /**
     * Get location stats
     *
     * @param int $limit
     * @return array
     */
    public function getLocationStats(int $limit = 10): array
    {
        $queryString = "
            SELECT
                country,
                COUNT(*) as pageviews,
                COUNT(DISTINCT visitor_id) as unique_visitors,
                COUNT(DISTINCT session_id) as visits
            FROM {$this->table}
            {$this->_getVisitTimeCondition()}
            GROUP BY country
            ORDER BY pageviews DESC
            LIMIT $limit
        ";

        $data = self::raw()->query($queryString)->get();

        return [
            "data" => $data,
            "start_date" => $this->_currentPeriodStart?->toDateTimeString(),
            "end_date" => $this->_currentPeriodEnd?->toDateTimeString(),
        ];
    }

    /**
     * Get device stats
     *
     * @param string $deviceType
     * @return array
     */
    public function getDeviceStats(string $deviceType = ""): array
    {
        $columnName = match ($deviceType) {
            "size" => "device",
            "os" => "os",
            default => "browser",
        };

        $queryString = "
            SELECT
                $columnName,
                COUNT(*) as pageviews,
                COUNT(DISTINCT visitor_id) as unique_visitors
            FROM {$this->table}
            {$this->_getVisitTimeCondition()}
            GROUP BY $columnName
            ORDER BY pageviews DESC
        ";

        $data = self::raw()->query($queryString)->get();

        return [
            "data" => $data,
            "start_date" => $this->_currentPeriodStart?->toDateTimeString(),
            "end_date" => $this->_currentPeriodEnd?->toDateTimeString(),
        ];
    }

    /**
     * Get daily trends
     *
     * @return array
     */
    public function getDailyTrends(): array
    {
        $queryString = "
            SELECT
                DATE(visit_time) as date,
                COUNT(*) as pageviews,
                COUNT(DISTINCT visitor_id) as unique_visitors,
                COUNT(DISTINCT session_id) as visits
            FROM {$this->table}
            {$this->_getVisitTimeCondition()}
            GROUP BY DATE(visit_time)
            ORDER BY date
        ";

        $data = self::raw()->query($queryString)->get();

        return [
            "data" => $data,
            "start_date" => $this->_currentPeriodStart?->toDateTimeString(),
            "end_date" => $this->_currentPeriodEnd?->toDateTimeString(),
        ];
    }

    /**
     * Get visit time condition query string
     *
     * @return string
     */
    private function _getVisitTimeCondition(): string
    {
        if (!$this->_currentPeriodStart && !$this->_currentPeriodEnd) {
            return "";
        }

        return " WHERE visit_time BETWEEN '{$this->_currentPeriodStart->toDateTimeString()}' AND '{$this->_currentPeriodEnd->toDateTimeString()}'";
    }

    /**
     * Get period name
     *
     * @return array{
     *     start: string,
     *     end: string,
     *     label: string,
     *     name: string
     * }
     */
    public function getPeriodName(): array
    {
        $name = "custom";
        if (Storage::hasSession($this->_periodSessionKey)) {
            $sessionData = Storage::getSession($this->_periodSessionKey);

            $name = $sessionData["periodName"];
        }

        if (!$this->_currentPeriodStart && !$this->_currentPeriodEnd) {
            return [
                "start" => null,
                "end" => null,
                "label" => null,
                "name" => $name,
            ];
        }

        return [
            "start" => $this->_currentPeriodStart->format("M j, Y"),
            "end" => $this->_currentPeriodEnd->format("M j, Y"),
            "label" => $this->_currentPeriodStart->format("M j") . " - " . $this->_currentPeriodEnd->format("M j, Y"),
            "name" => $name,
        ];
    }

    /**
     * Get hourly stats for a day
     *
     * @param Carbon|null $date
     * @return array
     */
    // $analytics->getHourlyTrends(Carbon::yesterday());
    public function getHourlyTrends(?Carbon $date = null): array
    {
        $date ??= Carbon::today();

        $queryString = "
            SELECT
                HOUR(visit_time) as hour,
                COUNT(*) as pageviews,
                COUNT(DISTINCT visitor_id) as unique_visitors,
                COUNT(DISTINCT session_id) as visits
            FROM {$this->table}
            WHERE DATE(visit_time) = '{$date->toDateString()}'
            GROUP BY HOUR(visit_time)
            ORDER BY hour
        ";

        $data = self::raw()->query($queryString)->get();

        return [
            "data" => $data,
            "date" => $date->toDateTimeString(),
        ];
    }
}
