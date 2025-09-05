<?php if ($p_data->total > $p_data->perPage) : ?>
    <nav class="pagination">
        <ul class="pagination-list">
            <li class="pagination-item">
                <a href="<?= $p_data->firstPageUrl ?>" class="pagination-link">
                    <i class="iconoir-fast-arrow-left"></i>
                </a>
            </li>
            <li class="pagination-item">
                <a href="<?= $p_data->prevPageUrl ?>" class="pagination-link">
                    <i class="iconoir-nav-arrow-left"></i>
                </a>
            </li>
            <?php foreach ($p_page_numbers as $pageNumber) : ?>
                <li
                    class="pagination-item is-number<?= $pageNumber == $p_data->currentPage ? ' pagination-item-active' : '' ?>">
                    <a href="<?= $p_data->path ?><?= str_contains($p_data->path, "?") ? "&" : "?" ?>page=<?= $pageNumber ?>" class="pagination-link"><?= $pageNumber ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <li class="pagination-item">
                <a href="<?= $p_data->nextPageUrl ?>" class="pagination-link">
                    <i class="iconoir-nav-arrow-right"></i>
                </a>
            </li>
            <li class="pagination-item">
                <a href="<?= $p_data->lastPageUrl ?>" class="pagination-link">
                    <i class="iconoir-fast-arrow-right"></i>
                </a>
            </li>
        </ul>

        <div class="pagination-meta">
            <div class="pagination-meta-label">
                Total record
                <?= $p_data->total ?>. Showing page
                <strong>
                    <?= $p_data->currentPage ?>
                </strong>
                out of
                <strong>
                    <?= $p_data->lastPage ?>
                </strong>
            </div>
        </div>
    </nav>
<?php endif; ?>
