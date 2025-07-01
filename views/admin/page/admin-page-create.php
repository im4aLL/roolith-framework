<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <ul class="breadcrumb">
            <li class="breadcrumb__item">
                <a href="<?= route('admin.pages.index') ?>" class="breadcrumb__link">Pages</a>
            </li>
            <li class="breadcrumb__item">
                <a href="<?= route('admin.pages.create') ?>" class="breadcrumb__link">Add new</a>
            </li>
        </ul>

        <div class="layout__header">
            <h3>Add new page</h3>
        </div>

        <form action="<?= route('admin.pages.store') ?>" method="post" class="form" data-ajax="true">
            <div class="form__field">
                <label for="title" class="form__label">Title</label>
                <input type="text" name="title" id="title" class="form__input">
            </div>

            <div class="form__field">
                <label class="form__label">Type</label>
                <label><input type="radio" name="type" class="form__input form--radio" value="page" checked> Page</label>
                <label><input type="radio" name="type" class="form__input form--radio" value="blog"> Blog</label>
            </div>

            <div class="form__field">
                <label for="status" class="form__label">Status</label>
                <select name="status" id="status" class="form__input form--select">
                    <option value="draft" selected>Draft</option>
                    <option value="published">Publish</option>
                </select>
            </div>

            <div class="form__field">
                <label for="editor" class="form__label">Body</label>
                <div id="editor"></div>
                <div id="editor-value" data-input-name="body" style="display: none"></div>
            </div>

            <button class="button" type="submit">Add page</button>
        </form>
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
