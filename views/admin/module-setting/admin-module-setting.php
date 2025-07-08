<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3>Module Settings (<?= $total ?>)</h3>

        <nav class="nav nav--horizontal">
            <ul class="nav__list">
                <li class="nav__item">
                    <a href="<?= route('admin.module-settings.create') ?>" class="nav__link">Add Module Setting</a>
                </li>
            </ul>
        </nav>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Created</th>
                    <th>Last updated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($moduleSettings->data as $row): ?>
                <tr>
                    <td><a href="<?= route('admin.module-settings.edit', ['param' => $row->id]) ?>"><?= $row->name ?></a></td>
                    <td><?= diffForHumans($row->created_at) ?></td>
                    <td><?= diffForHumans($row->updated_at) ?></td>
                </tr>
                <?php endforeach; ?>

                <?php if ($total == 0) : ?>
                <tr>
                    <td colspan="3">It looks like this table decided to go minimalist. No records here!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($isShowPagination) : ?>
            <ul class="pagination">
                <li class="pagination__item"><a href="<?= $moduleSettings->prevPageUrl ?>">Previous</a></li>
                <li class="pagination__item"><a href="<?= $moduleSettings->firstPageUrl ?>">First</a></li>
                <?php foreach ($pageNumbers as $pageNumber) : ?>
                    <li class="pagination__item <?= $pageNumber == $moduleSettings->currentPage ? 'pagination__item--active' : '' ?>">
                        <a href="<?= $moduleSettings->path ?>?page=<?= $pageNumber ?>">
                            <?= $pageNumber ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li class="pagination__item"><a href="<?= $moduleSettings->lastPageUrl ?>">Last</a></li>
                <li class="pagination__item"><a href="<?= $moduleSettings->nextPageUrl ?>">Next</a></li>
            </ul>

            <small class="is--dimmed">Total row(s) <?= $moduleSettings->total ?>. Showing page <?= $moduleSettings->currentPage ?> out of <?= $moduleSettings->lastPage ?></small>
        <?php endif; ?>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
