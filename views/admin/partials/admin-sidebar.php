<?php
$activeRoute = getActiveRoute();
$activeRouteNameArray = explode('.', $activeRoute['name']);
$activeRouteName = null;

if (is_array($activeRouteNameArray) && isset($activeRouteNameArray[1])) {
    $activeRouteName = $activeRouteNameArray[1];
}
?>

<!-- sidebar nav -->
<nav class="sidebar-nav" id="js-sidebar-nav-list">
    <!-- group -->
    <div class="sidebar-nav-group">
        <div class="sidebar-nav-header">General</div>

        <ul class="sidebar-nav-list">
            <li class="sidebar-nav-list-item<?= $activeRouteName === 'home' ? ' is-active' : '' ?>">
                <a href="<?= route('admin.home') ?>" class="sidebar-nav-list-link">
                    <i class="iconoir-home"></i>
                    <span class="sidebar-nav-list-label">Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-list-item<?= $activeRouteName === 'pages' ? ' is-active' : '' ?>">
                <a href="<?= route('admin.pages.index') ?>" class="sidebar-nav-list-link">
                    <i class="iconoir-multiple-pages"></i>
                    <span class="sidebar-nav-list-label">Pages</span>
                </a>
            </li>
            <li class="sidebar-nav-list-item<?= $activeRouteName === 'categories' ? ' is-active' : '' ?>">
                <a href="<?= route('admin.categories.index') ?>" class="sidebar-nav-list-link">
                    <i class="iconoir-keyframes"></i>
                    <span class="sidebar-nav-list-label">Categories</span>
                </a>
            </li>
            <li class="sidebar-nav-list-item<?= $activeRouteName === 'modules' ? ' is-active' : '' ?>">
                <a href="<?= route('admin.modules.index') ?>" class="sidebar-nav-list-link">
                    <i class="iconoir-xray-view"></i>
                    <span class="sidebar-nav-list-label">Modules</span>
                </a>
            </li>
            <li class="sidebar-nav-list-item<?= $activeRouteName === 'file' ? ' is-active' : '' ?>">
                <a href="<?= route('admin.file.manager') ?>" class="sidebar-nav-list-link">
                    <i class="iconoir-folder"></i>
                    <span class="sidebar-nav-list-label">File Manager</span>
                </a>
            </li>
            <li class="sidebar-nav-list-item<?= $activeRouteName === 'messages' ? ' is-active' : '' ?>">
                <a href="<?= route('admin.messages.index') ?>" class="sidebar-nav-list-link">
                    <i class="iconoir-message"></i>
                    <span class="sidebar-nav-list-label">Messages</span>
                    <span class="sidebar-nav-list-badge badge">99</span>
                </a>
            </li>
        </ul>
    </div>
    <!-- group -->

    <!-- group -->
    <div class="sidebar-nav-group">
        <div class="sidebar-nav-header">Settings</div>

        <ul class="sidebar-nav-list">
            <li class="sidebar-nav-list-item<?= $activeRouteName === 'module-settings' ? ' is-active' : '' ?>">
                <a href="<?= route('admin.module-settings.index') ?>" class="sidebar-nav-list-link">
                    <i class="iconoir-rubik-cube"></i>
                    <span class="sidebar-nav-list-label">Module Settings</span>
                </a>
            </li>
            <li class="sidebar-nav-list-item<?= $activeRouteName === 'siteSettings' ? ' is-active' : '' ?>">
                <a href="<?= route('admin.siteSettings') ?>" class="sidebar-nav-list-link">
                    <i class="iconoir-settings"></i>
                    <span class="sidebar-nav-list-label">Site Settings</span>
                </a>
            </li>
        </ul>
    </div>
    <!-- group -->

    <!-- group -->
    <div class="sidebar-nav-group">
        <div class="sidebar-nav-header">Account</div>

        <ul class="sidebar-nav-list">
            <li class="sidebar-nav-list-item<?= $activeRouteName === 'auth' ? ' is-active' : '' ?>">
                <a href="<?= route('admin.auth.changePassword') ?>" class="sidebar-nav-list-link">
                    <i class="iconoir-password-cursor"></i>
                    <span class="sidebar-nav-list-label">Change Password</span>
                </a>
            </li>
            <li class="sidebar-nav-list-item<?= $activeRouteName === 'auth' ? ' is-active' : '' ?>">
                <a href="<?= route('admin.auth.logout') ?>" class="sidebar-nav-list-link">
                    <i class="iconoir-log-out"></i>
                    <span class="sidebar-nav-list-label">Sign Out</span>
                </a>
            </li>
        </ul>
    </div>
    <!-- group -->
</nav>
<!-- sidebar nav -->