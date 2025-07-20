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
                <a href="<?= route('admin.categories.edit', ['param' => $category->id]) ?>" class="breadcrumb__link"><?= $category->name ?></a>
            </li>
        </ul>

        <?php
            $this->inject('admin/category/admin-category-form', [
                'form_action_url' => route('admin.categories.update', ['param' => $category->id]),
                'form_action_url_method' => 'put',
                'form_button_text' => 'Save changes',
                'form_header' => 'Edit category - ' . $category->name,
                'form_data' => $category,
            ]);
        ?>
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
