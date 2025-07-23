<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <ul class="breadcrumb">
            <li class="breadcrumb__item">
                <a href="<?= route('admin.messages.index') ?>" class="breadcrumb__link">Messages</a>
            </li>
            <li class="breadcrumb__item">
                <a href="<?= route('admin.messages.show', ['param' => $message->id]) ?>" class="breadcrumb__link"><?= $message->reference ?></a>
            </li>
        </ul>

        <section>
            <dl>
                <dt>Name</dt>
                <dd><?= $message->name ?></dd>

                <dt>Source</dt>
                <dd><?= $message->type ?></dd>

                <dt>Email address</dt>
                <dd><?= $message->email ?></dd>

                <?php if ($message->fields) : ?>
                    <?php foreach ($message->fields as $fieldLabel => $fieldValue) : ?>
                        <dt><?= $fieldLabel ?></dt>
                        <dd><?= $fieldValue ?></dd>
                    <?php endforeach; ?>
                <?php endif; ?>

                <dt>Message</dt>
                <dd><?= nl2br($message->message) ?></dd>

                <dt>Created</dt>
                <dd><?= diffForHumans($message->created_at) ?></dd>
            </dl>

            <div class="form__cta-group">
                <form action="<?= route('admin.messages.update', ['param' => $message->id]) ?>" method="post">
                    <button type="submit" class="button"><?= $message->is_seen ? 'Mark as unread' : 'Mark as read' ?></button>
                </form>

                <form action="<?= route('admin.messages._destroy', ['param' => $message->id]) ?>" method="post">
                    <button type="submit" class="button button--danger button--text" onclick="return confirm('Are you sure?')">Delete permanently</button>
                </form>
            </div>
        </section>
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
