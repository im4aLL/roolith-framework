<?php $this->inject('admin/partials/admin-header'); ?>

<!-- main -->
<main class="layout<?= getUiStateByKey('compact') == 'compact' ? ' layout-compact' : null ?>" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">

        <div class="block-header">
            <!-- breadcrumb -->
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Analytics</li>
            </ol>
            <!-- breadcrumb -->

            <!-- block header container -->
            <div class="block-header-container">
                <div class="block-header-primary">
                    <h5 class="block-header-title">
                        <?= $this->escape('title') ?>
                    </h5>
                    <p class="block-header-subtitle">Analytics</p>
                </div>
            </div>
            <!-- block header container -->
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <div class="analytics-grid">
            <div class="analytics-grid-item analytics-grid-item-full">
                <?php $this->inject('admin/analytics/admin-analytics-overview') ?>
            </div>
            <div class="analytics-grid-item analytics-grid-item-full">
                <?php $this->inject('admin/analytics/admin-analytics-top-pages') ?>
            </div>
            <div class="analytics-grid-item">
                <?php $this->inject('admin/analytics/admin-analytics-top-sources') ?>
            </div>
            <div class="analytics-grid-item">
                <?php $this->inject('admin/analytics/admin-analytics-device-stats') ?>
            </div>
            <div class="analytics-grid-item analytics-grid-item-full">
                <?php $this->inject('admin/analytics/admin-analytics-location-stats') ?>
            </div>
            <div class="analytics-grid-item analytics-grid-item-full">
                <?php $this->inject('admin/analytics/admin-analytics-daily-trends') ?>
            </div>
            <div class="analytics-grid-item analytics-grid-item-full">
                <?php $this->inject('admin/analytics/admin-analytics-hourly-trends') ?>
            </div>
        </div>

    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>