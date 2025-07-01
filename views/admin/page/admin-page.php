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
                    <td><a href="<?= route('admin.pages.show', ['param' => $page->id]) ?>"><?= $page->title ?></a></td>
                    <td>/<?= $page->slug ?></td>
                    <td><?= $page->type ?></td>
                    <td><?= $page->status ?></td>
                    <td><?= $page->category_id_data ? $page->category_id_data->name : '-' ?></td>
                    <td><?= $page->user_id_data ? $page->user_id_data->name : '-' ?></td>
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

        <?php if ($isShowPagination) : ?>
        <ul class="pagination">
            <li class="pagination__item"><a href="<?= $pages->prevPageUrl ?>">Previous</a></li>
            <?php foreach ($pageNumbers as $pageNumber) : ?>
                <li class="pagination__item <?= $pageNumber == $pages->currentPage ? 'pagination__item--active' : '' ?>">
                    <a href="<?= $pages->path ?>?page=<?= $pageNumber ?>">
                        <?= $pageNumber ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <li class="pagination__item"><a href="<?= $pages->nextPageUrl ?>">Next</a></li>
        </ul>
        <?php endif; ?>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
