export class DeleteItem {
    constructor() {
        this.init();
    }

    init() {
        this.ajaxDelete();
    }

    ajaxDelete() {
        const deleteButton = $("#delete-button");

        if (!deleteButton) {
            return;
        }

        deleteButton.on("click", (e) => {
            e.preventDefault();

            if (confirm("Are you sure you want to delete permanently?")) {
                const url = deleteButton.attr("data-url");
                const method = "delete";

                this._submitForm(url, method);
            }
        });
    }

    _submitForm(url, method) {
        $.ajax({
            url: url,
            type: method,
            dataType: "json",
            success: (response) => {
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
        const { message } = response;

        alert(message);
    }
}
