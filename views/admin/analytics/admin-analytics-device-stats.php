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
            <div class="block-stat-table-text analytics-logo-n-text">
                <img src="{imageLink}" alt="{type} logo" height="16" width="16">
                {type}
            </div>
            <div class="block-stat-table-indicator" style="width: {width}%"></div>
        </div>
        <div class="block-stat-table-cell">{pageviews}</div>
        <div class="block-stat-table-cell">{unique_visitors}</div>
    </div>
</template>

<script>
    (() => {
        const browserLogo = {
            safari: "https://static.cdnlogo.com/logos/s/81/safari.svg",
            opera: "https://static.cdnlogo.com/logos/o/37/opera.svg",
            edge: "https://static.cdnlogo.com/logos/m/87/microsoft-edge.svg",
            firefox: "https://static.cdnlogo.com/logos/f/7/firefox.svg",
            chrome: "https://static.cdnlogo.com/logos/c/72/chrome.svg",
            brave: "https://static.cdnlogo.com/logos/b/65/brave.svg",
            default: "https://static.cdnlogo.com/logos/i/1/internet-explorer.svg"
        };

        const osLogo = {
            linux: "https://static.cdnlogo.com/logos/l/11/linux.svg",
            ios: "https://static.cdnlogo.com/logos/a/36/apple.svg",
            macos: "https://static.cdnlogo.com/logos/a/30/apple.svg",
            android: "https://static.cdnlogo.com/logos/a/92/android.svg",
            windows: "https://static.cdnlogo.com/logos/m/71/microsoft-windows-22.svg",
            default: "https://static.cdnlogo.com/logos/l/11/linux.svg",
        };

        const deviceLogo = {
            tablet: "https://api.iconify.design/mdi:tablet.svg?color=%233b82f6",
            mobile: "https://api.iconify.design/mdi:cellphone.svg?color=%233b82f6",
            desktop: "https://api.iconify.design/mdi:monitor.svg?color=%233b82f6",
            default: "https://api.iconify.design/mdi:monitor.svg?color=%233b82f6",
        };

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

                    const browserName = row.type.toLowerCase();
                    row.imageLink = browserLogo[browserName] ? browserLogo[browserName] : browserLogo['default'];
                } else if (row.os) {
                    row.type = row.os;

                    const osName = row.type.toLowerCase();
                    row.imageLink = osLogo[osName] ? osLogo[osName] : osLogo['default'];
                } else if (row.device) {
                    row.type = row.device;

                    const deviceName = row.type.toLowerCase();
                    row.imageLink = deviceLogo[deviceName] ? deviceLogo[deviceName] : deviceLogo['default'];
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

            Event.listen('periodChange', () => {
                getData();
            });
        });
    })();
</script>
