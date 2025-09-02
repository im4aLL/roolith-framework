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
                <li class="breadcrumb-item active">Site settings</li>
            </ol>
            <!-- breadcrumb -->

            <!-- block header container -->
            <div class="block-header-container">
                <div class="block-header-primary">
                    <h5 class="block-header-title"><?= $this->escape('title') ?></h5>
                    <p class="block-header-subtitle">Custom site settings and configurations</p>
                </div>
            </div>
            <!-- block header container -->
        </div>

        <!-- Analytics -->
        <div class="block-enable" data-action-url="<?= route('admin.siteSettings.toggle') ?>">
            <div class="box">
                <div class="box-header">Enable or disable Analytics</div>
                <div class="box-body">
                    By enabling analytics, you can view key statistics like total visitors, trends over time, top pages, top sources, visitor locations, and device types. You can also customize the time period to analyze the data that matters most.
                </div>
                <div class="box-footer">
                    <div class="form-switch">
                        <label for="enable" class="form-switch-label">
                            <input type="checkbox" name="enable-analytics" id="enable" class="form-switch-input" <?= $analyticsFeature && $analyticsFeature->value == 'true' ? 'checked' : '' ?> />
                            <span class="form-switch-slider"></span>

                            <span class="form-switch-slider-text">Enable</span>
                        </label>
                    </div>

                    <p class="dimmed">Last updated <strong><?= $analyticsFeature && $analyticsFeature->updated_at ? diffForHumans($analyticsFeature->updated_at) : 'N/A' ?></strong></p>
                </div>
            </div>
        </div>
        <!-- Analytics -->

        <div class="spacer-20"></div>

        <!-- content -->
        <div class="form-field" id="site-settings-field-container" data-action-url="<?= route('admin.siteSettings.store') ?>">

            <!-- block repeater -->
            <div class="block-repeater">
                <label class="block-repeater-label form-label">Custom Fields</label>

                <ul class="block-repeater-list">
                    <?php foreach ($settingsData as $row) : ?>
                        <li class="block-repeater-item" data-id="<?= $row->id ?>">
                            <div class="block-repeater-primary">
                                <div class="form-field">
                                    <label class="form-label">Item</label>
                                    <input type="text" class="form-input" name="item" value="<?= $row->item ?>" placeholder="item" />
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Value</label>
                                    <input type="text" class="form-input" name="value" value="<?= $row->value ?>" placeholder="value" />
                                </div>
                            </div>
                            <div class="block-repeater-secondary">
                                <button class="button button-outline button-danger button-text js-alt-remove-field" type="button">
                                    Remove
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <li class="block-repeater-item">
                        <div class="block-repeater-primary">
                            <div class="form-field">
                                <label class="form-label">Item</label>
                                <input type="text" class="form-input" name="item" placeholder="item" />
                            </div>
                            <div class="form-field">
                                <label class="form-label">Value</label>
                                <input type="text" class="form-input" name="value" placeholder="value" />
                            </div>
                        </div>
                        <div class="block-repeater-secondary">
                            <button class="button button-outline button-danger button-text js-alt-remove-field" type="button">
                                Remove
                            </button>
                        </div>
                    </li>
                </ul>

                <button class="button button-icon js-add-field" type="button">
                    <i class="iconoir-plus"></i>
                    Add More Fields
                </button>
            </div>
            <!-- block repeater -->



        </div>
        <!-- content -->
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>