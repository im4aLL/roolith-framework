<?php $this->inject('admin/partials/admin-header'); ?>

<main class="layout">

    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3><?= $this->escape('title') ?></h3>

        <form action="<?= route('admin.auth.updatePassword') ?>" class="form" method="post">
            <?php if (isset($error_message)) : ?>
                <div class="message message--danger"><?= $error_message ?></div>
            <?php endif; ?>

            <?php if (isset($success_message)) : ?>
                <div class="message message--success"><?= $success_message ?></div>
            <?php endif; ?>

            <div class="form__field">
                <label for="current_password" class="form__label">Current password</label>
                <input type="password" name="current_password" id="current_password" class="form__input">
            </div>

            <div class="form__field">
                <label for="new_password" class="form__label">New password</label>
                <input type="password" name="new_password" id="new_password" class="form__input">
            </div>

            <div class="form__field">
                <label for="re_new_password" class="form__label">Retype new password</label>
                <input type="password" name="re_new_password" id="re_new_password" class="form__input">
            </div>

            <button class="button" type="submit">Change password</button>
        </form>
    </section>

</main>

<?php $this->inject('admin/partials/admin-footer') ?>
