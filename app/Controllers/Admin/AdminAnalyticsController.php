<?php
namespace App\Controllers\Admin;

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

    public function index() {
//        return $this->analytics->getTopPages();
//        return $this->analytics->getTopSources();
//        return $this->analytics->getLocationStats();
//        return $this->analytics->getDeviceStats();
//        return $this->analytics->getDailyTrends();
//        return $this->analytics->getPeriodName();
//        return $this->analytics->getCustomPeriodStats('this_year');
//        return $this->analytics->getHourlyTrends(Carbon::now()->subDays(2));
        return $this->analytics->getOverviewStats();
    }
}
