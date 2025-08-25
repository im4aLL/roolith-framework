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

        <!-- content -->
        <div class="form-field" id="site-settings-field-container" data-action-url="<?= route('admin.siteSettings.store') ?>">
            <div class="block-repeater">
                <label class="block-repeater-label form-label">Custom Fields</label>

                <ul class="block-repeater-list">
                    <?php foreach ($settingsData as $row) : ?>
                        <li class="block-repeater-item" data-id="<?= $row->id ?>">
                            <button class="block-repeater-sort">⋮⋮</button>
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
                                <button class="button button-outline button-danger button-icon js-alt-remove-field" type="button">
                                    <i class="iconoir-xmark"></i>
                                    Remove
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <li class="block-repeater-item">
                        <button class="block-repeater-sort">⋮⋮</button>
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
                            <button class="button button-outline button-danger button-icon js-alt-remove-field" type="button">
                                <i class="iconoir-xmark"></i>
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
        </div>
        <!-- content -->
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>
