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

        <?php $this->inject('admin/partials/admin-pagination', ['p_data' => $moduleSettings, 'p_page_numbers' => $pageNumbers ]) ?>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
