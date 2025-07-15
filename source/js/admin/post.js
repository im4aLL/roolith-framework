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
                $.each(editorData, (index, data) => {
                    formData[data.name] = data.value;
                });
            }

            const jsFormData = this._getFormFiles(form);

            const url = form.attr("action");
            const method = form.attr("method");

            this._submitForm(url, method, formData, jsFormData);
        });
    }

    _getFormFiles(form) {
        const fileFields = form.find('input[type="file"]');
        const jsFormData = new FormData();

        $.each(fileFields, (index, field) => {
            const count = field.files.length;
            if (count > 0) {
                if (count === 1) {
                    jsFormData.append(field.name, field.files[0]);
                } else {
                    for (let i = 0; i < count; i++) {
                        jsFormData.append(field.name, field.files[i]);
                    }
                }
            }
        });

        return jsFormData;
    }

    _getEditorData() {
        const editorValueElements = $(".form__editor-value");

        if (editorValueElements.length === 0) {
            return null;
        }

        const result = [];

        $.each(editorValueElements, (index, element) => {
            result.push({
                name: $(element).attr("data-input-name"),
                value: $(element).html(),
            });
        });

        return result;
    }

    _submitForm(url, method, formData, jsFormData) {
        const fileCount = this._getFormDataCount(jsFormData);

        if (fileCount > 0) {
            $.each(formData, (key, value) => {
                jsFormData.append(key, value);
            });

            $.ajax({
                url: url,
                type: method,
                data: jsFormData,
                processData: false,
                contentType: false,
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
        } else {
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
    }

    _getFormDataCount(formData) {
        return Array.from(formData.entries()).length;
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

        $("#error-container").html(message);
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
