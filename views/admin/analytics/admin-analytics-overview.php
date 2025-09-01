<div class="block-analytics" id="block-analytics-overview">
    <div class="block-analytics-header">
        <h6 class="block-analytics-hl">Overview</h6>

        <ul class="quick-menu small" id="overview-period">
            <li class="quick-menu-item active" data-period="this_month">
                <a href="<?= route('admin.analytics.setPeriod') ?>?period=this_month" class="quick-menu-link">This
                    month</a>
            </li>
            <li class="quick-menu-item" data-period="last_month">
                <a href="<?= route('admin.analytics.setPeriod') ?>?period=last_month" class="quick-menu-link">Last
                    month</a>
            </li>
            <li class="quick-menu-item" data-period="last_7_days">
                <a href="<?= route('admin.analytics.setPeriod') ?>?period=last_7_days" class="quick-menu-link">Last 7
                    days</a>
            </li>
            <li class="quick-menu-item" data-period="yesterday">
                <a href="<?= route('admin.analytics.setPeriod') ?>?period=yesterday"
                    class="quick-menu-link">Yesterday</a>
            </li>
            <li class="quick-menu-item" data-period="today">
                <a href="<?= route('admin.analytics.setPeriod') ?>?period=today" class="quick-menu-link">Today</a>
            </li>
            <li class="quick-menu-item" data-period="last_6_months">
                <a href="<?= route('admin.analytics.setPeriod') ?>?period=last_6_months" class="quick-menu-link">Last 6
                    months</a>
            </li>
            <li class="quick-menu-item" data-period="this_year">
                <a href="<?= route('admin.analytics.setPeriod') ?>?period=this_year" class="quick-menu-link">This
                    year</a>
            </li>
            <li class="quick-menu-item" data-period="lifetime">
                <a href="<?= route('admin.analytics.setPeriod') ?>?period=lifetime" class="quick-menu-link">All time</a>
            </li>
        </ul>
    </div>
    <div class="block-analytics-body">
        <!-- content -->
        <div id="overview-regular"></div>
        <!-- content -->

        <!-- content -->
        <div id="overview-lifetime"></div>
        <!-- content -->
    </div>
</div>

<template id="template-analytics-overview">
    <div class="block-stat">
        <div class="block-stat-item active">
            <div class="block-stat-label">Unique Visitors</div>
            <div class="block-stat-value">{current.unique_visitors}</div>
            <div class="block-stat-indicator"><span class="indicator">{changes.unique_visitors}%</span></div>
        </div>

        <div class="block-stat-item">
            <div class="block-stat-label">Page Views</div>
            <div class="block-stat-value">{current.pageviews}</div>
            <div class="block-stat-indicator"><span class="indicator">{changes.pageviews}%</span></div>
        </div>

        <div class="block-stat-item">
            <div class="block-stat-label">Bounce Rate</div>
            <div class="block-stat-value">{current.bounce_rate}%</div>
            <div class="block-stat-indicator"><span class="indicator">{changes.bounce_rate}%</span></div>
        </div>

        <div class="block-stat-item">
            <div class="block-stat-label">Total Visits</div>
            <div class="block-stat-value">{current.total_visits}</div>
            <div class="block-stat-indicator"><span class="indicator">{changes.total_visits}%</span></div>
        </div>

        <div class="block-stat-item">
            <div class="block-stat-label">Visit Duration</div>
            <div class="block-stat-value">{current.avg_duration}</div>
            <div class="block-stat-indicator"><span class="indicator">{changes.avg_duration}%</span></div>
        </div>
    </div>
</template>

<template id="template-analytics-overview-lifetime">
    <div class="block-stat">
        <div class="block-stat-item active">
            <div class="block-stat-label">Unique Visitors</div>
            <div class="block-stat-value">{unique_visitors}</div>
        </div>

        <div class="block-stat-item">
            <div class="block-stat-label">Page Views</div>
            <div class="block-stat-value">{pageviews}</div>
        </div>

        <div class="block-stat-item">
            <div class="block-stat-label">Total Visits</div>
            <div class="block-stat-value">{total_visits}</div>
        </div>

        <div class="block-stat-item">
            <div class="block-stat-label">Total days of tracking</div>
            <div class="block-stat-value">{days_tracking}</div>
        </div>

        <div class="block-stat-item">
            <div class="block-stat-label">AVG visitors per day</div>
            <div class="block-stat-value">{avg_visitors_per_day}</div>
        </div>

        <div class="block-stat-item">
            <div class="block-stat-label">AVG visit per day</div>
            <div class="block-stat-value">{avg_visits_per_day}</div>
        </div>

        <div class="block-stat-item">
            <div class="block-stat-label">AVG page views per day</div>
            <div class="block-stat-value">{avg_pageviews_per_day}</div>
        </div>
    </div>
