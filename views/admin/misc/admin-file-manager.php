<?php $this->inject('admin/partials/admin-header'); ?>

<!-- main -->
<main class="layout" id="js-layout">
    <?php $this->inject('admin/partials/admin-layout-header-n-primary') ?>

    <!-- right -->
    <div class="layout-secondary">
        <div class="block-header">
            <!-- breadcrumb -->
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= route('admin.home') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">File manager</li>
            </ol>
            <!-- breadcrumb -->

            <!-- block header container -->
            <div class="block-header-container">
                <div class="block-header-primary">
                    <h5 class="block-header-title"><?= $this->escape('title') ?></h5>
                    <p class="block-header-subtitle">List of assets and files in your site</p>
                </div>
            </div>
            <!-- block header container -->
        </div>

        <!-- content -->
        <ol class="breadcrumb file-manager-breadcrumb">
            <li class="breadcrumb-item"><a href="?">Home</a></li>
            <?php if ($currentPath): ?>
                <?php
                $pathParts = explode('/', $currentPath);
                $buildPath = '';
                foreach ($pathParts as $part):
                    $buildPath .= ($buildPath ? '/' : '') . $part;
                ?>
                    <li class="breadcrumb-item"><a href="?path=<?= urlencode($buildPath) ?>"><?= htmlspecialchars($part) ?></a></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ol>

        <div class="fm__content">
            <?php if ($message): ?>
                <p class="fm__message"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <div class="block-grid">
                <div class="block-grid-item">
                    <form method="post" class="form block-inline-form">
                        <div class="form-field">
                            <input type="hidden" name="action" value="create_folder">
                            <input type="text" name="folder_name" placeholder="Folder name" class="form-input" required>
                        </div>
                        <div class="form-field">
                            <button type="submit" class="button">Create Folder</button>
                        </div>
                    </form>
                </div>

                <div class="block-grid-item">
                    <form method="post" enctype="multipart/form-data" class="form block-inline-form">
                        <div class="form-field">
                            <input type="hidden" name="action" value="upload_file">
                            <input type="file" name="file" class="form-file" required>
                        </div>
                        <div class="form-field">
                            <button type="submit" class="button">Upload File</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-primary full-width file-manager-table">
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
                                    <a href="?path=<?= urlencode(dirname($currentPath)) ?>" class="file-manager-label" style="opacity: 0.5">
                                        <i class="iconoir-media-image-folder"></i> ... (Parent Directory)
                                    </a>
                                </td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($contents['directories'] as $dir): ?>
                            <tr>
                                <td>
                                    <a href="?path=<?= urlencode($dir['path']) ?>" class="file-manager-label">
                                        <?= getIconHtmlByExtension() ?>
                                        <?= htmlspecialchars($dir['name']) ?>
                                    </a>
                                </td>
                                <td><?= $fm->formatBytes($dir['size']) ?></td>
                                <td><?php $dateTime = \Carbon\Carbon::parse($dir['modified']);
                                    echo '<span title="' . $dateTime->toDayDateTimeString() . '">' . $dateTime->diffForHumans() . '</span>'; ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_path" value="<?= htmlspecialchars($dir['path']) ?>">
                                        <button type="submit" class="button button-text button-danger button-small" onclick="return confirm('Delete this folder?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php foreach ($contents['files'] as $file): ?>
                            <tr>
                                <td>
                                    <a href="<?= htmlspecialchars($fm->getDirectFileUrl($file['path'])) ?>" target="_blank" class="file-manager-label">
                                        <?= getIconHtmlByExtension($file['extension']) ?> <?= htmlspecialchars($file['name']) ?>
                                    </a>
                                </td>
                                <td><?= $fm->formatBytes($file['size']) ?></td>
                                <td><?php $dateTime = \Carbon\Carbon::parse($file['modified']);
                                    echo '<span title="' . $dateTime->toDayDateTimeString() . '">' . $dateTime->diffForHumans() . '</span>'; ?></td>
                                <td>
                                    <form method="post" class="form form--inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_path" value="<?= htmlspecialchars($file['path']) ?>">
                                        <button type="submit" class="button button-text button-danger button-small" onclick="return confirm('Delete this file?')">Delete</button>
                                    </form>
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
        </div>
        <!-- content -->
    </div>
    <!-- right -->
</main>
<!-- main -->

<?php $this->inject('admin/partials/admin-footer') ?>