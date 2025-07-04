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
                <a href="<?= route('admin.pages.show', ['param' => $page->id]) ?>" class="breadcrumb__link"><?= $page->title ?></a>
            </li>
        </ul>

        <?php
            $this->inject('admin/page/admin-page-form', [
                'form_action_url' => route('admin.pages.update', ['param' => $page->id]),
                'form_action_url_method' => 'put',
                'form_button_text' => 'Save changes',
                'form_header' => 'Edit page - ' .$page->title,
                'form_data' => $page,
                'form_data_categories' => $categories,
            ]);
        ?>
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
