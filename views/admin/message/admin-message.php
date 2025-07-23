<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3>Messages (<?= $total ?>)</h3>

        <table class="table">
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

        <?php $this->inject('admin/partials/admin-pagination', ['p_data' => $messages, 'p_page_numbers' => $pageNumbers ]) ?>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
