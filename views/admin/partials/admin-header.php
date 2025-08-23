<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $this->escape('title') ?></title>
    <?php $this->inject('admin/partials/admin-inject-editor') ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= $this->url('assets/css/admin.css' . '?v=' . getVersion()) ?>">

    <script>
        if (typeof Quill === 'undefined') {
            var Quill = {
                import: () => null,
                register: () => null,
            };
        }
    </script>
</head>
<body>
