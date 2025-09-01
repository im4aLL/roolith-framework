<?php

namespace App\Controllers\Admin;

use App\Core\ApiResponseTransformer;
use App\Core\Request;
use App\Models\Admin\AdminAnalytics;
use Carbon\Carbon;

class AdminAnalyticsController extends AdminBaseController
{
    private AdminAnalytics $analytics;

    public function __construct(AdminAnalytics $analytics)
    {
        parent::__construct();

        $this->analytics = $analytics;
    }

    public function index()
    {
        $data = [
            'title' => 'Analytics',
        ];

        return $this->view('admin/analytics/admin-analytics', $data);
    }

    public function overview(): array
    {
        $data = $this->analytics->getOverviewStats();

        if (!$data) {
            return ApiResponseTransformer::error(null, "Overview is not available in lifetime period");
        }

        return ApiResponseTransformer::success($data);
    }

    public function lifetimeOverview(): array
    {
        $data = $this->analytics->getLifetimeStats();

        return ApiResponseTransformer::success($data);
    }

    public function topPages(): array
    {
        $data = $this->analytics->getTopPages();

        return ApiResponseTransformer::success($data);
    }

    public function topSources(): array
    {
        $data = $this->analytics->getTopSources();

        return ApiResponseTransformer::success($data);
    }

    public function locationStats(): array
    {
        $data = $this->analytics->getLocationStats();

        return ApiResponseTransformer::success($data);
    }

    public function deviceStats(): array
    {
        $type = Request::input('by') ?? '';
        $data = $this->analytics->getDeviceStats($type);

        return ApiResponseTransformer::success($data);
    }

    public function dailyTrends(): array
    {
        $data = $this->analytics->getDailyTrends();

        return ApiResponseTransformer::success($data);
    }

    public function periodName(): array
    {
        $data = $this->analytics->getPeriodName();

        return ApiResponseTransformer::success($data);
    }

    public function hourlyTrends(): array
    {
        $date = Request::input('date') ?? Carbon::now()->toDateString();
        $data = $this->analytics->getHourlyTrends(Carbon::parse($date));

        return ApiResponseTransformer::success($data);
    }

    public function setPeriod(): array
    {
        $period = Request::input('period');

        if ($period) {
            if (!in_array($period, $this->analytics->periods)) {
                return ApiResponseTransformer::error(null, 'Invalid period name.');
            }

            return ApiResponseTransformer::success($this->analytics->setPeriod($period));
        }

        $start = Request::input('start');
        $end = Request::input('end');

        if (!$start || !$end) {
            return ApiResponseTransformer::error(null, 'Invalid period.');
        }

        $startDateTime = Carbon::parse($start);
        $endDateTime = Carbon::parse($end);

        if ($endDateTime->lte($startDateTime)) {
            return ApiResponseTransformer::error(null, 'End date must be after start date.');
        }

        return ApiResponseTransformer::success($this->analytics->setPeriodByDate($startDateTime, $endDateTime));
    }
}
