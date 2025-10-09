<div class="block-analytics">
    <!-- block analytics header -->
    <div class="block-analytics-header">
        <h6 class="block-analytics-hl">Top Sources</h6>
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
            <div class="block-stat-table-body" id="analytics-top-sources-body"></div>
        </div>
    </div>
    <!-- block analytics body -->
</div>

<template id="analytics-top-sources-row">
    <div class="block-stat-table-row">
        <div class="block-stat-table-cell is-left is-primary">
            <div class="block-stat-table-text">{source}</div>
            <div class="block-stat-table-indicator" style="width: {width}%"></div>
        </div>
        <div class="block-stat-table-cell">{visits}</div>
        <div class="block-stat-table-cell">{unique_visitors}</div>
    </div>
</template>

<script>
    (() => {
        const logPrefix = 'Analytics top sources ';

        function render(payload) {
            const {
                data
            } = payload;

            const maxValue = Math.max(...data.map(row => row.visits));
            const template = $('#analytics-top-sources-row').html();
            let html = '';

            $.each(data, function(index, row) {
                row.width = maxValue > 0 ? row.visits / maxValue * 100 : 0;
                html += parseTemplate(template, row);
            });

            $('#analytics-top-sources-body').html(html);
        }

        function getData() {
            $.ajax({
                url: '<?= route('admin.analytics.topSources') ?>',
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
