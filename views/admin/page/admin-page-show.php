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
                <a href="<?= route('admin.pages.show', ['param' => $page->id]) ?>" class="breadcrumb__link"><?= $page->title ?></a>
            </li>
        </ul>

        <div class="layout__header">
            <h3><?= $page->title ?></h3>
            <p><?= url($page->slug) ?></p>
        </div>

        <form action="" class="form">
            <div class="form__field">
                <label for="title" class="form__label">Title</label>
                <input type="text" name="title" id="title" class="form__input" value="<?= $page->title ?>">
            </div>

            <div class="form__field">
                <label for="slug" class="form__label">Slug</label>
                <input type="text" name="slug" id="slug" class="form__input" value="<?= $page->slug ?>">
            </div>

            <div class="form__field">
                <label class="form__label">Type</label>
                <label><input type="radio" name="type" class="form__input form--radio" value="page" <?php if ($page->type == 'page') echo 'checked' ?>> Page</label>
                <label><input type="radio" name="type" class="form__input form--radio" value="blog" <?php if ($page->type == 'blog') echo 'checked' ?>> Blog</label>
            </div>

            <div class="form__field">
                <label for="status" class="form__label">Status</label>
                <select name="status" id="status" class="form__input form--select">
                    <option value="draft">Draft</option>
                    <option value="published">Publish</option>
                </select>
            </div>

            <div class="form__field">
                <label for="editor" class="form__label">Body</label>
                <div id="editor"></div>
                <div id="editor-value" style="display: none"><?= $page->body ?></div>
            </div>

            <button class="button" type="submit">Save changes</button>
        </form>
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
