<div class="block-analytics">
    <!-- block analytics header -->
    <div class="block-analytics-header">
        <h6 class="block-analytics-hl">Device Stats</h6>

        <ul class="quick-menu small" id="analytics-device-stats-type">
            <li class="quick-menu-item active">
                <a href="#" class="quick-menu-link" data-by="browser">Browser</a>
            </li>
            <li class="quick-menu-item">
                <a href="#" class="quick-menu-link" data-by="os">OS</a>
            </li>
            <li class="quick-menu-item">
                <a href="#" class="quick-menu-link" data-by="size">Size</a>
            </li>
        </ul>
    </div>
    <!-- block analytics header -->

    <!-- block analytics body -->
    <div class="block-analytics-body">
        <div class="block-stat-table">
            <div class="block-stat-table-head">
                <div class="block-stat-table-header is-left is-primary">Page URLs</div>
                <div class="block-stat-table-header">Page Views</div>
                <div class="block-stat-table-header">Unique Visitor</div>
            </div>
            <div class="block-stat-table-body" id="analytics-device-stats-body"></div>
        </div>
    </div>
    <!-- block analytics body -->
</div>

<template id="analytics-device-stats-row">
    <div class="block-stat-table-row">
        <div class="block-stat-table-cell is-left is-primary">
            <a href="" class="block-stat-table-text">{type}</a>
            <div class="block-stat-table-indicator" style="width: {width}%"></div>
        </div>
        <div class="block-stat-table-cell">{pageviews}</div>
        <div class="block-stat-table-cell">{unique_visitors}</div>
    </div>
</template>

<script>
    (() => {
        const logPrefix = 'Analytics devices stats ';

        function render(payload) {
            const {
                data
            } = payload;

            const maxValue = Math.max(...data.map(row => row.pageviews));
            const template = $('#analytics-device-stats-row').html();
            let html = '';

            $.each(data, function(index, row) {
                row.width = maxValue > 0 ? row.pageviews / maxValue * 100 : 0;

                if (row.browser) {
                    row.type = row.browser;
                } else if (row.os) {
                    row.type = row.os;
                } else if (row.device) {
                    row.type = row.device;
                }

                html += parseTemplate(template, row);
            });

            $('#analytics-device-stats-body').html(html);
        }

        function getData(settings = {
            by: 'browser'
        }) {
            $.ajax({
                    url: '<?= route('admin.analytics.deviceStats') ?>',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        by: settings.by
                    }
                })
                .done(function(response) {
                    if (response.status === 'success') {
                        render(response.payload);
                    } else {
                        console.error(`$(logPrefix) api failed`);
                    }
                })
                .fail(function() {
                    console.error(`$(logPrefix) failed`);
                });
        }

        function differentDeviceStatTypeHandler() {
            const $el = $('#analytics-device-stats-type');

            $el.on('click', '.quick-menu-link', function(event) {
                event.preventDefault();

                $el.find('.quick-menu-item').removeClass('active');
                $(this).parent().addClass('active');

                getData({
                    by: $(this).data('by')
                });
            });
        }

        $(() => {
            getData();
            differentDeviceStatTypeHandler();
        });
    })();
</script>