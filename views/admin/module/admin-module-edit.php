<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout<?= getUiStateByKey('compact') == 'compact' ? ' layout-compact' : null ?>" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <!-- breadcrumb -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= route('admin.modules.index') ?>">Modules</a></li>
            <li class="breadcrumb-item active"><?= $module->title ?></li>
        </ol>
        <!-- breadcrumb -->

        <div class="spacer-20"></div>

        <!-- block header container -->
        <div class="block-header-container">
            <div class="block-header-primary">
                <h5 class="block-header-title">Edit module - <?= $module->title ?></h5>
            </div>

            <div class="block-header-secondary">
                <button data-url="<?= route('admin.modules.destroy', ['param' => $module->id]) ?>" class="button button-danger button-text" id="delete-button">Delete permanently</button>
            </div>
        </div>
        <!-- block header container -->

        <div class="spacer-20"></div>

        <!-- content -->
        <?php if (isset($moduleSettingData)) : ?>
            <form action="<?= route('admin.modules.update', ['param' => $module->id]) ?>" method="post" class="form" enctype="multipart/form-data" data-ajax="true">
                <!-- base -->
                <div class="form-field">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" name="title" id="title" class="form-input" value="<?= $module->title ?>">
                </div>

                <div class="form-field">
                    <label for="hook" class="form-label">Hook</label>
                    <input type="text" name="hook" id="hook" class="form-input" value="<?= $module->hook ?>" disabled>
                </div>

                <div class="form-field">
                    <label for="group" class="form-label">Group (optional)</label>
                    <input type="text" name="group_name" id="group" class="form-input" list="group-name-list" value="<?= $module->group_name ?>">
                </div>

                <datalist id="group-name-list">
                    <?php foreach ($groupNames as $groupName): ?>
                        <option value="<?= $groupName ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <div class="form-field">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <?php if ($module) : ?>
                            <option value="draft" <?= $module->status == 'draft' ? ' selected' : '' ?>>Draft</option>
                            <option value="published" <?= $module->status == 'published' ? ' selected' : '' ?>>Published</option>
                        <?php else: ?>
                            <option value="draft" selected>Draft</option>
                            <option value="published">Published</option>
                        <?php endif; ?>
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
                            <input type="text" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form-input" value="<?= $moduleData[$name] ?? null ?>">
                        </div>
                    <?php elseif ($fieldType == 'textarea') : ?>
                        <div class="form-field">
                            <label for="<?= $fieldKey ?>" class="form-label"><?= $fieldLabel ?></label>
                            <textarea name="<?= $name ?>" id="<?= $fieldKey ?>" class="form-input form--textarea" rows="8"><?= $moduleData[$name] ?? null ?></textarea>
                        </div>
                    <?php elseif ($fieldType == 'rich-text') : ?>
                        <div class="form-field form-field--editor">
                            <label class="form-label"><?= $fieldLabel ?></label>
                            <div class="form__editor" style="max-height: 400px; overflow: auto"></div>
                            <div class="form__editor-value" data-input-name="<?= $name ?>" style="display: none;"><?= $moduleData[$name] ?? null ?></div>
                        </div>
                    <?php elseif ($fieldType == 'image') : ?>
                        <div class="form-field">
                            <label for="<?= $fieldKey ?>" class="form-label"><?= $fieldLabel ?></label>

                            <?php if (isset($moduleData[$name])) : ?>
                                <ul class="form__image-list">
                                    <li class="form__image-item">
                                        <?php $imageUrl = getModuleImageUrl($moduleData[$name]); ?>
                                        <a href="<?= $imageUrl ?>" target="_blank">
                                            <img src="<?= $imageUrl ?>" alt="No image" class="form__image">
                                        </a>
                                        <a href="<?= route('admin.module.file.delete') ?>" class="form__file-delete" data-file="<?= $moduleData[$name] ?>" data-module-id="<?= $module->id ?>" data-module-data-id="<?= $moduleData[$name . '_id'] ?>">Delete</a>
                                    </li>
                                </ul>
                            <?php endif; ?>

                            <input type="file" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form-file" accept="<?= $acceptedImages ?>">
                        </div>
                    <?php elseif ($fieldType == 'image-multiple') : ?>
                        <div class="form-field">
                            <label for="<?= $fieldKey ?>" class="form-label"><?= $fieldLabel ?></label>

                            <?php if (isset($moduleData[$name])) : ?>
                                <ul class="form__image-list">
                                    <?php $multiImages = json_decode($moduleData[$name]); ?>

                                    <?php foreach ($multiImages as $singleImage) : ?>
                                        <li class="form__image-item">
                                            <?php $imageUrl = getModuleImageUrl($singleImage); ?>
                                            <a href="<?= $imageUrl ?>" target="_blank">
                                                <img src="<?= $imageUrl ?>" alt="No image" class="form__image">
                                            </a>
                                            <a href="<?= route('admin.module.file.delete') ?>" class="form__file-delete" data-file="<?= $singleImage ?>" data-module-id="<?= $module->id ?>" data-module-data-id="<?= $moduleData[$name . '_id'] ?>">Delete</a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <input type="file" name="<?= $name ?>[]" id="<?= $fieldKey ?>" class="form-file" accept="<?= $acceptedImages ?>" multiple>
                        </div>
                    <?php elseif ($fieldType == 'file') : ?>
                        <div class="form-field">
                            <label for="<?= $fieldKey ?>" class="form-label"><?= $fieldLabel ?></label>

                            <?php if (isset($moduleData[$name])) : ?>
                                <ul class="form__file-list">
                                    <li class="form__file-item">
                                        <?php $fileUrl = getModuleImageUrl($moduleData[$name]); ?>
                                        <a href="<?= $fileUrl ?>" target="_blank">
                                            <?= $moduleData[$name] ?>
                                        </a>
                                        <a href="<?= route('admin.module.file.delete') ?>" class="form__file-delete" data-file="<?= $moduleData[$name] ?>" data-module-id="<?= $module->id ?>" data-module-data-id="<?= $moduleData[$name . '_id'] ?>">Delete</a>
                                    </li>
                                </ul>
                            <?php endif; ?>
                            <input type="file" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form-file" accept="<?= $acceptedFiles ?>">
                        </div>
                    <?php elseif ($fieldType == 'file-multiple') : ?>
                        <div class="form-field">
                            <label for="<?= $fieldKey ?>" class="form-label"><?= $fieldLabel ?></label>

                            <?php if (isset($moduleData[$name])) : ?>
                                <ul class="form__file-list">
                                    <?php $multiFiles = json_decode($moduleData[$name]); ?>

                                    <?php foreach ($multiFiles as $singleFile) : ?>
                                        <li class="form__file-item">
                                            <?php $singleFileUrl = getModuleImageUrl($singleFile); ?>
                                            <a href="<?= $singleFileUrl ?>" target="_blank">
                                                <?= $singleFile ?>
                                            </a>
                                            <a href="<?= route('admin.module.file.delete') ?>" class="form__file-delete" data-file="<?= $singleFile ?>" data-module-id="<?= $module->id ?>" data-module-data-id="<?= $moduleData[$name . '_id'] ?>">Delete</a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

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

<?php $this->inject('admin/partials/admin-footer') ?>
