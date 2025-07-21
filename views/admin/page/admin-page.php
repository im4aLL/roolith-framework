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
