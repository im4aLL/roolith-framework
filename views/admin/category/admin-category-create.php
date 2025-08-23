<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <!-- breadcrumb -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
            <li class="breadcrumb-item">
                <a href="<?= route('admin.categories.index') ?>">Categories</a>
            </li>
            <li class="breadcrumb-item active">Add new</li>
        </ol>
        <!-- breadcrumb -->

        <?php
        $this->inject('admin/category/admin-category-form', [
            'form_action_url' => route('admin.categories.store'),
            'form_action_url_method' => 'post',
            'form_button_text' => 'Add category',
            'form_header' => 'Add new category',
            'form_data' => null,
        ]);
        ?>
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>