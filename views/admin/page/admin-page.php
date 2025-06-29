<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3>Pages (<?= $total ?>)</h3>

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
                    <td><?= $page->title ?></td>
                    <td>/<?= $page->slug ?></td>
                    <td><?= $page->type ?></td>
                    <td><?= $page->status ?></td>
                    <td><?= $page->category_id_data ? $page->category_id_data->name : '-' ?></td>
                    <td><?= $page->user_id_data ? $page->user_id_data->name : '-' ?></td>
                    <td><?= diffForHumans($page->created_at) ?></td>
                    <td><?= diffForHumans($page->updated_at) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <ul class="pagination">
            <li class="pagination__item"><a href="<?= $pages->prevPageUrl ?>" class="button button--small">Previous</a></li>
            <?php foreach ($pageNumbers as $pageNumber) : ?>
                <li class="pagination__item">
                    <a href="<?= $pages->path ?>?page=<?= $pageNumber ?>" class="button button--small<?= $pageNumber == $pages->currentPage ? ' button--active' : '' ?>">
                        <?= $pageNumber ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <li class="pagination__item"><a href="<?= $pages->nextPageUrl ?>" class="button button--small">Next</a></li>
        </ul>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
