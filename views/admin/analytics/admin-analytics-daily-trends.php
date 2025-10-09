<div class="block-analytics">
    <!-- block analytics header -->
    <div class="block-analytics-header">
        <h6 class="block-analytics-hl">Daily Trends</h6>
    </div>
    <!-- block analytics header -->

    <!-- block analytics body -->
    <div class="block-analytics-body">
        <canvas id="trafficChart"></canvas>
    </div>
    <!-- block analytics body -->
</div>

<script>
    (() => {
        const logPrefix = 'Analytics daily trends ';
        const data = [];

        // Prepare data
        const labels = data.map(d => d.date);
        const pageviews = data.map(d => d.pageviews);
        const visitors = data.map(d => d.unique_visitors);
        const visits = data.map(d => d.visits);

        const ctx = document.getElementById('trafficChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Pageviews',
                        data: pageviews,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        borderWidth: 1,
                        tension: 0.4
                    },
                    {
                        label: 'Unique Visitors',
                        data: visitors,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        fill: true,
                        borderWidth: 1,
                        tension: 0.4
                    },
                    {
                        label: 'Visits',
                        data: visits,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        fill: true,
                        borderWidth: 1,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Website Traffic (Daily)'
                    }
                },
                interaction: {
                    intersect: false,
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        function updateChart(payload) {
            const {
                data
            } = payload;

            chart.data.labels = data.map(d => d.date);
            chart.data.datasets[0].data = data.map(d => d.pageviews);
            chart.data.datasets[1].data = data.map(d => d.unique_visitors);
            chart.data.datasets[2].data = data.map(d => d.visits);

            chart.update();
        }

        function getData() {
            $.ajax({
                    url: '<?= route('admin.analytics.dailyTrends') ?>',
                    type: 'GET',
                    dataType: 'json',
                })
                .done(function(response) {
                    if (response.status === 'success') {
                        updateChart(response.payload);
                    } else {
                        console.error(`${logPrefix} api failed`);
                    }
                })
                .fail(function() {
                    console.error(`${logPrefix} failed`);
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