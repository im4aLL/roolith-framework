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

        <div class="layout__header">
            <div>
                <h3>Add new module</h3>
            </div>

            <button data-url="<?= route('admin.modules.destroy', ['param' => 1]) ?>" class="button button--danger button--text" id="delete-button">Delete permanently</button>
        </div>

        <?php if (isset($setting_load_error_message)) : ?>
            <div class="message message--danger"><?= $setting_load_error_message ?></div>
        <?php endif; ?>

        <div class="form__actions">
            <form method="get" class="form form--inline">
                <select name="module_setting_id" class="form__input form--select">
                    <option></option>
                    <?php foreach ($moduleSettings as $moduleSetting): ?>
                        <option value="<?= $moduleSetting->id ?>" <?= isset($moduleSettingData) && $moduleSettingData->id == $moduleSetting->id ? 'selected' : '' ?>><?= $moduleSetting->name ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button">Load selected module</button>
            </form>
        </div>

        <?php if (isset($moduleSettingData)) : ?>
            <form action="<?= route('admin.modules.store') ?>" method="post" class="form" enctype="multipart/form-data" data-ajax="true">
                <?php foreach ($moduleSettingData->settings->name as $index => $name) : ?>
                    <?php
                        $fieldKey = \App\Utils\_::toCamelCase($name);
                        $fieldType = $moduleSettingData->settings->type[$index];
                        $fieldLabel = \App\Utils\_::toTitleCase($name);
                    ?>

                    <?php if ($fieldType == 'text') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>
                            <input type="text" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form__input">
                        </div>
                    <?php elseif ($fieldType == 'textarea') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>
                            <textarea name="<?= $name ?>" id="<?= $fieldKey ?>" class="form__input form--textarea" rows="8"></textarea>
                        </div>
                    <?php elseif ($fieldType == 'rich-text') : ?>
                        <div class="form__field form__field--editor">
                            <label class="form__label"><?= $fieldLabel ?></label>
                            <div class="form__editor"></div>
                            <div class="form__editor-value" data-input-name="<?= $name ?>" style="display: none;"></div>
                        </div>
                    <?php elseif ($fieldType == 'image') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>
                            <input type="file" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form__input" accept=".jpg,.jpeg,.png">
                        </div>
                    <?php elseif ($fieldType == 'image-multiple') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>
                            <input type="file" name="<?= $name ?>[]" id="<?= $fieldKey ?>" class="form__input" accept=".jpg,.jpeg,.png" multiple>
                        </div>
                    <?php elseif ($fieldType == 'file') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>
                            <input type="file" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form__input" accept=".pdf,.doc,.docx,.zip,.xls,.xlsx,.csv,.ppt,.pptx">
                        </div>
                    <?php elseif ($fieldType == 'file-multiple') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>
                            <input type="file" name="<?= $name ?>[]" id="<?= $fieldKey ?>" class="form__input" accept=".pdf,.doc,.docx,.zip,.xls,.xlsx,.csv,.ppt,.pptx" multiple>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>

                <div class="form__button">
                    <button class="button" type="submit">Save</button>
                    <div id="error-container" class="form__general-error"></div>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
