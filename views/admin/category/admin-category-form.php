<div class="layout__header">
    <div>
        <h3><?= $form_header ?></h3>
    </div>

    <?php if ($form_data) : ?>
        <button data-url="<?= route('admin.categories.destroy', ['param' => $form_data->id]) ?>" class="button button--danger button--text" id="delete-button">Delete permanently</button>
    <?php endif; ?>
</div>

<form action="<?= $form_action_url ?>" method="<?= $form_action_url_method ?>" class="form" data-ajax="true">
    <div class="form__field">
        <label for="name" class="form__label">Name</label>
        <input type="text" name="name" id="name" class="form__input" value="<?= !is_null($form_data) ? $form_data->name : '' ?>">
    </div>

    <?php if ($form_data) : ?>
    <div class="form__field">
        <label for="slug" class="form__label">Slug</label>
        <input type="text" name="slug" id="slug" class="form__input" value="<?= $form_data->slug ?>">
    </div>
    <?php endif; ?>

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
