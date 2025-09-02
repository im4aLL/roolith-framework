<div class="block-analytics">
    <!-- block analytics header -->
    <div class="block-analytics-header">
        <h6 class="block-analytics-hl">Location Stats</h6>
    </div>
    <!-- block analytics header -->

    <!-- block analytics body -->
    <div class="block-analytics-body">
        <?php $this->inject('admin/analytics/admin-analytics-map') ?>
    </div>
    <!-- block analytics body -->
</div>

<script>
    (() => {
        const colorShade = [
            '#92bafa',
            '#7cacf9',
            '#679ef8',
            '#5190f7',
            '#3b82f6',
            '#2e65bf',
            '#2757a4',
            '#214889',
            '#1a3a6d',
            '#142b52',
        ];
        const logPrefix = 'Analytics location stats ';

        function render(payload) {
            const {
                data
            } = payload;

            const uniqueNumbers = [...data].reduce((acc, curr) => {
                if (!acc.includes(curr.pageviews)) {
                    acc.push(curr.pageviews);
                }

                return acc;
            }, []);
            uniqueNumbers.sort((a, b) => a - b);

            $.each(data, (index, country) => {
                const numberIndex = uniqueNumbers.indexOf(country.pageviews);
                const color = colorShade[numberIndex];

                $('#svg-world-map path[data-id="' + country.country + '"]').css({
                    fill: color,
                }).attr('data-pageviews', country.pageviews).attr('data-unique-visitors', country.unique_visitors).attr('data-visits', country.visits);
            });
        }

        function getData() {
            $.ajax({
                    url: '<?= route('admin.analytics.locationStats') ?>',
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
        });
    })();
</script>