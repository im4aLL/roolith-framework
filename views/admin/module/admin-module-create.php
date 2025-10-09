<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout<?= getUiStateByKey('compact') == 'compact' ? ' layout-compact' : null ?>" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <!-- breadcrumb -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= route('admin.modules.index') ?>">Modules</a></li>
            <li class="breadcrumb-item active">Add new</li>
        </ol>
        <!-- breadcrumb -->

        <div class="spacer-20"></div>

        <!-- content -->
        <?php if (isset($setting_load_error_message)) : ?>
            <div class="message message-danger"><?= $setting_load_error_message ?></div>
        <?php endif; ?>

        <!-- actions -->
        <form method="get" class="form block-inline-form">
            <div class="form-field">
                <label for="module_setting_id" class="form-label">Select a module settings</label>
                <select name="module_setting_id" id="module_setting_id" class="form-select">
                    <option></option>
                    <?php foreach ($moduleSettings as $moduleSetting): ?>
                        <option value="<?= $moduleSetting->id ?>" <?= isset($moduleSettingData) && $moduleSettingData->id == $moduleSetting->id ? 'selected' : '' ?>><?= $moduleSetting->name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <button type="submit" class="button">Load selected module</button>
            </div>
        </form>
        <!-- actions -->

        <div class="spacer-20"></div>

        <?php if (isset($moduleSettingData)) : ?>
            <form action="<?= route('admin.modules.store') ?>" method="post" class="form" enctype="multipart/form-data" data-ajax="true">
                <input type="hidden" name="module_setting_id" value="<?= $moduleSettingData->id ?>">
                <!-- base -->
                <div class="form-field">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" name="title" id="title" class="form-input">
                </div>

                <div class="form-field">
                    <label for="hook" class="form-label">Hook</label>
                    <input type="text" name="hook" id="hook" class="form-input" value="<?= uniqid('mod-') ?>">
                </div>

                <div class="form-field">
                    <label for="group" class="form-label">Group (optional)</label>
                    <input type="text" name="group_name" id="group" class="form-input" list="group-name-list">
                </div>

                <datalist id="group-name-list">
                    <?php foreach ($groupNames as $groupName): ?>
                        <option value="<?= $groupName ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <div class="form-field">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
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
                        <div class="form-field">
                            <label for="<?= $fieldKey ?>" class="form-label"><?= $fieldLabel ?></label>
                            <input type="text" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form-input">
                        </div>
                    <?php elseif ($fieldType == 'textarea') : ?>
                        <div class="form-field">
                            <label for="<?= $fieldKey ?>" class="form-label"><?= $fieldLabel ?></label>
                            <textarea name="<?= $name ?>" id="<?= $fieldKey ?>" class="form-input form--textarea" rows="8"></textarea>
                        </div>
                    <?php elseif ($fieldType == 'rich-text') : ?>
                        <div class="form-field form-field--editor">
                            <label class="form-label"><?= $fieldLabel ?></label>
                            <div class="form__editor"></div>
                            <div class="form__editor-value" data-input-name="<?= $name ?>" style="display: none;"></div>
                        </div>
                    <?php elseif ($fieldType == 'image') : ?>
                        <div class="form-field">
                            <label for="<?= $fieldKey ?>" class="form-label"><?= $fieldLabel ?></label>
                            <input type="file" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form-file" accept="<?= $acceptedImages ?>" />
                        </div>
                    <?php elseif ($fieldType == 'image-multiple') : ?>
                        <div class="form-field">
                            <label for="<?= $fieldKey ?>" class="form-label"><?= $fieldLabel ?></label>
                            <input type="file" name="<?= $name ?>[]" id="<?= $fieldKey ?>" class="form-file" accept="<?= $acceptedImages ?>" multiple>
                        </div>
                    <?php elseif ($fieldType == 'file') : ?>
                        <div class="form-field">
                            <label for="<?= $fieldKey ?>" class="form-label"><?= $fieldLabel ?></label>
                            <input type="file" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form-file" accept="<?= $acceptedFiles ?>">
                        </div>
                    <?php elseif ($fieldType == 'file-multiple') : ?>
                        <div class="form-field">
                            <label for="<?= $fieldKey ?>" class="form-label"><?= $fieldLabel ?></label>
                            <input type="file" name="<?= $name ?>[]" id="<?= $fieldKey ?>" class="form-file" accept="<?= $acceptedFiles ?>" multiple>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
                <!-- generated fields -->

                <div class="form__button">
                    <button class="button button-primary" type="submit">Save</button>
                    <div id="error-container" class="form__general-error"></div>
                </div>
            </form>
        <?php endif; ?>
        <!-- content -->
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>
