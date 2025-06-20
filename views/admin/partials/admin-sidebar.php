<div>
    <h3><a href="<?= route('admin.home') ?>" class="nav__logo">CMS</a> <span>v<?= getVersion() ?></span></h3>
</div>

<nav class="nav">
    <ul class="nav__list">
        <li class="nav__item">
            <a href="<?= route('admin.home') ?>" class="nav__link">Dashboard</a>
        </li>
        <li class="nav__item">
            <a href="<?= route('admin.posts.index') ?>" class="nav__link">Pages</a>
        </li>
        <li class="nav__item">
            <a href="<?= route('admin.posts.index') ?>" class="nav__link">Modules</a>
        </li>
        <li class="nav__item">
            <a href="<?= route('admin.users.index') ?>" class="nav__link">Settings</a>

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
            <a href="<?= route('admin.posts.index') ?>" class="nav__link">Sign Out</a>
        </li>
    </ul>
</nav>