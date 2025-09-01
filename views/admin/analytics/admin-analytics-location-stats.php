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
        function example() {

        }

        $(() => {
            example();

            $('#AF').css('fill', 'red');
        });
    })();
</script>
