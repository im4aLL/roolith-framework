<?php $this->inject('admin/partials/admin-header'); ?>


<!-- main -->
<main class="layout" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <div class="block-header">
            <!-- breadcrumb -->
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Change password</li>
            </ol>
            <!-- breadcrumb -->

            <!-- block header container -->
            <div class="block-header-container">
                <div class="block-header-primary">
                    <h5 class="block-header-title">Account</h5>
                    <p class="block-header-subtitle">Update you account information</p>
                </div>
            </div>
            <!-- block header container -->
        </div>

        <!-- message -->
        <?php if (isset($error_message)) : ?>
            <div class="message message-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if (isset($success_message)) : ?>
            <div class="message message-success"><?= $success_message ?></div>
        <?php endif; ?>
        <!-- message -->

        <!-- content -->
        <form action="<?= route('admin.auth.updatePassword') ?>" class="form" method="post">
            <div class="block-form-group">
                <div class="block-form">
                    <div class="block-form-primary">
                        <h5 class="block-form-title">Change password</h5>
                        <p class="block-form-text">Enter your new password and retype it.</p>
                    </div>
                    <div class="block-form-secondary">
                        <!-- fields -->
                        <div class="block-grid">
                            <div class="block-grid-item">
                                <div class="form-field">
                                    <label for="current_password" class="form-label">Current password</label>
                                    <input type="password" name="current_password" id="current_password" class="form-input">
                                </div>
                            </div>
                        </div>

                        <div class="block-grid">
                            <div class="block-grid-item">
                                <div class="form-field">
                                    <label for="new_password" class="form-label">New password</label>
                                    <input type="password" name="new_password" id="new_password" class="form-input">
                                </div>
                            </div>

                            <div class="block-grid-item">
                                <div class="form-field">
                                    <label for="re_new_password" class="form-label">Retype new password</label>
                                    <input type="password" name="re_new_password" id="re_new_password" class="form-input">
                                </div>
                            </div>
                        </div>
                        <!-- fields -->
                    </div>
                </div>

                <div class="block-form-action">
                    <div class="button-bundle">
                        <a class="button button-outline" href="<?= route('admin.home') ?>">Cancel</a>
                        <button class="button button-primary" type="submit">Save</button>
                    </div>
                </div>
            </div>
        </form>
        <!-- content -->
    </div>
    <!-- right -->
</main>
<!-- main -->


<?php $this->inject('admin/partials/admin-footer') ?>