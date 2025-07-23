<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3>Pages (<?= $total ?>)</h3>

        <nav class="nav nav--horizontal">
            <ul class="nav__list">
                <li class="nav__item">
                    <a href="<?= route('admin.pages.create') ?>" class="nav__link">Add Page</a>
                </li>
            </ul>
        </nav>

        <div class="table__filters">
            <form action="" method="get" class="form form--inline form--filter">
                <div class="form__field">
                    <label for="title" class="form__label">By title</label>
                    <input type="text" id="title" name="filter[title]" class="form__input" value="<?= $filterInput['title'] ?? '' ?>">
                </div>

                <div class="form__field">
                    <label for="type" class="form__label">By type</label>
                    <select name="filter[type]" id="type" class="form__input form--select">
                        <option value=""></option>
                        <option value="page" <?= isset($filterInput['type']) && $filterInput['type'] == 'page' ? 'selected' : '' ?>>Page</option>
                        <option value="blog" <?= isset($filterInput['type']) && $filterInput['type'] == 'blog' ? 'selected' : '' ?>>Blog</option>
                    </select>
                </div>

                <div class="form__field">
                    <label for="status" class="form__label">By status</label>
                    <select name="filter[status]" id="status" class="form__input form--select">
                        <option value=""></option>
                        <option value="draft" <?= isset($filterInput['status']) && $filterInput['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= isset($filterInput['status']) && $filterInput['status'] == 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>

                <button type="submit" class="button">Filter</button>

                <?php if (isset($filterInput)) : ?>
                    <a href="<?= route('admin.pages.index') ?>" class="button">Reset filter</a>
                <?php endif; ?>
            </form>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Category</th>
                    <th>User</th>
                    <th>Created</th>
                    <th>Last updated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages->data as $page): ?>
                <tr>
                    <td><a href="<?= route('admin.pages.edit', ['param' => $page->id]) ?>"><?= $page->title ?></a></td>
                    <td>/<?= $page->slug ?></td>
                    <td><?= $page->type ?></td>
                    <td><?= $page->status ?></td>
                    <td><?= $page->categoryNames ?? '-' ?></td>
                    <td><?= $page->admin_user ? $page->admin_user->name : '-' ?></td>
                    <td><?= diffForHumans($page->created_at) ?></td>
                    <td><?= diffForHumans($page->updated_at) ?></td>
                </tr>
                <?php endforeach; ?>

                <?php if ($total == 0) : ?>
                <tr>
                    <td colspan="8">Looks like this table decided to go minimalist. No records here!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php $this->inject('admin/partials/admin-pagination', ['p_data' => $pages, 'p_page_numbers' => $pageNumbers ]) ?>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
