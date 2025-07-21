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

        <div class="table__filters">
            <form action="" method="get" class="form form--inline form--filter">
                <div class="form__field">
                    <label for="title" class="form__label">By title</label>
                    <input type="text" id="title" name="filter[title]" class="form__input" value="<?= isset($filterInput) ? $filterInput['title'] : '' ?>">
                </div>

                <div class="form__field">
                    <label for="group_name" class="form__label">By group</label>
                    <select name="filter[group_name]" id="group_name" class="form__input form--select">
                        <option value=""></option>
                        <?php foreach ($groupNames as $groupName): ?>
                            <option value="<?= $groupName ?>" <?= isset($filterInput) && $filterInput['group_name'] == $groupName ? 'selected' : '' ?>><?= $groupName ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form__field">
                    <label for="status" class="form__label">By status</label>
                    <select name="filter[status]" id="status" class="form__input form--select">
                        <option value=""></option>
                        <option value="draft" <?= isset($filterInput) && $filterInput['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= isset($filterInput) && $filterInput['status'] == 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>

                <button type="submit" class="button">Filter</button>

                <?php if (isset($filterInput)) : ?>
                    <a href="<?= route('admin.modules.index') ?>" class="button">Reset filter</a>
                <?php endif; ?>
            </form>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Module settings</th>
                    <th>Group</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Last updated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modules->data as $module): ?>
                <tr>
                    <td><a href="<?= route('admin.modules.edit', ['param' => $module->id]) ?>"><?= $module->title ?></a></td>
                    <td><?= $module->admin_module_setting->name ?> (<?= $module->admin_module_setting->settings_count ?>)</td>
                    <td><?= $module->group_name ?></td>
                    <td><?= $module->status ?></td>
                    <td><?= diffForHumans($module->created_at) ?></td>
                    <td><?= diffForHumans($module->updated_at) ?></td>
                </tr>
                <?php endforeach; ?>

                <?php if ($total == 0) : ?>
                <tr>
                    <td colspan="6">It looks like this table decided to go minimalist. No records here!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php $this->inject('admin/partials/admin-pagination', ['p_data' => $modules, 'p_page_numbers' => $pageNumbers ]) ?>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
