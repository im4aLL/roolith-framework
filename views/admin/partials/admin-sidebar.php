<div>
    <h2><a href="<?= route('admin.home') ?>" class="nav__logo">CMS</a> <span>v<?= getVersion() ?></span></h2>
</div>

<nav class="nav">
    <ul class="nav__list">
        <li class="nav__item">
            <a href="<?= route('admin.home') ?>" class="nav__link">Dashboard</a>
        </li>
        <li class="nav__item">
            <a href="<?= route('admin.pages.index') ?>" class="nav__link">Pages</a>
        </li>
        <li class="nav__item">
            <a href="<?= route('admin.categories.index') ?>" class="nav__link">Categories</a>
        </li>
        <li class="nav__item">
            <a href="<?= route('admin.modules.index') ?>" class="nav__link">Modules</a>
        </li>
        <li class="nav__item">
            <a href="<?= route('admin.module-settings.index') ?>" class="nav__link">Module Settings</a>
        </li>
        <li class="nav__item">
            <a href="<?= route('admin.file.manager') ?>" class="nav__link">File Manager</a>
        </li>

        <li class="nav__item">
            <a href="#" class="nav__link">Settings</a>

            <ul class="nav__list nav__list--sub">
                <li class="nav__item">
                    <a href="<?= route('admin.users.create') ?>" class="nav__link">Change Password</a>
                </li>
                <li class="nav__item">
                    <a href="<?= route('admin.users.create') ?>" class="nav__link">Site Settings</a>
                </li>
            </ul>
        </li>

        <li class="nav__item">
            <a href="<?= route('admin.auth.logout') ?>" class="nav__link">Sign Out</a>
        </li>
    </ul>
</nav>
