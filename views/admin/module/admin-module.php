<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3>Modules (<?= $total ?>)</h3>

        <nav class="nav nav--horizontal">
            <ul class="nav__list">
                <li class="nav__item">
                    <a href="<?= route('admin.modules.create') ?>" class="nav__link">Add Module</a>
                </li>
            </ul>
        </nav>

        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Module settings</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Last updated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modules->data as $module): ?>
                <tr>
                    <td><a href="<?= route('admin.modules.edit', ['param' => $module->id]) ?>"><?= $module->title ?></a></td>
                    <td><?= $module->module_setting_id_data->name ?></td>
                    <td><?= $module->status ?></td>
                    <td><?= diffForHumans($module->created_at) ?></td>
                    <td><?= diffForHumans($module->updated_at) ?></td>
                </tr>
                <?php endforeach; ?>

                <?php if ($total == 0) : ?>
                <tr>
                    <td colspan="5">It looks like this table decided to go minimalist. No records here!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php $this->inject('admin/partials/admin-pagination', ['p_data' => $modules, 'p_page_numbers' => $pageNumbers ]) ?>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
