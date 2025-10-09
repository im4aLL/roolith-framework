<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout<?= getUiStateByKey('compact') == 'compact' ? ' layout-compact' : null ?>" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <!-- breadcrumb -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
            <li class="breadcrumb-item">
                <a href="<?= route('admin.categories.index') ?>">Categories</a>
            </li>
            <li class="breadcrumb-item active"><?= $category->name ?></li>
        </ol>
        <!-- breadcrumb -->

        <?php
        $this->inject('admin/category/admin-category-form', [
            'form_action_url' => route('admin.categories.update', ['param' => $category->id]),
            'form_action_url_method' => 'put',
            'form_button_text' => 'Save changes',
            'form_header' => 'Edit category - ' . $category->name,
            'form_data' => $category,
        ]);
        ?>
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>
