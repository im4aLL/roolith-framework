<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout<?= getUiStateByKey('compact') == 'compact' ? ' layout-compact' : null ?>" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">

        <!-- block banner -->
        <div class="block-banner">
            <div class="box box-large">
                <div class="box-body">
                    <h5 class="block-banner-title">Welcome to roolith CMS!</h5>
                    <p class="block-banner-text">
                        Start creating your first module, or manage your existing modules. Its easy! Then you can
                        start creating page content and upload media files.
                    </p>

                    <div class="button-bundle block-banner-buttons">
                        <a href="<?= route('admin.pages.create') ?>" class="button button-primary">Get Started</a>
                        <a href="<?= route('admin.analytics.index') ?>"
                            class="button button-text button-primary button-has-icon">
                            See metrics
                            <i class="iconoir-open-new-window"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- block banner -->

        <div class="spacer-20"></div>

        <p>You have last logged in at
            <?= diffForHumans($lastLoggedIn) ?>,
            <?= toDateTimeString($lastLoggedIn) ?>
        </p>

        <div class="spacer-20"></div>

        <!-- block box -->
        <div class="block-boxes">
            <a href="<?= route('admin.pages.index') ?>?filter[status]=published" class="box block-box">
                <span class="block-box-hl">
                    <?= $pageCount->published_count ?? 0 ?>
                </span>
                <i class="iconoir-multiple-pages block-box-icon"></i>
                <span class="block-box-title">Published Pages</span>
            </a>

            <a href="<?= route('admin.pages.index') ?>?filter[status]=draft" class="box block-box">
                <span class="block-box-hl">
                    <?= $pageCount->draft_count ?? 0 ?>
                </span>
                <i class="iconoir-page-minus block-box-icon"></i>
                <span class="block-box-title">Unpublished Pages</span>
            </a>

            <a href="<?= route('admin.modules.index') ?>?filter[status]=published" class="box block-box">
                <span class="block-box-hl">
                    <?= $moduleCount->published_count ?? 0 ?>
                </span>
                <i class="iconoir-xray-view block-box-icon"></i>
                <span class="block-box-title">Published Modules</span>
            </a>

            <a href="<?= route('admin.modules.index') ?>?filter[status]=draft" class="box block-box">
                <span class="block-box-hl">
                    <?= $moduleCount->draft_count ?? 0 ?>
                </span>
                <i class="iconoir-cube-dots block-box-icon"></i>
                <span class="block-box-title">Unpublished Modules</span>
            </a>

            <a href="<?= route('admin.module-settings.index') ?>" class="box block-box">
                <span class="block-box-hl">
                    <?= $moduleSettingsCount->total ?>
                </span>
                <i class="iconoir-rubik-cube block-box-icon"></i>
                <span class="block-box-title">Module Settings</span>
            </a>

            <a href="<?= route('admin.categories.index') ?>" class="box block-box">
                <span class="block-box-hl">
                    <?= $categoryCount->total ?>
                </span>
                <i class="iconoir-keyframes block-box-icon"></i>
                <span class="block-box-title">Categories</span>
            </a>

            <a href="<?= route('admin.messages.index') ?>" class="box block-box">
                <span class="block-box-hl">
                    <?= $unreadMessageCount->total ?>
                </span>
                <i class="iconoir-message block-box-icon"></i>
                <span class="block-box-title">Unread Messages</span>
            </a>
        </div>
        <!-- block box -->

        <div class="spacer-20"></div>

        <!-- block step -->
        <div class="block-banner">
            <div class="box box-large">
                <div class="box-body">
                    <h5 class="block-banner-title">Steps to create your page!</h5>
                    <p class="block-banner-text">
                        Start creating your first module, or manage your existing modules. Its easy! Then you can
                        start creating page content and upload media files.
                    </p>

                    <!-- Steps -->
                    <div class="block-banner-steps">
                        <div class="block-banner-step">
                            <span class="badge badge-secondary">Step 1</span>
                            <h6 class="block-banner-step-title">Create a module settings</h6>
                            <p class="block-banner-step-text">Create a module settings for your module</p>

                            <a href="<?= route('admin.module-settings.index') ?>"
                                class="button button-secondary block-banner-step-button">
                                Add Module Settings
                            </a>
                        </div>

                        <div class="block-banner-step">
                            <span class="badge badge-success">Step 2</span>
                            <h6 class="block-banner-step-title">Create one or multiple module</h6>
                            <p class="block-banner-step-text">Create modules based on your module settings</p>

                            <a href="<?= route('admin.modules.index') ?>"
                                class="button button-success block-banner-step-button">Add Module</a>
                        </div>

                        <div class="block-banner-step">
                            <span class="badge badge-primary">Step 3</span>
                            <h6 class="block-banner-step-title">Create a page</h6>
                            <p class="block-banner-step-text">
                                Create a page based on your module and order your modules
                            </p>

                            <a href="<?= route('admin.pages.index') ?>"
                                class="button button-primary block-banner-step-button">Create Page</a>
                        </div>
                    </div>
                    <!-- Steps -->
                </div>
            </div>
        </div>
        <!-- block step -->

    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>
