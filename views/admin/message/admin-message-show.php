<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout<?= getUiStateByKey('compact') == 'compact' ? ' layout-compact' : null ?>" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <!-- breadcrumb -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= route('admin.messages.index') ?>">Messages</a></li>
            <li class="breadcrumb-item active">
                <?= $message->reference ?>
            </li>
        </ol>
        <!-- breadcrumb -->

        <div class="spacer-20"></div>

        <!-- block header container -->
        <div class="block-header-container">
            <div class="block-header-primary">
                <h5 class="block-header-title">
                    <?= $message->reference ?>
                </h5>
            </div>
            <div class="block-header-secondary">
                <!-- action menu -->
                <nav class="action-menu action-menu-primary">
                    <ul class="action-menu-list">
                        <li class="action-menu-item">
                            <form action="<?= route('admin.messages._destroy', ['param' => $message->id]) ?>"
                                method="post">
                                <button type="submit" class="button button-danger button-text"
                                    onclick="return confirm('Are you sure?')">Delete permanently</button>
                            </form>
                        </li>
                    </ul>
                </nav>
                <!-- action menu -->
            </div>
        </div>
        <!-- block header container -->

        <!-- content -->
        <section>
            <dl>
                <dt>Name</dt>
                <dd>
                    <?= $message->name ?>
                </dd>

                <dt>Source</dt>
                <dd>
                    <?= $message->type ?>
                </dd>

                <dt>Email address</dt>
                <dd>
                    <?= $message->email ?>
                </dd>

                <?php if ($message->fields) : ?>
                <?php foreach ($message->fields as $fieldLabel => $fieldValue) : ?>
                <dt>
                    <?= $fieldLabel ?>
                </dt>
                <dd>
                    <?= $fieldValue ?>
                </dd>
                <?php endforeach; ?>
                <?php endif; ?>

                <dt>Message</dt>
                <dd>
                    <?= nl2br($message->message) ?>
                </dd>

                <dt>Created</dt>
                <dd>
                    <?= diffForHumans($message->created_at) ?>
                </dd>
            </dl>

            <div class="spacer-20"></div>

            <div class="form__cta-group">
                <form action="<?= route('admin.messages.update', ['param' => $message->id]) ?>" method="post">
                    <button type="submit" class="button">
                        <?= $message->is_seen ? 'Mark as unread' : 'Mark as read' ?>
                    </button>
                </form>
            </div>
        </section>
        <!-- content -->
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>
