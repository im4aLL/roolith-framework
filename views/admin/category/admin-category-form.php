<!-- block header container -->
<div class="block-header-container">
    <div class="block-header-primary">
        <h5 class="block-header-title"><?= $form_header ?></h5>
    </div>

    <?php if ($form_data) : ?>
        <div class="block-header-secondary">
            <button data-url="<?= route('admin.categories.destroy', ['param' => $form_data->id]) ?>" class="button button-danger button-text" id="delete-button">Delete permanently</button>
        </div>
    <?php endif; ?>
</div>
<!-- block header container -->

<form action="<?= $form_action_url ?>" method="<?= $form_action_url_method ?>" class="form" data-ajax="true">
    <div class="form-field">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-input" value="<?= !is_null($form_data) ? $form_data->name : '' ?>">
    </div>

    <?php if ($form_data) : ?>
        <div class="form-field">
            <label for="slug" class="form-label">Slug</label>
            <input type="text" name="slug" id="slug" class="form-input" value="<?= $form_data->slug ?>">
        </div>
    <?php endif; ?>

    <div class="form-field form-field--editor">
        <label class="form-label">Body</label>
        <div class="form__editor"></div>
        <div class="form__editor-value" data-input-name="body" style="display: none;"><?= !is_null($form_data) ? $form_data->body : '' ?></div>
    </div>

    <div class="form__button">
        <button class="button button-primary" type="submit"><?= $form_button_text ?></button>
        <div id="error-container" class="form__general-error"></div>
    </div>
</form>