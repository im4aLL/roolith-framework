<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <ul class="breadcrumb">
            <li class="breadcrumb__item">
                <a href="<?= route('admin.categories.index') ?>" class="breadcrumb__link">Categories</a>
            </li>
            <li class="breadcrumb__item">
                <a href="<?= route('admin.categories.create') ?>" class="breadcrumb__link">Add new</a>
            </li>
        </ul>

        <?php
            $this->inject('admin/category/admin-category-form', [
                'form_action_url' => route('admin.categories.store'),
                'form_action_url_method' => 'post',
                'form_button_text' => 'Add category',
                'form_header' => 'Add new category',
                'form_data' => null,
            ]);
        ?>
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
