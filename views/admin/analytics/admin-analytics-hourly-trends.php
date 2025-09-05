<div class="block-analytics">
    <!-- block analytics header -->
    <div class="block-analytics-header">
        <h6 class="block-analytics-hl">Hourly Trends</h6>

        <div class="form-field form-datepicker form-datepicker-js small">
            <input type="text" class="form-input" id="hourly-trend-datepicker-js" />
        </div>
    </div>
    <!-- block analytics header -->

    <!-- block analytics body -->
    <div class="block-analytics-body">
        <canvas id="hourlyChart"></canvas>
    </div>
    <!-- block analytics body -->
</div>

<script src="https://cdn.jsdelivr.net/npm/js-datepicker@5.18.4/dist/datepicker.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/js-datepicker@5.18.4/dist/datepicker.min.css" rel="stylesheet">

<script>
    (() => {
        const logPrefix = 'Analytics hourly trends ';
        const hourlyData = [];

        // Prepare labels and datasets
        const labels = hourlyData.map(d => d.hour + ':00');
        const pageviews = hourlyData.map(d => d.pageviews);
        const visitors = hourlyData.map(d => d.unique_visitors);
        const visits = hourlyData.map(d => d.visits);

        const ctx = document.getElementById('hourlyChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line', // can change to 'bar' if you prefer
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pageviews',
                    data: pageviews,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 1
                },
                {
                    label: 'Unique Visitors',
                    data: visitors,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 1
                },
                {
                    label: 'Visits',
                    data: visits,
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Hourly Traffic for Last 24 Hours'
                    },
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hour'
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Count'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        function formatDate(dateString) {
            const date = new Date(dateString.replace(' ', 'T'));

            return `${date.toLocaleDateString('en-US', { weekday: 'long' })}, ${date.toLocaleDateString('en-US', { month: 'long' })} ${date.getDate()}, ${date.getFullYear()}`;
        }


        function updateChart(payload) {
            const {
                data
            } = payload;

            chart.options.plugins.title.text = formatDate(payload.date);

            chart.data.labels = data.map(d => d.hour + ':00');
            chart.data.datasets[0].data = data.map(d => d.pageviews);
            chart.data.datasets[1].data = data.map(d => d.unique_visitors);
            chart.data.datasets[2].data = data.map(d => d.visits);
            chart.update();
        }

        function getData(data = {}) {
            $.ajax({
                url: '<?= route('admin.analytics.hourlyTrends') ?>',
                type: 'GET',
                dataType: 'json',
                data
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
            const datePicker = datepicker('#hourly-trend-datepicker-js', {
                position: 'br',
                onSelect: (instance, date) => {
                    const data = date.toISOString().slice(0, 10);
                    getData({ date: data });
                },
                dateSelected: new Date(),
            });

            getData();
        });
    })();
</script>
