<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">

        <!-- block header -->
        <div class="block-header">
            <!-- breadcrumb -->
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Pages</li>
            </ol>
            <!-- breadcrumb -->

            <!-- block header container -->
            <div class="block-header-container">
                <div class="block-header-primary">
                    <h5 class="block-header-title">Pages (<?= $total ?>)</h5>
                    <p class="block-header-subtitle">Manage your pages and blogs</p>
                </div>
                <div class="block-header-secondary">
                    <!-- action menu -->
                    <nav class="action-menu action-menu-primary">
                        <ul class="action-menu-list">
                            <li class="action-menu-item">
                                <a href="<?= route('admin.pages.create') ?>" class="action-menu-link">
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
        <!-- block header -->

        <!-- filter -->
        <div class="block-inline">
            <form action="" class="form block-inline-form" method="get">
                <div class="form-field form-field-search">
                    <span class="form-field-search-icon"><i class="icon icon-search"></i></span>
                    <input type="text" name="filter[title]" class="form-input" placeholder="Search ..." value="<?= $filterInput['title'] ?? '' ?>" />
                </div>

                <div class="form-field">
                    <label for="type" class="form-label">By type</label>
                    <select name="filter[type]" id="type" class="form-select">
                        <option value=""></option>
                        <option value="page" <?= isset($filterInput['type']) && $filterInput['type'] == 'page' ? 'selected' : '' ?>>Page</option>
                        <option value="blog" <?= isset($filterInput['type']) && $filterInput['type'] == 'blog' ? 'selected' : '' ?>>Blog</option>
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
                        <a href="<?= route('admin.pages.index') ?>" class="button button-outline">Reset filter</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <!-- filter -->

        <!-- content -->
        <div class="table-responsive">
            <table class="table table-primary full-width">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>User</th>
                        <th>Created</th>
                        <th>Last updated</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages->data as $page): ?>
                        <tr>
                            <td><a href="<?= route('admin.pages.edit', ['param' => $page->id]) ?>"><?= $page->title ?></a></td>
                            <td>/<?= $page->slug ?></td>
                            <td>
                                <span class="badge<?= $page->type == 'page' ? ' badge-primary' : ' badge-secondary' ?>"><?= $page->type ?></span>
                            </td>
                            <td><?= $page->categoryNames ?? '-' ?></td>
                            <td><?= $page->admin_user ? $page->admin_user->name : '-' ?></td>
                            <td><?= diffForHumans($page->created_at) ?></td>
                            <td><?= diffForHumans($page->updated_at) ?></td>
                            <td>
                                <span class="badge<?= $page->status == 'published' ? ' badge-success' : ' badge-info' ?>"><?= $page->status ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if ($total == 0) : ?>
                        <tr>
                            <td colspan="8">Looks like this table decided to go minimalist. No records here!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php $this->inject('admin/partials/admin-pagination', ['p_data' => $pages, 'p_page_numbers' => $pageNumbers]) ?>
        <!-- content -->

    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>