</template>

<script>
    (() => {
        const el = '#overview-regular';
        const lifetimeEl = '#overview-lifetime';
        const apiUrl = '<?= route('admin.analytics.overview') ?>';
        const apiUrlLifetime = '<?= route('admin.analytics.overview.lifetime') ?>';
        const templateEl = '#template-analytics-overview';
        const templateLifetimeEl = '#template-analytics-overview-lifetime';
        const logPrefix = 'Analytics Overview ';

        let loading = true;

        const adjustIndicator = (containerSelector) => {
            const $indicators = $(containerSelector).find('.indicator');

            $.each($indicators, function(index, indicator) {
                const indicatorValue = parseFloat($(indicator).html());

                if (indicatorValue > 0) {
                    $(indicator).addClass('is-upward');
                } else if (indicatorValue < 0) {
                    $(indicator).addClass('is-downward');
                }
            });
        }

        const render = (data) => {
            const template = $(templateEl).html();
            const html = parseTemplate(template, data);

            $(el).html(html).show();
            $(lifetimeEl).hide();

            adjustIndicator(el);
        }

        const renderLifetime = (data) => {
            const template = $(templateLifetimeEl).html();
            const html = parseTemplate(template, data);

            $(lifetimeEl).html(html).show();
            $(el).hide();

            adjustIndicator(lifetimeEl);
        }

        const getLifetimeDataAndRender = () => {
            $.ajax({
                url: apiUrlLifetime,
                type: 'GET',
                dataType: 'json',
            })
                .done(function(response) {
                    if (response.status === 'success') {
                        renderLifetime(response.payload);
                    } else {
                        console.error(`${logPrefix} lifetime response status failed`);
                    }
                })
                .fail(function() {
                    console.error(`${logPrefix} lifetime api request failed`);
                })
                .always(function() {
                    loading = false;
                });

        }

        const getDataAndRender = (periodName) => {
            loading = true;

            if (periodName === 'lifetime') {
                getLifetimeDataAndRender();

                return;
            }

            $.ajax({
                url: apiUrl,
                type: 'GET',
                dataType: 'json',
            })
                .done(function(response) {
                    if (response.status === 'success') {
                        render(response.payload);
                    } else {
                        console.error(`${logPrefix} response status failed`);
                    }
                })
                .fail(function() {
                    console.error(`${logPrefix} api request failed`);
                })
                .always(function() {
                    loading = false;
                });
        }

        const periodChangeHandler = () => {
            const $periodEl = $('#overview-period');
            const periodLogPrefix = 'Period selection: ';

            $periodEl.on('click', 'a', function(event) {
                const linkEl = this;
                event.preventDefault();

                $.ajax({
                    url: $(linkEl).attr('href'),
                    type: 'GET',
                    dataType: 'json'
                })
                    .done(function(response) {
                        if (response.status === 'success') {
                            getDataAndRender(response.payload.periodName);

                            $periodEl.find('.quick-menu-item').removeClass('active');
                            $(linkEl).closest('.quick-menu-item').addClass('active');
                        } else {
                            console.error(`${periodLogPrefix} api failed`);
                        }
                    })
                    .fail(function() {
                        console.error(`${periodLogPrefix} failed`);
                    });
            });
        }

        const getPeriodAndMarkActive = (callback) => {
            $.ajax({
                url: '<?= route('admin.analytics.periodName') ?>',
                type: 'GET',
                dataType: 'json'
            })
                .done(function(response) {
                    $('#overview-period').find('.quick-menu-item').removeClass('active');
                    $('#overview-period').find(`.quick-menu-item[data-period="${response.payload.name}"]`).addClass('active');

                    callback(response.payload.name);
                })
                .fail(function() {
                    console.error("Unable to fetch period");
                });
        }

        $(() => {
            getPeriodAndMarkActive((periodName) => {
                getDataAndRender(periodName);
            });

            periodChangeHandler();
        });
    })();
</script>
