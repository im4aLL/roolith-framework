<!-- block header container -->
<div class="block-header-container">
    <div class="block-header-primary">
        <h5 class="block-header-title"><?= $form_header ?></h5>
    </div>

    <?php if ($form_data) : ?>
        <div class="block-header-secondary">
            <button data-url="<?= route('admin.module-settings.destroy', ['param' => $form_data->id]) ?>" class="button button-danger button-text" id="delete-button">Delete permanently</button>
        </div>
    <?php endif; ?>
</div>
<!-- block header container -->

<div class="spacer-20"></div>

<form action="<?= $form_action_url ?>" method="<?= $form_action_url_method ?>" class="form">
    <?php if (isset($error_message)) : ?>
        <div class="message message-danger"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="form-field">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-input" value="<?= !is_null($form_data) ? $form_data->name : '' ?>">
    </div>

    <div class="form-field">
        <div class="block-repeater">
            <label class="block-repeater-label form-label">Custom Fields</label>

            <ul class="block-repeater-list">
                <?php if ($form_data) : ?>
                    <?php foreach ($form_data->settings->name as $index => $name) : ?>

                        <li class="block-repeater-item" draggable="true">
                            <button class="block-repeater-sort">⋮⋮</button>
                            <div class="block-repeater-primary">
                                <div class="form-field">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="settings[name][]" class="form-input" placeholder="Field name" value="<?= $name ?>">
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Type</label>
                                    <select name="settings[type][]" class="form-select">
                                        <option value="text" <?= $form_data->settings->type[$index] == 'text' ? 'selected' : '' ?>>Text</option>
                                        <option value="textarea" <?= $form_data->settings->type[$index] == 'textarea' ? 'selected' : '' ?>>Textarea</option>
                                        <option value="rich-text" <?= $form_data->settings->type[$index] == 'rich-text' ? 'selected' : '' ?>>Rich text editor</option>
                                        <option value="image" <?= $form_data->settings->type[$index] == 'image' ? 'selected' : '' ?>>Image upload</option>
                                        <option value="image-multiple" <?= $form_data->settings->type[$index] == 'image-multiple' ? 'selected' : '' ?>>Multiple image upload</option>
                                        <option value="file" <?= $form_data->settings->type[$index] == 'file' ? 'selected' : '' ?>>File upload</option>
                                        <option value="file-multiple" <?= $form_data->settings->type[$index] == 'file-multiple' ? 'selected' : '' ?>>Multiple file upload</option>
                                    </select>
                                </div>
                            </div>
                            <div class="block-repeater-secondary">
                                <button class="button button-outline button-danger button-text js-remove-field" type="button">
                                    Remove
                                </button>
                            </div>
                        </li>

                    <?php endforeach; ?>
                <?php else : ?>

                    <li class="block-repeater-item" draggable="true">
                        <button class="block-repeater-sort">⋮⋮</button>
                        <div class="block-repeater-primary">
                            <div class="form-field">
                                <label class="form-label">Name</label>
                                <input type="text" name="settings[name][]" class="form-input" placeholder="Field name">
                            </div>
                            <div class="form-field">
                                <label class="form-label">Type</label>
                                <select name="settings[type][]" class="form-select">
                                    <option value="">Select field type</option>
                                    <option value="text">Text</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="rich-text">Rich text editor</option>
                                    <option value="image">Image upload</option>
                                    <option value="image-multiple">Multiple image upload</option>
                                    <option value="file">File upload</option>
                                    <option value="file-multiple">Multiple file upload</option>
                                </select>
                            </div>
                        </div>
                        <div class="block-repeater-secondary">
                            <button class="button button-outline button-danger button-text js-remove-field" type="button">
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

    <div class="form-field">
        <div class="button-bundle">
            <button class="button" type="submit"><?= $form_button_text ?></button>
            <div id="error-container" class="form__general-error"></div>
        </div>
    </div>

</form>
