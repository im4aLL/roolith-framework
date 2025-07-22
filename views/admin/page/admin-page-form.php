<div class="layout__header">
    <div>
        <h3><?= $form_header ?></h3>

        <?php if ($form_data) : ?>
            <p><?= url($form_data->slug) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($form_data) : ?>
        <button data-url="<?= route('admin.pages.destroy', ['param' => $form_data->id]) ?>" class="button button--danger button--text" id="delete-button">Delete permanently</button>
    <?php endif; ?>
</div>

<form action="<?= $form_action_url ?>" method="<?= $form_action_url_method ?>" class="form" data-ajax="true">
    <div class="form__field">
        <label for="title" class="form__label">Title</label>
        <input type="text" name="title" id="title" class="form__input" value="<?= !is_null($form_data) ? $form_data->title : '' ?>">
    </div>

    <?php if ($form_data) : ?>
    <div class="form__field">
        <label for="slug" class="form__label">Slug</label>
        <input type="text" name="slug" id="slug" class="form__input" value="<?= $form_data->slug ?>">
    </div>
    <?php endif; ?>

    <div class="form__field">
        <label class="form__label">Type</label>

        <?php if ($form_data) : ?>
            <label><input type="radio" name="type" class="form__input form__input--radio" value="page" <?php if ($form_data->type == 'page') echo 'checked' ?>> Page</label>
            <label><input type="radio" name="type" class="form__input form__input--radio" value="blog" <?php if ($form_data->type == 'blog') echo 'checked' ?>> Blog</label>
        <?php else: ?>
            <label><input type="radio" name="type" class="form__input form__input--radio" value="page" checked> Page</label>
            <label><input type="radio" name="type" class="form__input form__input--radio" value="blog"> Blog</label>
        <?php endif; ?>
    </div>

    <div class="form__field">
        <label for="status" class="form__label">Status</label>
        <select name="status" id="status" class="form__input form--select">
            <?php if ($form_data) : ?>
                <option value="draft"<?= $form_data->status == 'draft' ? ' selected' : '' ?>>Draft</option>
                <option value="published"<?= $form_data->status == 'published' ? ' selected' : '' ?>>Published</option>
            <?php else: ?>
                <option value="draft" selected>Draft</option>
                <option value="published">Published</option>
            <?php endif; ?>
        </select>
    </div>

    <div class="form__field form__field--editor">
        <label class="form__label">Body</label>
        <div class="form__editor"></div>
        <div class="form__editor-value" data-input-name="body" style="display: none;"><?= !is_null($form_data) ? $form_data->body : '' ?></div>
    </div>

    <div class="form__field">
        <label for="category_id" class="form__label">Category</label>
        <select name="category_id[]" id="category_id" class="form__input form--select" multiple>
            <?php if ($form_data) : ?>
                <option value=""<?= count($form_data->category_ids) === 0 ? ' selected' : '' ?>>None</option>
                <?php foreach ($form_data_categories as $category): ?>
                    <option value="<?= $category->id ?>"<?= \App\Utils\_::contains($form_data->category_ids, $category->id) ? ' selected' : '' ?>><?= $category->name ?></option>
                <?php endforeach; ?>
            <?php else : ?>
                <option value="" selected>None</option>
                <?php foreach ($form_data_categories as $category): ?>
                    <option value="<?= $category->id ?>"><?= $category->name ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <div class="form__field">
        <label for="module_list" class="form__label">Add modules</label>
        <ul class="form__list form__list--draggable">
            <?php
                if ($form_data) :
                    foreach ($form_data->modules as $pageModule): ?>
                        <li class="form__list-item" draggable="true">
                            <select name="module_id[]" class="form__input form--select">
                                <option value="">Select module</option>
                                <?php foreach ($form_data_modules as $module): ?>
                                    <option value="<?= $module->id ?>" <?= $module->id == $pageModule->module_id ? 'selected' : '' ?>>
                                        <?= $module->title ?> <?= $module->group_name ? "({$module->group_name})" : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <div class="form__list-item-action">
                                <button class="button button--text button--danger" type="button">Remove</button>
                            </div>
                        </li>
            <?php
                    endforeach;
                endif;
            ?>

            <?php if (!$form_data || count($form_data->modules) == 0) : ?>
            <li class="form__list-item" draggable="true">
                <select name="module_id[]" class="form__input form--select">
                    <option value="">Select module</option>
                    <?php foreach ($form_data_modules as $module): ?>
                        <option value="<?= $module->id ?>"><?= $module->title ?> <?= $module->group_name ? "({$module->group_name})" : '' ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="form__list-item-action">
                    <button class="button button--text button--danger" type="button">Remove</button>
                </div>
            </li>
            <?php endif; ?>
        </ul>
        <button class="button button--text" type="button" id="add-field">Add more</button>
    </div>

    <div class="form__button">
        <button class="button" type="submit"><?= $form_button_text ?></button>
        <div id="error-container" class="form__general-error"></div>
    </div>
</form>
