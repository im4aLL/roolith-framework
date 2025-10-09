export class DeleteFile {
    constructor() {
        this._init();
    }

    _init() {
        this.ajaxDelete();
    }

    ajaxDelete() {
        const self = this;
        const deleteButton = $(".form__file-delete");

        if (!deleteButton) {
            return;
        }

        deleteButton.on("click", function (e) {
            e.preventDefault();

            if (confirm("Are you sure you want to delete permanently?")) {
                const url = $(this).attr("href");
                const data = {
                    fileName: $(this).attr("data-file"),
                    moduleId: $(this).attr("data-module-id"),
                    moduleDataId: $(this).attr("data-module-data-id"),
                };

                self._submitForm(url, data, this);
            }
        });
    }

    _submitForm(url, data, deleteButton) {
        $.ajax({
            url: url,
            type: "DELETE",
            dataType: "json",
            data,
            success: (response) => {
                if (response.status === "error") {
                    this._errorResponseHandler(response);
                } else if (response.status === "success") {
                    this._successResponseHandler(deleteButton);
                } else {
                    console.log(response);
                }
            },
            error: (error) => {
                console.log(error);
            },
        });
    }

    _errorResponseHandler(response) {
        const { message } = response;

        alert(message);
    }

    _successResponseHandler(deleteButton) {
        $(deleteButton).closest("li").remove();
    }
}
