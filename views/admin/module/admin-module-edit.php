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
                <a href="<?= route('admin.modules.edit', ['param' => $module->id]) ?>" class="breadcrumb__link"><?= $module->title ?></a>
            </li>
        </ul>

        <div class="layout__header">
            <div>
                <h3><?= $module->title ?></h3>
            </div>

            <button data-url="<?= route('admin.modules.destroy', ['param' => $module->id]) ?>" class="button button--danger button--text" id="delete-button">Delete permanently</button>
        </div>

        <?php if (isset($moduleSettingData)) : ?>
            <form action="<?= route('admin.modules.update', ['param' => $module->id]) ?>" method="post" class="form" enctype="multipart/form-data" data-ajax="true">
                <!-- base -->
                <div class="form__col">
                    <div class="form__field">
                        <label for="title" class="form__label">Title</label>
                        <input type="text" name="title" id="title" class="form__input" value="<?= $module->title ?>">
                    </div>

                    <div class="form__field">
                        <label for="hook" class="form__label">Hook</label>
                        <input type="text" name="hook" id="hook" class="form__input" value="<?= $module->hook ?>" disabled>
                    </div>
                </div>

                <div class="form__field">
                    <label for="group" class="form__label">Group (optional)</label>
                    <input type="text" name="group_name" id="group" class="form__input" list="group-name-list" value="<?= $module->group_name ?>">
                </div>

                <datalist id="group-name-list">
                    <?php foreach ($groupNames as $groupName): ?>
                        <option value="<?= $groupName ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <div class="form__field">
                    <label for="status" class="form__label">Status</label>
                    <select name="status" id="status" class="form__input form--select">
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
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>
                            <input type="text" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form__input" value="<?= $moduleData[$name] ?? null ?>">
                        </div>
                    <?php elseif ($fieldType == 'textarea') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>
                            <textarea name="<?= $name ?>" id="<?= $fieldKey ?>" class="form__input form--textarea" rows="8"><?= $moduleData[$name] ?? null ?></textarea>
                        </div>
                    <?php elseif ($fieldType == 'rich-text') : ?>
                        <div class="form__field form__field--editor">
                            <label class="form__label"><?= $fieldLabel ?></label>
                            <div class="form__editor" style="max-height: 400px; overflow: auto"></div>
                            <div class="form__editor-value" data-input-name="<?= $name ?>" style="display: none;"><?= $moduleData[$name] ?? null ?></div>
                        </div>
                    <?php elseif ($fieldType == 'image') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>

                            <?php if (isset($moduleData[$name])) : ?>
                                <ul class="form__image-list">
                                    <li class="form__image-item">
                                        <?php $imageUrl = getModuleImageUrl($moduleData[$name]); ?>
                                        <a href="<?= $imageUrl ?>" target="_blank">
                                            <img src="<?= $imageUrl ?>" alt="No image" class="form__image">
                                        </a>
                                        <a href="<?= route('admin.module.file.delete') ?>" class="form__file-delete" data-file="<?= $moduleData[$name] ?>" data-module-id="<?= $module->id ?>" data-module-data-id="<?= $moduleData[$name.'_id'] ?>">Delete</a>
                                    </li>
                                </ul>
                            <?php endif; ?>

                            <input type="file" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form__input" accept="<?= $acceptedImages ?>">
                        </div>
                    <?php elseif ($fieldType == 'image-multiple') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>

                            <?php if (isset($moduleData[$name])) : ?>
                                <ul class="form__image-list">
                                    <?php $multiImages = json_decode($moduleData[$name]); ?>

                                    <?php foreach ($multiImages as $singleImage) : ?>
                                    <li class="form__image-item">
                                        <?php $imageUrl = getModuleImageUrl($singleImage); ?>
                                        <a href="<?= $imageUrl ?>" target="_blank">
                                            <img src="<?= $imageUrl ?>" alt="No image" class="form__image">
                                        </a>
                                        <a href="<?= route('admin.module.file.delete') ?>" class="form__file-delete" data-file="<?= $singleImage ?>" data-module-id="<?= $module->id ?>" data-module-data-id="<?= $moduleData[$name.'_id'] ?>">Delete</a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <input type="file" name="<?= $name ?>[]" id="<?= $fieldKey ?>" class="form__input" accept="<?= $acceptedImages ?>" multiple>
                        </div>
                    <?php elseif ($fieldType == 'file') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>

                            <?php if (isset($moduleData[$name])) : ?>
                                <ul class="form__file-list">
                                    <li class="form__file-item">
                                        <?php $fileUrl = getModuleImageUrl($moduleData[$name]); ?>
                                        <a href="<?= $fileUrl ?>" target="_blank">
                                            <?= $moduleData[$name] ?>
                                        </a>
                                        <a href="<?= route('admin.module.file.delete') ?>" class="form__file-delete" data-file="<?= $moduleData[$name] ?>" data-module-id="<?= $module->id ?>" data-module-data-id="<?= $moduleData[$name.'_id'] ?>">Delete</a>
                                    </li>
                                </ul>
                            <?php endif; ?>
                            <input type="file" name="<?= $name ?>" id="<?= $fieldKey ?>" class="form__input" accept="<?= $acceptedFiles ?>">
                        </div>
                    <?php elseif ($fieldType == 'file-multiple') : ?>
                        <div class="form__field">
                            <label for="<?= $fieldKey ?>" class="form__label"><?= $fieldLabel ?></label>

                            <?php if (isset($moduleData[$name])) : ?>
                                <ul class="form__file-list">
                                    <?php $multiFiles = json_decode($moduleData[$name]); ?>

                                    <?php foreach ($multiFiles as $singleFile) : ?>
                                        <li class="form__file-item">
                                            <?php $singleFileUrl = getModuleImageUrl($singleFile); ?>
                                            <a href="<?= $singleFileUrl ?>" target="_blank">
                                                <?= $singleFile ?>
                                            </a>
                                            <a href="<?= route('admin.module.file.delete') ?>" class="form__file-delete" data-file="<?= $singleFile ?>" data-module-id="<?= $module->id ?>" data-module-data-id="<?= $moduleData[$name.'_id'] ?>">Delete</a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

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
