<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout<?= getUiStateByKey('compact') == 'compact' ? ' layout-compact' : null ?>" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <!-- breadcrumb -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= route('admin.pages.index') ?>">Pages</a></li>
            <li class="breadcrumb-item active">Add new</li>
        </ol>
        <!-- breadcrumb -->

        <?php
        $this->inject('admin/page/admin-page-form', [
            'form_action_url' => route('admin.pages.store'),
            'form_action_url_method' => 'post',
            'form_button_text' => 'Add page',
            'form_header' => 'Add new page',
            'form_data' => null,
            'form_data_categories' => $categories,
            'form_data_modules' => $modules,
        ]);
        ?>
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>
