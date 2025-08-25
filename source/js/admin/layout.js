export class Layout {
    constructor() {
        this.init();
    }

    init() {
        this.toggleCompactMode();
        this.toggleDarkMode();
    }

    toggleCompactMode() {
        const toggleButtonEl = document.getElementById(
            "js-layout-header-sidebar-toggle"
        );
        const layoutEl = document.getElementById("js-layout");
        const sidebarNavListEl = document.getElementById("js-sidebar-nav-list");

        toggleButtonEl.addEventListener("click", () => {
            layoutEl.classList.toggle("layout-compact");
            sidebarNavListEl.classList.toggle("sidebar-nav-compact");

            const isCompact = layoutEl.classList.contains("layout-compact");
            toggleButtonEl
                .querySelectorAll("[data-show-when]")
                .forEach((icon) => {
                    icon.classList.toggle(
                        "is-hidden",
                        icon.dataset.showWhen ===
                            (isCompact ? "expanded" : "collapsed")
                    );
                });

            // store the ui state
            this._storeUiState({
                compact: isCompact ? "compact" : "expanded",
            });
        });
    }

    toggleDarkMode() {
        // settings
        const lightModeIcon = "iconoir-half-moon";
        const darkModeIcon = "iconoir-sun-light";
        const darkModeClass = "theme-dark";

        // Add event listener for dark mode toggle
        const toggleDarkModeEl = document.getElementById("js-toggle-mode");

        toggleDarkModeEl.addEventListener("click", (event) => {
            event.preventDefault();
            document.body.classList.toggle(darkModeClass);

            // Update the icon based on the current theme
            const icon = toggleDarkModeEl.querySelector("i");
            if (document.body.classList.contains(darkModeClass)) {
                icon.className = darkModeIcon;
            } else {
                icon.className = lightModeIcon;
            }

            // store the ui state
            this._storeUiState({
                theme: document.body.classList.contains(darkModeClass)
                    ? darkModeClass
                    : "",
            });
        });
    }

    _storeUiState(data) {
        const url = window.ROOLITH_CONFIG.uiStateAjaxUrl;

        $.ajax({
            url,
            method: "POST",
            data,
            dataType: "json",
        });
    }
}
