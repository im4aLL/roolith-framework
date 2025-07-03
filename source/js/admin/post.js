export class Post {
    constructor() {
        this.init();
    }

    init() {
        this.ajaxPost();
    }

    ajaxPost() {
        const form = $('form[data-ajax="true"]');

        if (!form) {
            return;
        }

        $(form).on("submit", (e) => {
            e.preventDefault();

            const formData = form.serializeObject();
            const editorData = this._getEditorData();

            if (editorData) {
                formData[editorData.name] = editorData.value;
            }

            const url = form.attr("action");
            const method = form.attr("method");

            this._submitForm(url, method, formData);
        });
    }

    _getEditorData() {
        const editorData = $("#editor-value").html();

        if (!editorData) {
            return null;
        }

        const inputName = $("#editor-value").attr("data-input-name");

        return {
            name: inputName,
            value: editorData,
        };
    }

    _submitForm(url, method, formData) {
        $.ajax({
            url: url,
            type: method,
            data: formData,
            dataType: "json",
            success: (response) => {
                this._resetFormError();

                if (response.status === "error") {
                    this._errorResponseHandler(response);
                } else if (response.status === "success") {
                    this._successResponseHandler(response);
                } else {
                    console.log(response);
                }
            },
            error: (error) => {
                console.log(error);
            },
        });
    }

    _successResponseHandler(response) {
        const { payload } = response;

        if (payload.redirect) {
            window.location.href = payload.redirect;
        }
    }

    _errorResponseHandler(response) {
        const { payload, message } = response;

        this._injectGeneralErrorMessage(message);
        this._injectErrorClassToFields(payload);
    }

    _injectGeneralErrorMessage(message) {
        if (!message) {
            return;
        }

        $("#error-container").html(
            `<div class="message message--danger">${message}</div>`
        );
    }

    _injectErrorClassToFields(payload) {
        if (!payload || (payload && this._getLength(payload) === 0)) {
            return;
        }

        $.each(payload, (inputName, errorArray) => {
            const fieldSelector = `[name="${inputName}"], [data-input-name="${inputName}"]`;
            const field = $(fieldSelector);

            if (field.length > 0) {
                field.closest(".form__field").addClass("form__field--error");
            }
        });
    }

    _resetFormError() {
        $(".form__field").removeClass("form__field--error");
    }

    _getLength(value) {
        if (Array.isArray(value)) {
            return value.length;
        } else if (typeof value === "object" && value !== null) {
            return Object.keys(value).length;
        }

        return 0;
    }
}
