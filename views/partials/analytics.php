<?php
if (APP_ENABLE_CMS) {
    try {
        $tracker = new \App\Utils\Admin\AnalyticsTracker();
        $tracker->track();
    } catch (App\Core\Exceptions\Exception $e) {
        error_log('Analytics tracking error: ' . $e->getMessage());
    }
}
