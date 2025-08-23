<!-- block header container -->
<div class="block-header-container">
    <div class="block-header-primary">
        <h5 class="block-header-title"><?= $form_header ?></h5>

        <?php if ($form_data) : ?>
            <p class="block-header-subtitle">
                <a href="<?= url($form_data->slug) ?>" target="_blank"><?= url($form_data->slug) ?> <i class="iconoir-open-new-window"></i></a>
            </p>
        <?php endif; ?>
    </div>

    <?php if ($form_data) : ?>
        <div class="block-header-secondary">
            <button data-url="<?= route('admin.pages.destroy', ['param' => $form_data->id]) ?>" class="button button-danger button-text" id="delete-button">Delete permanently</button>
        </div>
    <?php endif; ?>
</div>
<!-- block header container -->

<form action="<?= $form_action_url ?>" method="<?= $form_action_url_method ?>" class="form" data-ajax="true">
    <div class="form-field">
        <label for="title" class="form-label">Title</label>
        <input type="text" name="title" id="title" class="form-input" value="<?= !is_null($form_data) ? $form_data->title : '' ?>">
    </div>

    <?php if ($form_data) : ?>
        <div class="form-field">
            <label for="slug" class="form-label">Slug</label>
            <input type="text" name="slug" id="slug" class="form-input" value="<?= $form_data->slug ?>">
        </div>
    <?php endif; ?>

    <div class="form-field">
        <label class="form-label">Type</label>

        <div class="form-radio-items">
            <?php if ($form_data) : ?>
                <label class="form-radio-item"><input type="radio" name="type" class="form-radio" value="page" <?php if ($form_data->type == 'page') echo 'checked' ?>> Page</label>
                <label class="form-radio-item"><input type="radio" name="type" class="form-radio" value="blog" <?php if ($form_data->type == 'blog') echo 'checked' ?>> Blog</label>
            <?php else: ?>
                <label class="form-radio-item"><input type="radio" name="type" class="form-radio" value="page" checked> Page</label>
                <label class="form-radio-item"><input type="radio" name="type" class="form-radio" value="blog"> Blog</label>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-field">
        <label for="status" class="form-label">Status</label>
        <select name="status" id="status" class="form-select">
            <?php if ($form_data) : ?>
                <option value="draft" <?= $form_data->status == 'draft' ? ' selected' : '' ?>>Draft</option>
                <option value="published" <?= $form_data->status == 'published' ? ' selected' : '' ?>>Published</option>
            <?php else: ?>
                <option value="draft">Draft</option>
                <option value="published" selected>Published</option>
            <?php endif; ?>
        </select>
    </div>

    <div class="form-field form-field--editor">
        <label class="form-label">Body</label>
        <div class="form__editor"></div>
        <div class="form__editor-value" data-input-name="body" style="display: none;"><?= !is_null($form_data) ? $form_data->body : '' ?></div>
    </div>

    <div class="form-field">
        <label for="category_id" class="form-label">Category</label>
        <select name="category_id[]" id="category_id" class="form-select" multiple>
            <?php if ($form_data) : ?>
                <option value="" <?= count($form_data->category_ids) === 0 ? ' selected' : '' ?>>None</option>
                <?php foreach ($form_data_categories as $category): ?>
                    <option value="<?= $category->id ?>" <?= \App\Utils\_::contains($form_data->category_ids, $category->id) ? ' selected' : '' ?>><?= $category->name ?></option>
                <?php endforeach; ?>
            <?php else : ?>
                <option value="" selected>None</option>
                <?php foreach ($form_data_categories as $category): ?>
                    <option value="<?= $category->id ?>"><?= $category->name ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <div class="form-field">
        <div class="block-repeater">
            <label class="block-repeater-label form-label">Add modules</label>

            <ul class="block-repeater-list">
                <?php
                if ($form_data) :
                    foreach ($form_data->modules as $pageModule): ?>
                        <li class="block-repeater-item" draggable="true">
                            <button class="block-repeater-sort">⋮⋮</button>
                            <div class="block-repeater-primary">
                                <div class="form-field">
                                    <label class="form-label">Choose module</label>
                                    <select name="module_id[]" class="form-select">
                                        <option value="">Select module</option>
                                        <?php foreach ($form_data_modules as $module): ?>
                                            <option value="<?= $module->id ?>" <?= $module->id == $pageModule->module_id ? 'selected' : '' ?>>
                                                <?= $module->title ?> <?= $module->group_name ? "({$module->group_name})" : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="block-repeater-secondary">
                                <button class="button button-outline button-danger button-icon js-remove-field" type="button">
                                    <i class="iconoir-xmark"></i>
                                    Remove
                                </button>
                            </div>
                        </li>
                <?php
                    endforeach;
                endif;
                ?>

                <?php if (!$form_data || count($form_data->modules) == 0) : ?>
                    <li class="block-repeater-item" draggable="true">
                        <button class="block-repeater-sort">⋮⋮</button>
                        <div class="block-repeater-primary">
                            <div class="form-field">
                                <label class="form-label">Choose module</label>
                                <select name="module_id[]" class="form-select">
                                    <option value="">Select module</option>
                                    <?php foreach ($form_data_modules as $module): ?>
                                        <option value="<?= $module->id ?>"><?= $module->title ?> <?= $module->group_name ? "({$module->group_name})" : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="block-repeater-secondary">
                            <button class="button button-outline button-danger button-icon js-remove-field" type="button">
                                <i class="iconoir-xmark"></i>
                                Remove
                            </button>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>

            <button class="button button-icon js-add-field" type="button">
                <i class="iconoir-plus"></i>
                Add More Fields
            </button>
        </div>
    </div>

    <div class="button-bundle">
        <button class="button button-primary" type="submit"><?= $form_button_text ?></button>
        <div id="error-container" class="form__general-error"></div>
    </div>
</form>