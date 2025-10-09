export class SiteSettings {
    constructor() {
        this.containerSelector = "#site-settings-field-container";
        this.actionUrl = $(this.containerSelector).data("action-url");

        this._init();
    }

    _init() {
        this._onBlurInputForRepeater();
        this._deleteHandler();
        this._enableDisableFields();
    }

    _enableDisableFields() {
        const self = this;

        $(".block-enable").on("change", ".form-switch-input", function () {
            const blockEnableEl = $(this).closest(".block-enable");
            const actionUrl = blockEnableEl.data("action-url");

            const data = {
                item: $(this).attr("name"),
                value: $(this).is(":checked") ? "true" : "false",
                actionUrl,
            };

            self._submitEnableDisable(data);
        });
    }

    _submitEnableDisable(data) {
        $.ajax({
            url: data.actionUrl,
            method: "POST",
            data: {
                item: data.item,
                value: data.value,
            },
            dataType: "json",
            success: function (data) {
                const { status, message } = data;

                if (status === "error") {
                    alert(message);
                } else {
                    location.reload();
                }
            },
            error: function () {
                alert("Something went wrong");
            },
        });
    }

    _onBlurInputForRepeater() {
        const self = this;

        $(this.containerSelector).on("blur", ".block-repeater .form-input", function () {
            const $section = $(this).closest(".block-repeater-item");
            const item = $section.find(".form-input[name='item']").val();
            const value = $section.find(".form-input[name='value']").val();
            const id = $section.data("id");
            const method = id ? "PUT" : "POST";

            if (item && value) {
                const settings = {
                    data: {
                        item,
                        value,
                    },
                    actionUrl: self.actionUrl,
                    method: method,
                    element: $section,
                };

                if (id) {
                    settings.method = "PUT";
                    settings.actionUrl += "/" + id;
                }

                self._submit(settings);
            }
        });
    }

    _submit(settings) {
        $.ajax({
            url: settings.actionUrl,
            method: settings.method,
            data: settings.data,
            dataType: "json",
            success: function (data) {
                const { status, payload, message } = data;

                if (status === "success" && settings.method === "POST") {
                    settings.element.attr("data-id", payload.id);
                } else if (status === "error") {
                    alert(message);
                }
            },
            error: function () {
                alert("Something went wrong");
            },
        });
    }

    _deleteHandler() {
        const self = this;

        $(this.containerSelector).on("click", ".js-alt-remove-field", function (e) {
            e.stopImmediatePropagation();

            if (!confirm("Are you sure?")) {
                return;
            }

            const $section = $(this).closest(".block-repeater-item");
            const id = $section.data("id");

            if (!id) {
                alert("You don't have to remove this");
                return;
            }

            $.ajax({
                url: self.actionUrl + "/" + id,
                method: "DELETE",
                dataType: "json",
                success: function (data) {
                    const { status, payload, message } = data;

                    if (status === "success") {
                        $section.remove();
                    } else if (status === "error") {
                        alert(message);
                    }
                },
                error: function () {
                    alert("Something went wrong");
                },
            });
        });
    }
}
