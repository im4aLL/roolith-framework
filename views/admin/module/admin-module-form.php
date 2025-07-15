<div class="layout__header">
    <div>
        <h3><?= $form_header ?></h3>
    </div>

    <?php if ($form_data) : ?>
        <button data-url="<?= route('admin.modules.destroy', ['param' => $form_data->id]) ?>" class="button button--danger button--text" id="delete-button">Delete permanently</button>
    <?php endif; ?>
</div>

<form action="<?= $form_action_url ?>" method="<?= $form_action_url_method ?>" class="form" data-ajax="true">
    <div class="form__field">
        <label for="title" class="form__label">Title</label>
        <input type="text" name="title" id="title" class="form__input" value="<?= !is_null($form_data) ? $form_data->title : '' ?>">
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

    <div class="form__button">
        <button class="button" type="submit"><?= $form_button_text ?></button>
        <div id="error-container" class="form__general-error"></div>
    </div>
</form>
