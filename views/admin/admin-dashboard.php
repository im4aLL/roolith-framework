<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3><?= $this->escape('content') ?></h3>

        <p>You have last logged in at <time datetime="2021-01-01T00:00:00+00:00"><?= $lastLoggedIn ?></time></p>

        <ul class="card">
            <li class="card__item"><span class="card__info"><?= $pageCount->published_count ?></span> Published Pages</li>
            <li class="card__item"><span class="card__info"><?= $pageCount->draft_count ?></span> Unpublished Pages</li>
            <li class="card__item"><span class="card__info"><?= $moduleCount->published_count ?></span> Published Modules</li>
            <li class="card__item"><span class="card__info"><?= $moduleCount->draft_count ?></span> Unpublished Modules</li>
            <li class="card__item"><span class="card__info"><?= $moduleSettingsCount->total ?></span> Module settings</li>
            <li class="card__item"><span class="card__info"><?= $categoryCount->total ?></span> Categories</li>
        </ul>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
