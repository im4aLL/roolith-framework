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
        });
    }

    toggleDarkMode() {
        // settings
        const lightModeIcon = "iconoir-half-moon";
        const darkModeIcon = "iconoir-sun-light";
        const darkModeClass = "theme-dark";
        const storageKey = "theme";

        // Check for saved theme preference in localStorage
        const savedTheme = localStorage.getItem(storageKey);
        if (savedTheme) {
            document.body.classList.add(savedTheme);
            const icon = document
                .getElementById("js-toggle-mode")
                .querySelector("i");
            icon.className =
                savedTheme === darkModeClass ? lightModeIcon : darkModeIcon;
        }

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

            // store the theme preference in localStorage
            localStorage.setItem(
                storageKey,
                document.body.classList.contains(darkModeClass)
                    ? darkModeClass
                    : ""
            );
        });
    }
}
