<?php $this->inject('admin/partials/admin-header'); ?>

<div class="block-auth">
    <div class="block-auth-container">
        <div class="block-auth-header">
            <h5 class="block-auth-title">Welcome Back</h5>
            <p class="block-auth-text">Enter your credentials to access your account.</p>
        </div>

        <?php if (isset($error_message)) : ?>
        <div class="message message-danger">
            <?= $error_message ?>
        </div>
        <?php endif; ?>

        <form action="<?= route('admin.auth.verifyCredential') ?>" class="form" method="post">
            <div class="form-field">
                <label class="form-label" for="email">Email</label>
                <input type="text" class="form-input" name="email" id="email" />
            </div>
            <div class="form-field form-field-password">
                <label class="form-label" for="password">Password</label>
                <input type="password" class="form-input" name="password" id="password" />
                <button class="form-password-toggle" type="button"><i class="iconoir-eye"></i></button>
            </div>
            <div class="form-field">
                <button class="button button-primary">Sign In</button>
            </div>
        </form>

        <div class="block-auth-footer">
            <p class="block-auth-footer-text">
                Forgot your password?
                <a href="" class="block-auth-footer-link">Reset password</a>
            </p>
        </div>
    </div>
</div>

<?php $this->inject('admin/partials/admin-footer') ?>
