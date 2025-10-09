export class GlobalSearch {
    constructor() {
        this.$fieldEl = $("#global-search-input");
        this.badgeClassMap = {
            page: "badge-primary",
            category: "badge-secondary",
            module: "badge-tertiary",
            moduleSetting: "badge-danger",
            default: "badge-success",
        };
        this.hasHintData = false;
        this.currentFocusIndex = -1;

        this.init();
    }

    init() {
        this.watchSearchInput();
        this.outsideClickHandler();
        this.insideClickHandler();
        this.watchArrowKeyEventOnInput();
    }

    watchSearchInput() {
        const self = this;
        const actionUrl = this.$fieldEl.closest(".form").attr("action");
        const debouncedSearch = self.debounce(self.searchByValue.bind(self), 300);

        this.$fieldEl.on("input", "input", function () {
            self.reset();
            self.hasHintData = false;
            const value = $(this).val();

            if (value.length > 2) {
                debouncedSearch(value, actionUrl);
            }
        });
    }

    searchByValue(value, actionUrl) {
        const self = this;
        this.$fieldEl.addClass("loading");

        $.ajax({
            url: actionUrl,
            type: "GET",
            data: {
                q: value,
            },
            success: function (response) {
                if (response.status === "success") {
                    self.showHint(response.payload);
                } else {
                    console.error("Error:", response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("Error:", error);
            },
            complete: function () {
                self.$fieldEl.removeClass("loading");
            },
        });
    }

    showHint(data) {
        if (data.length === 0) {
            return;
        }

        this.hasHintData = true;
        const self = this;

        self.$fieldEl.addClass("hint");

        let html = "<ul>";
        $.each(data, function (index, item) {
            const badgeClass = self.badgeClassMap[item.type] ?? self.badgeClassMap.default;
            const badgeHtml = `<span class="badge ${badgeClass}">${item.type}</span>`;

            html += `<li><a href="${item.link}">${item.label} ${badgeHtml}</a></li>`;
        });
        html += "</ul>";

        self.$fieldEl.find(".form-field-search-hint").html(html);
    }

    reset() {
        this.$fieldEl.removeClass("hint");
        this.$fieldEl.removeClass("loading");
    }

    outsideClickHandler() {
        const self = this;

        $(document).on("click", function (event) {
            if (!self.$fieldEl.is(event.target) && self.$fieldEl.has(event.target).length === 0) {
                self.reset();
            }
        });
    }

    insideClickHandler() {
        const self = this;

        self.$fieldEl.on("click", function () {
            if (!self.hasHintData) {
                self.reset();
            } else {
                self.$fieldEl.addClass("hint");
            }
        });
    }

    debounce(func, wait) {
        let timeout;

        return function (...args) {
            const context = this;

            if (timeout) {
                clearTimeout(timeout);
            }

            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    watchArrowKeyEventOnInput() {
        const self = this;
        const $hintEl = self.$fieldEl.find(".form-field-search-hint");

        const setFocusToFirstHint = () => {
            const $hints = $hintEl.find("li");
            const $firstHint = $hints.first();

            $hints.removeClass("active");
            $firstHint.addClass("active");
            self.currentFocusIndex = 0;
        };

        const setFocusToLastHint = () => {
            const $hints = $hintEl.find("li");
            const $lastHint = $hints.last();

            $hints.removeClass("active");
            $lastHint.addClass("active");
            self.currentFocusIndex = $hints.length - 1;
        };

        const setFocusToNextHint = () => {
            const $hints = $hintEl.find("li");
            const $currentHint = self.currentFocusIndex === -1 ? $hints.last() : $hints.eq(self.currentFocusIndex);
            const $nextHint = self.currentFocusIndex === -1 ? $hints.first() : $currentHint.next();

            $currentHint.removeClass("active");
            $nextHint.addClass("active");
            self.currentFocusIndex++;

            if (self.currentFocusIndex > $hints.length - 1) {
                setFocusToFirstHint();
            }
        };

        const setFocusToPreviousHint = () => {
            const $hints = $hintEl.find("li");
            const $currentHint = self.currentFocusIndex === -1 ? $hints.first() : $hints.eq(self.currentFocusIndex);
            const $previousHint = self.currentFocusIndex === -1 ? $hints.last() : $currentHint.prev();

            $currentHint.removeClass("active");
            $previousHint.addClass("active");
            self.currentFocusIndex--;

            if (self.currentFocusIndex < 0) {
                setFocusToLastHint();
            }
        };

        this.$fieldEl.on("keydown", "input", function (event) {
            switch (event.key) {
                case "ArrowDown":
                    setFocusToNextHint();
                    break;
                case "ArrowUp":
                    setFocusToPreviousHint();
                    break;
                case "Enter": {
                    event.preventDefault();

                    const $activeHint = $hintEl.find("li.active");
                    if ($activeHint.length === 0) {
                        return;
                    }

                    const href = $activeHint.find("a").attr("href");
                    if (!href) {
                        return;
                    }

                    window.location.href = href;
                }
            }
        });
    }
}
