<?php $this->inject('admin/partials/admin-header'); ?>

<main class="layout">

    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">

        <h3><?= $this->escape('title') ?></h3>

        <div class="breadcrumb">
            <a href="?">Home</a>
            <?php if ($currentPath): ?>
                <?php
                $pathParts = explode('/', $currentPath);
                $buildPath = '';
                foreach ($pathParts as $part):
                    $buildPath .= ($buildPath ? '/' : '') . $part;
                ?>
                    / <a href="?path=<?= urlencode($buildPath) ?>"><?= htmlspecialchars($part) ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="fm__content">
            <?php if ($message): ?>
                <p class="fm__message"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <div class="form__actions">
                <form method="post" class="form form--inline">
                    <input type="hidden" name="action" value="create_folder">
                    <input type="text" name="folder_name" placeholder="Folder name" class="form__input" required>
                    <button type="submit" class="button">Create Folder</button>
                </form>

                <form method="post" enctype="multipart/form-data" class="form form--inline">
                    <input type="hidden" name="action" value="upload_file">
                    <input type="file" name="file" class="form__input" required>
                    <button type="submit" class="button">Upload File</button>
                </form>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Modified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($currentPath): ?>
                        <tr>
                            <td>
                                <div class="fm__item-name">
                                    <span class="fm__folder-icon"></span>
                                    <a href="?path=<?= urlencode(dirname($currentPath)) ?>">.. (Parent Directory)</a>
                                </div>
                            </td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($contents['directories'] as $dir): ?>
                        <tr>
                            <td>
                                <div class="fm__item-name">
                                    <span class="fm__folder-icon"></span>
                                    <a href="?path=<?= urlencode($dir['path']) ?>"><?= htmlspecialchars($dir['name']) ?></a>
                                </div>
                            </td>
                            <td><?= $fm->formatBytes($dir['size']) ?></td>
                            <td><?php $dateTime = \Carbon\Carbon::parse($dir['modified']);
                                echo '<span title="' . $dateTime->toDayDateTimeString() . '">' . $dateTime->diffForHumans() . '</span>'; ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item_path" value="<?= htmlspecialchars($dir['path']) ?>">
                                    <button type="submit" class="button button--text button--danger" onclick="return confirm('Delete this folder?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php foreach ($contents['files'] as $file): ?>
                        <tr>
                            <td>
                                <div class="fm__item-name">
                                    <span class="fm__file-icon fm__file-icon--<?= $file['extension'] ?>"></span>
                                    <a href="<?= htmlspecialchars($fm->getDirectFileUrl($file['path'])) ?>" target="_blank" class="fm__download-link"><?= htmlspecialchars($file['name']) ?></a>
                                </div>
                            </td>
                            <td><?= $fm->formatBytes($file['size']) ?></td>
                            <td><?php $dateTime = \Carbon\Carbon::parse($file['modified']); echo '<span title="'.$dateTime->toDayDateTimeString().'">'.$dateTime->diffForHumans().'</span>'; ?></td>
                            <td>
                                <div class="fm__file-actions">
                                    <form method="post" class="form form--inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_path" value="<?= htmlspecialchars($file['path']) ?>">
                                        <button type="submit" class="button button--text button--danger" onclick="return confirm('Delete this file?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($contents['directories']) && empty($contents['files'])): ?>
                        <tr>
                            <td colspan="4">No files or folders found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

<?php $this->inject('admin/partials/admin-footer') ?>
