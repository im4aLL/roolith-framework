<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <ul class="breadcrumb">
            <li class="breadcrumb__item">
                <a href="<?= route('admin.pages.index') ?>" class="breadcrumb__link">Pages</a>
            </li>
            <li class="breadcrumb__item">
                <a href="<?= route('admin.pages.create') ?>" class="breadcrumb__link">Add new</a>
            </li>
        </ul>

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
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
