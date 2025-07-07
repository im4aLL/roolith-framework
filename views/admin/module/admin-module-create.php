<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <ul class="breadcrumb">
            <li class="breadcrumb__item">
                <a href="<?= route('admin.modules.index') ?>" class="breadcrumb__link">Module</a>
            </li>
            <li class="breadcrumb__item">
                <a href="<?= route('admin.modules.create') ?>" class="breadcrumb__link">Add new</a>
            </li>
        </ul>

        <?php
            $this->inject('admin/module/admin-module-form', [
                'form_action_url' => route('admin.modules.store'),
                'form_action_url_method' => 'post',
                'form_button_text' => 'Add module',
                'form_header' => 'Add new module',
                'form_data' => null,
                'form_data_pages' => $pages,
            ]);
        ?>
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
