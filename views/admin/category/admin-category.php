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
                <li class="breadcrumb-item active">Categories</li>
            </ol>
            <!-- breadcrumb -->

            <!-- block header container -->
            <div class="block-header-container">
                <div class="block-header-primary">
                    <h5 class="block-header-title">Categories (<?= $total ?>)</h5>
                    <p class="block-header-subtitle">Manage categories for your pages and blogs</p>
                </div>
                <div class="block-header-secondary">
                    <!-- action menu -->
                    <nav class="action-menu action-menu-primary">
                        <ul class="action-menu-list">
                            <li class="action-menu-item">
                                <a href="<?= route('admin.categories.create') ?>" class="action-menu-link">
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

        <!-- table -->
        <div class="table-responsive">
            <table class="table table-primary full-width">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Created</th>
                        <th>Last updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories->data as $category): ?>
                        <tr>
                            <td><a href="<?= route('admin.categories.edit', ['param' => $category->id]) ?>"><?= $category->name ?></a></td>
                            <td><?= $category->slug ?></td>
                            <td><?= diffForHumans($category->created_at) ?></td>
                            <td><?= diffForHumans($category->updated_at) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if ($total == 0) : ?>
                        <tr>
                            <td colspan="4">Looks like this table decided to go minimalist. No records here!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- table -->

        <?php $this->inject('admin/partials/admin-pagination', ['p_data' => $categories, 'p_page_numbers' => $pageNumbers]) ?>
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>