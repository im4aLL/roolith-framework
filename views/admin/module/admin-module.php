<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <div class="block-header">
            <!-- breadcrumb -->
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Modules</li>
            </ol>
            <!-- breadcrumb -->

            <!-- block header container -->
            <div class="block-header-container">
                <div class="block-header-primary">
                    <h5 class="block-header-title">Modules (<?= $total ?>)</h5>
                    <p class="block-header-subtitle">Manage page modules or sections</p>
                </div>
                <div class="block-header-secondary">
                    <!-- action menu -->
                    <nav class="action-menu action-menu-primary">
                        <ul class="action-menu-list">
                            <li class="action-menu-item">
                                <a href="<?= route('admin.modules.create') ?>" class="action-menu-link">
                                    <i class="iconoir-plus-square action-menu-icon"></i>
                                    <span class="action-menu-label">Add new</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <!-- action menu -->
                </div>
            </div>
            <!-- block header container -->
        </div>

        <!-- filter -->
        <div class="block-inline">
            <form action="" class="form block-inline-form" method="get">
                <div class="form-field form-field-search">
                    <span class="form-field-search-icon"><i class="icon icon-search"></i></span>
                    <input type="text" name="filter[title]" class="form-input" placeholder="Search ..." value="<?= $filterInput['title'] ?? '' ?>" />
                </div>

                <div class="form-field">
                    <label for="group_name" class="form-label">By type</label>
                    <select name="filter[group_name]" id="group_name" class="form-select">
                        <option value=""></option>
                        <?php foreach ($groupNames as $groupName): ?>
                            <option value="<?= $groupName ?>" <?= isset($filterInput['group_name']) && $filterInput['group_name'] == $groupName ? 'selected' : '' ?>><?= $groupName ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="status" class="form-label">By status</label>
                    <select name="filter[status]" id="status" class="form-select">
                        <option value=""></option>
                        <option value="draft" <?= isset($filterInput['status']) && $filterInput['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= isset($filterInput['status']) && $filterInput['status'] == 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>

                <div class="block-inline-button-group">
                    <button type="submit" class="button button-primary">Filter</button>
                    <?php if (isset($filterInput)) : ?>
                        <a href="<?= route('admin.modules.index') ?>" class="button button-outline">Reset filter</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <!-- filter -->

        <div class="table-responsive">
            <table class="table table-primary full-width">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Module settings</th>
                        <th>Group</th>
                        <th>Created</th>
                        <th>Last updated</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules->data as $module): ?>
                        <tr>
                            <td><a href="<?= route('admin.modules.edit', ['param' => $module->id]) ?>"><?= $module->title ?></a></td>
                            <td><?= $module->admin_module_setting->name ?> (<?= $module->admin_module_setting->settings_count ?>)</td>
                            <td><?= $module->group_name ?></td>
                            <td><?= diffForHumans($module->created_at) ?></td>
                            <td><?= diffForHumans($module->updated_at) ?></td>
                            <td><span class="badge<?= $module->status == 'published' ? ' badge-success' : ' badge-info' ?>"><?= $module->status ?></span></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if ($total == 0) : ?>
                        <tr>
                            <td colspan="6">It looks like this table decided to go minimalist. No records here!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php $this->inject('admin/partials/admin-pagination', ['p_data' => $modules, 'p_page_numbers' => $pageNumbers]) ?>
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>