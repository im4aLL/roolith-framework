<?php $this->inject('admin/partials/admin-header'); ?>

<main class="layout">

    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3><?= $this->escape('title') ?></h3>

        <!-- fields -->
        <div class="form__field" id="site-settings-field-container" data-action-url="<?= route('admin.siteSettings.store') ?>">
            <ul class="form__list">
                <?php foreach ($settingsData as $row) : ?>
                <li class="form__list-item" data-id="<?= $row->id ?>">
                    <input type="text" name="item" class="form__input form__input--no-space" placeholder="item" value="<?= $row->item ?>">
                    <input type="text" name="value" class="form__input form__input--no-space" placeholder="value" value="<?= $row->value ?>">

                    <div class="form__list-item-action">
                        <button class="button button--text button--danger form__delete-cta" type="button">Remove</button>
                    </div>
                </li>
                <?php endforeach; ?>
                <li class="form__list-item">
                    <input type="text" name="item" class="form__input form__input--no-space" placeholder="item">
                    <input type="text" name="value" class="form__input form__input--no-space" placeholder="value">

                    <div class="form__list-item-action">
                        <button class="button button--text button--danger form__delete-cta" type="button">Remove</button>
                    </div>
                </li>
            </ul>
            <button class="button button--text" type="button" id="add-field">Add new</button>
        </div>
        <!-- fields -->
    </section>

</main>

<?php $this->inject('admin/partials/admin-footer') ?>
