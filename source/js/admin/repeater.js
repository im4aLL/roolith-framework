export class Repeater {
    constructor() {
        this.init();
    }

    init() {
        this.addNewFieldButtonClickHandler();
        this.removeFieldButtonClickHandler();
    }

    addNewFieldButtonClickHandler() {
        $("#add-field").on("click", function () {
            const cloneElement = $(this)
                .closest(".form__field")
                .find(".form__list .form__list-item")
                .last()
                .clone();

            $(this)
                .closest(".form__field")
                .find(".form__list")
                .append(cloneElement);

            cloneElement.find(".form__input").first().val("").trigger("focus");
        });
    }

    removeFieldButtonClickHandler() {
        $(".form__list").on(
            "click",
            ".form__list-item-action .button",
            function () {
                if (confirm("Are you sure you want to remove this field?")) {
                    $(this).closest(".form__list-item").remove();
                }
            }
        );
    }
}
