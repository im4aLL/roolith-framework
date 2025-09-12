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

        this.init();
    }

    init() {
        this.watchSearchInput();
        this.outsideClickHandler();
        this.insideClickHandler();
    }

    watchSearchInput() {
        const self = this;
        const actionUrl = this.$fieldEl.closest(".form").attr("action");

        this.$fieldEl.on("input", "input", function () {
            self.reset();
            self.hasHintData = false;
            const value = $(this).val();

            if (value.length > 2) {
                self.searchByValue(value, actionUrl);
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
}
