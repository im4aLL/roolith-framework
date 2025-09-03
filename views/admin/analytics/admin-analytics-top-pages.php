<div class="block-analytics">
    <!-- block analytics header -->
    <div class="block-analytics-header">
        <h6 class="block-analytics-hl">Top Pages</h6>
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
            <div class="block-stat-table-body" id="analytics-top-pages-body"></div>
        </div>
    </div>
    <!-- block analytics body -->
</div>

<template id="analytics-top-pages-row">
    <div class="block-stat-table-row">
        <div class="block-stat-table-cell is-left is-primary">
            <a href="{link}" target="_blank" class="block-stat-table-text">{page_url}</a>
            <div class="block-stat-table-indicator" style="width: {width}%"></div>
        </div>
        <div class="block-stat-table-cell">{pageviews}</div>
        <div class="block-stat-table-cell">{unique_visitors}</div>
    </div>
</template>

<script>
    (() => {
        const logPrefix = 'Analytics top pages ';

        function render(payload) {
            const {
                data
            } = payload;

            const maxValue = Math.max(...data.map(row => row.pageviews));
            const template = $('#analytics-top-pages-row').html();
            const baseUrl = '<?= url('') ?>';
            let html = '';

            $.each(data, function(index, row) {
                row.width = maxValue > 0 ? row.pageviews / maxValue * 100 : 0;
                row.link = baseUrl + row.page_url.substring(1);
                html += parseTemplate(template, row);
            });

            $('#analytics-top-pages-body').html(html);
        }

        function getData() {
            $.ajax({
                url: '<?= route('admin.analytics.topPages') ?>',
                type: 'GET',
                dataType: 'json',
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

        $(() => {
            getData();

            Event.listen('periodChange', () => {
                getData();
            });
        });
    })();
</script>
