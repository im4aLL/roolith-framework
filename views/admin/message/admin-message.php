<?php $this->inject('admin/partials/admin-header') ?>

<!-- main -->
<main class="layout" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <div class="block-header">
            <!-- breadcrumb -->
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Messages</li>
            </ol>
            <!-- breadcrumb -->

            <!-- block header container -->
            <div class="block-header-container">
                <div class="block-header-primary">
                    <h5 class="block-header-title">Messages (<?= $total ?>)</h5>
                    <p class="block-header-subtitle">All message sent from your site</p>
                </div>
            </div>
            <!-- block header container -->
        </div>

        <!-- content -->
        <div class="table-responsive">
            <table class="table table-primary full-width">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Source</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages->data as $message): ?>
                        <tr <?= $message->is_seen ? 'style="opacity: 0.5;"' : '' ?>>
                            <td><a href="<?= route('admin.messages.show', ['param' => $message->id]) ?>"><?= $message->reference ?></a></td>
                            <td><?= $message->name ?></td>
                            <td><?= $message->email ?></td>
                            <td><?= $message->type ?></td>
                            <td><?= diffForHumans($message->created_at) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if ($total == 0) : ?>
                        <tr>
                            <td colspan="5">Looks like this table decided to go minimalist. No records here!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php $this->inject('admin/partials/admin-pagination', ['p_data' => $messages, 'p_page_numbers' => $pageNumbers]) ?>
        <!-- content -->
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>