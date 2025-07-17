<?php if ($p_data->total > $p_data->perPage) : ?>
    <ul class="pagination">
        <li class="pagination__item"><a href="<?= $p_data->prevPageUrl ?>">Previous</a></li>
        <li class="pagination__item"><a href="<?= $p_data->firstPageUrl ?>">First</a></li>
        <?php foreach ($p_page_numbers as $pageNumber) : ?>
            <li class="pagination__item <?= $pageNumber == $p_data->currentPage ? 'pagination__item--active' : '' ?>">
                <a href="<?= $p_data->path ?>?page=<?= $pageNumber ?>">
                    <?= $pageNumber ?>
                </a>
            </li>
        <?php endforeach; ?>
        <li class="pagination__item"><a href="<?= $p_data->lastPageUrl ?>">Last</a></li>
        <li class="pagination__item"><a href="<?= $p_data->nextPageUrl ?>">Next</a></li>
    </ul>

    <small class="is--dimmed">Total row(s) <?= $p_data->total ?>. Showing page <?= $p_data->currentPage ?> out of <?= $p_data->lastPage ?></small>
<?php endif; ?>
