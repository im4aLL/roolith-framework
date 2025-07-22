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
                <input type="hidden" name="module_setting_id" value="<?= $moduleSettingData->id ?>">
                <!-- base -->
                <div class="form__col">
                    <div class="form__field">
                        <label for="title" class="form__label">Title</label>
                        <input type="text" name="title" id="title" class="form__input">
                    </div>

                    <div class="form__field">
                        <label for="hook" class="form__label">Hook</label>
                        <input type="text" name="hook" id="hook" class="form__input" value="<?= uniqid('mod-') ?>">
                    </div>
                </div>

                <div class="form__field">
                    <label for="group" class="form__label">Group (optional)</label>
                    <input type="text" name="group_name" id="group" class="form__input" list="group-name-list">
                </div>

                <datalist id="group-name-list">
                    <?php foreach ($groupNames as $groupName): ?>
                        <option value="<?= $groupName ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <div class="form__field">
                    <label for="status" class="form__label">Status</label>
                    <select name="status" id="status" class="form__input form--select">
                        <option value="draft">Draft</option>
                        <option value="published" selected>Published</option>
                    </select>
                </div>
                <!-- base -->

                <!-- generated fields -->
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
                            <input type="file" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form__input" accept="<?= $acceptedImages ?>" />
                        </div>
                    <?php elseif ($fieldType == 'image-multiple') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>
                            <input type="file" name="<?= $name ?>[]" id="<?= $fieldKey ?>" class="form__input" accept="<?= $acceptedImages ?>" multiple>
                        </div>
                    <?php elseif ($fieldType == 'file') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>
                            <input type="file" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form__input" accept="<?= $acceptedFiles ?>">
                        </div>
                    <?php elseif ($fieldType == 'file-multiple') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>
                            <input type="file" name="<?= $name ?>[]" id="<?= $fieldKey ?>" class="form__input" accept="<?= $acceptedFiles ?>" multiple>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
                <!-- generated fields -->

                <div class="form__button">
                    <button class="button" type="submit">Save</button>
                    <div id="error-container" class="form__general-error"></div>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
