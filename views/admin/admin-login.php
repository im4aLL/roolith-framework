<?php $this->inject('admin/partials/admin-header'); ?>

<section class="login">
    <h2 class="login__title"><a href="<?= route('admin.home') ?>" class="nav__logo">CMS</a> <span>v<?= getVersion() ?></span></h2>

    <form action="<?= route('admin.auth.verifyCredential') ?>" class="form login__form" method="post">
        <?php if (isset($error_message)) : ?>
            <div class="message message--danger"><?= $error_message ?></div>
        <?php endif; ?>

        <div class="form__field">
            <label for="email" class="form__label">Email address</label>
            <input type="email" name="email" id="email" class="form__input" placeholder="e.g james@example.com">
        </div>

        <div class="form__field">
            <label for="password" class="form__label">Password</label>
            <input type="password" name="password" id="password" class="form__input" placeholder="Enter password">
        </div>

        <button class="button">Sign in</button>
    </form>
</section>

<?php $this->inject('admin/partials/admin-footer') ?>
