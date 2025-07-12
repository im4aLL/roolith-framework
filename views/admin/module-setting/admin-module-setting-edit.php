<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <ul class="breadcrumb">
            <li class="breadcrumb__item">
                <a href="<?= route('admin.module-settings.index') ?>" class="breadcrumb__link">Module Settings</a>
            </li>
            <li class="breadcrumb__item">
                <a href="<?= route('admin.module-settings.edit', ['param' => $moduleSetting->id]) ?>" class="breadcrumb__link"><?= $moduleSetting->name ?></a>
            </li>
        </ul>

        <?php
            $this->inject('admin/module-setting/admin-module-setting-form', [
                'form_action_url' => route('admin.module-settings._update', ['param' => $moduleSetting->id]),
                'form_action_url_method' => 'post',
                'form_button_text' => 'Save changes',
                'form_header' => 'Edit module setting - '.$moduleSetting->name,
                'form_data' => $moduleSetting,
            ]);
        ?>
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
