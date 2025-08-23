<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
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
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>