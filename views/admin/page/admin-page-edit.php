<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <!-- breadcrumb -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= route('admin.pages.index') ?>">Pages</a></li>
            <li class="breadcrumb-item active"><?= $page->title ?></li>
        </ol>
        <!-- breadcrumb -->

        <?php
        $this->inject('admin/page/admin-page-form', [
            'form_action_url' => route('admin.pages.update', ['param' => $page->id]),
            'form_action_url_method' => 'put',
            'form_button_text' => 'Save changes',
            'form_header' => 'Edit page - ' . $page->title,
            'form_data' => $page,
            'form_data_categories' => $categories,
            'form_data_modules' => $modules,
        ]);
        ?>
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>