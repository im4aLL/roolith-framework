<div class="layout__header">
    <div>
        <h3><?= $form_header ?></h3>
    </div>

    <?php if ($form_data) : ?>
        <button data-url="<?= route('admin.module-settings.destroy', ['param' => $form_data->id]) ?>" class="button button--danger button--text" id="delete-button">Delete permanently</button>
    <?php endif; ?>
</div>

<form action="<?= $form_action_url ?>" method="<?= $form_action_url_method ?>" class="form">
    <?php if (isset($error_message)) : ?>
        <div class="message message--danger"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="form__field">
        <label for="name" class="form__label">Name</label>
        <input type="text" name="name" id="name" class="form__input" value="<?= !is_null($form_data) ? $form_data->name : '' ?>">
    </div>

    <div class="form__field">
        <label for="first-field">Custom Fields</label>

        <ul class="form__list">
            <?php if ($form_data) : ?>
                <?php foreach ($form_data->settings->name as $index => $name) : ?>
                    <li class="form__list-item">
                        <input type="text" name="settings[name][]" id="first-field" class="form__input" placeholder="Field name" value="<?= $name ?>">
                        <select name="settings[type][]" class="form__input form--select">
                            <option value="text" <?= $form_data->settings->type[$index] == 'text' ? 'selected' : '' ?>>Text</option>
                            <option value="textarea" <?= $form_data->settings->type[$index] == 'textarea' ? 'selected' : '' ?>>Textarea</option>
                            <option value="rich-text" <?= $form_data->settings->type[$index] == 'rich-text' ? 'selected' : '' ?>>Rich text editor</option>
                            <option value="image" <?= $form_data->settings->type[$index] == 'image' ? 'selected' : '' ?>>Image upload</option>
                            <option value="image-multiple" <?= $form_data->settings->type[$index] == 'image-multiple' ? 'selected' : '' ?>>Multiple image upload</option>
                            <option value="file" <?= $form_data->settings->type[$index] == 'file' ? 'selected' : '' ?>>File upload</option>
                            <option value="file-multiple" <?= $form_data->settings->type[$index] == 'file-multiple' ? 'selected' : '' ?>>Multiple file upload</option>
                        </select>
                        <div class="form__list-item-action">
                            <button class="button button--text button--danger" type="button">Remove</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else : ?>
                <li class="form__list-item">
                    <input type="text" name="settings[name][]" id="first-field" class="form__input" placeholder="Field name">
                    <select name="settings[type][]" class="form__input form--select">
                        <option value="">Select field type</option>
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="rich-text">Rich text editor</option>
                        <option value="image">Image upload</option>
                        <option value="image-multiple">Multiple image upload</option>
                        <option value="file">File upload</option>
                        <option value="file-multiple">Multiple file upload</option>
                    </select>
                    <div class="form__list-item-action">
                        <button class="button button--text button--danger" type="button">Remove</button>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
        <button class="button button--text" type="button" id="add-field">Add new field</button>
    </div>

    <div class="form__button">
        <button class="button" type="submit"><?= $form_button_text ?></button>
        <div id="error-container" class="form__general-error"></div>
    </div>
</form>
