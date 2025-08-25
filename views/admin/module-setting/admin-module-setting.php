<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout<?= getUiStateByKey('compact') == 'compact' ? ' layout-compact' : null ?>" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <div class="block-header">
            <!-- breadcrumb -->
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Module settings</li>
            </ol>
            <!-- breadcrumb -->

            <!-- block header container -->
            <div class="block-header-container">
                <div class="block-header-primary">
                    <h5 class="block-header-title">Module Settings (<?= $total ?>)</h5>
                    <p class="block-header-subtitle">You can add new module settings and configure them</p>
                </div>
                <div class="block-header-secondary">
                    <!-- action menu -->
                    <nav class="action-menu action-menu-primary">
                        <ul class="action-menu-list">
                            <li class="action-menu-item">
                                <a href="<?= route('admin.module-settings.create') ?>" class="action-menu-link">
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

        <!-- content -->
        <div class="table-responsive">
            <table class="table table-primary full-width">
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
        </div>

        <?php $this->inject('admin/partials/admin-pagination', ['p_data' => $moduleSettings, 'p_page_numbers' => $pageNumbers]) ?>
        <!-- content -->
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>
