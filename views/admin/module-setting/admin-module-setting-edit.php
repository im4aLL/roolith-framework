<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout<?= getUiStateByKey('compact') == 'compact' ? ' layout-compact' : null ?>" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <!-- breadcrumb -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= route('admin.module-settings.index') ?>">Module settings</a></li>
            <li class="breadcrumb-item active"><?= $moduleSetting->name ?></li>
        </ol>
        <!-- breadcrumb -->

        <!-- content -->
        <?php
        $this->inject('admin/module-setting/admin-module-setting-form', [
            'form_action_url' => route('admin.module-settings._update', ['param' => $moduleSetting->id]),
            'form_action_url_method' => 'post',
            'form_button_text' => 'Save changes',
            'form_header' => 'Edit module setting - ' . $moduleSetting->name,
            'form_data' => $moduleSetting,
        ]);
        ?>
        <!-- content -->
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>
