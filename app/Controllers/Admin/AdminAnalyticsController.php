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
        $data = $this->analytics->getOverviewStats();

        return ApiResponseTransformer::success($data);
    }

    public function topPages()
    {
        $data = $this->analytics->getTopPages();

        return ApiResponseTransformer::success($data);
    }

    public function topSources()
    {
        $data = $this->analytics->getTopSources();

        return ApiResponseTransformer::success($data);
    }

    public function locationStats()
    {
        $data = $this->analytics->getLocationStats();

        return ApiResponseTransformer::success($data);
    }

    public function deviceStats()
    {
        $data = $this->analytics->getDeviceStats();

        return ApiResponseTransformer::success($data);
    }

    public function dailyTrends()
    {
        $data = $this->analytics->getDailyTrends();

        return ApiResponseTransformer::success($data);
    }

    public function periodName()
    {
        $data = $this->analytics->getPeriodName();

        return ApiResponseTransformer::success($data);
    }

    public function customPeriodStats()
    {
        $period = Request::input('period');

        // validate period name
        if (!in_array($period, ['today', 'yesterday', 'last_7_days', 'this_month', 'last_month', 'this_year'])) {
            return ApiResponseTransformer::error(null, 'Invalid period name.');
        }

        $data = $this->analytics->getCustomPeriodStats($period);

        return ApiResponseTransformer::success($data);
    }

    public function hourlyTrends()
    {
        $date = Request::input('date');
        $data = $this->analytics->getHourlyTrends(Carbon::parse($date));

        return ApiResponseTransformer::success($data);
    }
}
