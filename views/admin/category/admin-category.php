<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3>Categories (<?= $total ?>)</h3>

        <nav class="nav nav--horizontal">
            <ul class="nav__list">
                <li class="nav__item">
                    <a href="<?= route('admin.categories.create') ?>" class="nav__link">Add Category</a>
                </li>
            </ul>
        </nav>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Created</th>
                    <th>Last updated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories->data as $category): ?>
                <tr>
                    <td><a href="<?= route('admin.categories.edit', ['param' => $category->id]) ?>"><?= $category->name ?></a></td>
                    <td><?= $category->slug ?></td>
                    <td><?= diffForHumans($category->created_at) ?></td>
                    <td><?= diffForHumans($category->updated_at) ?></td>
                </tr>
                <?php endforeach; ?>

                <?php if ($total == 0) : ?>
                <tr>
                    <td colspan="4">Looks like this table decided to go minimalist. No records here!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php $this->inject('admin/partials/admin-pagination', ['p_data' => $categories, 'p_page_numbers' => $pageNumbers ]) ?>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
