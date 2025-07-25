<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3><?= $this->escape('content') ?></h3>

        <p>You have last logged in at <?= diffForHumans($lastLoggedIn) ?></p>

        <ul class="card">
            <li class="card__item">
                <a href="<?= route('admin.pages.index') ?>?filter[status]=published">
                    <span class="card__info"><?= $pageCount->published_count ?? 0 ?></span> Published Pages
                </a>
            </li>
            <li class="card__item">
                <a href="<?= route('admin.pages.index') ?>?filter[status]=draft">
                    <span class="card__info"><?= $pageCount->draft_count ?? 0 ?></span> Unpublished Pages
                </a>
            </li>
            <li class="card__item">
                <a href="<?= route('admin.modules.index') ?>?filter[status]=published">
                    <span class="card__info"><?= $moduleCount->published_count ?? 0 ?></span> Published Modules
                </a>
            </li>
            <li class="card__item">
                <a href="<?= route('admin.modules.index') ?>?filter[status]=draft">
                    <span class="card__info"><?= $moduleCount->draft_count ?? 0 ?></span> Unpublished Modules
                </a>
            </li>
            <li class="card__item">
                <a href="<?= route('admin.module-settings.index') ?>">
                    <span class="card__info"><?= $moduleSettingsCount->total ?></span> Module settings
                </a>
            </li>
            <li class="card__item">
                <a href="<?= route('admin.categories.index') ?>">
                    <span class="card__info"><?= $categoryCount->total ?></span> Categories
                </a>
            </li>
            <li class="card__item">
                <a href="<?= route('admin.messages.index') ?>">
                    <span class="card__info"><?= $unreadMessageCount->total ?></span> Unread messages
                </a>
            </li>
        </ul>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>