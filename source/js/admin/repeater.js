export class Repeater {
    constructor() {
        this.init();
    }

    init() {
        this.addNewFieldButtonClickHandler();
        this.removeFieldButtonClickHandler();
        this.watchNoSpaceInput();
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

    toSnakeCase(str) {
        return str
            .replace(/\s+/g, "_") // Replace spaces with underscores
            .replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`) // Handle camelCase
            .replace(/-+/g, "_") // Convert hyphens to underscores
            .replace(/__+/g, "_") // Remove double underscores
            .replace(/^_+|_+$/g, "") // Trim underscores at start/end
            .toLowerCase();
    }

    watchNoSpaceInput() {
        const _this = this;

        $(".form__list").on("blur", ".form__input--no-space", function () {
            const snakeCase = _this.toSnakeCase($(this).val());

            $(this).val(snakeCase);
        });
    }
}
