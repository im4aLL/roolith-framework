export class Repeater {
    constructor() {
        this.init();
    }

    init() {
        this.addNewFieldButtonClickHandler();
        this.removeFieldButtonClickHandler();
        this.dragHandler();
    }

    addNewFieldButtonClickHandler() {
        $(".js-add-field").on("click", function () {
            const cloneElement = $(this)
                .closest(".block-repeater")
                .find(".block-repeater-list .block-repeater-item")
                .last()
                .clone();

            $(this)
                .closest(".block-repeater")
                .find(".block-repeater-list")
                .append(cloneElement);

            cloneElement.find(".form-input").val("").first().trigger("focus");
        });
    }

    removeFieldButtonClickHandler() {
        $(".block-repeater-list").on("click", ".js-remove-field", function () {
            console.log(this);

            if (confirm("Are you sure you want to remove this field?")) {
                $(this).closest(".block-repeater-item").remove();
            }
        });
    }

    dragHandler() {
        let $draggedItem = null;

        $(".block-repeater-list").on(
            "dragstart",
            ".block-repeater-item",
            function (e) {
                $draggedItem = $(this);
                $(this).addClass("dragging");
                e.originalEvent.dataTransfer.effectAllowed = "move";
            }
        );

        $(".block-repeater-list").on(
            "dragend",
            ".block-repeater-item",
            function () {
                $(this).removeClass("dragging");
                $draggedItem = null;
            }
        );

        $(".block-repeater-list").on(
            "dragover",
            ".block-repeater-item",
            function (e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = "move";
            }
        );

        $(".block-repeater-list").on(
            "dragenter",
            ".block-repeater-item",
            function (e) {
                e.preventDefault();

                if ($(this).is($draggedItem)) {
                    return;
                }

                const draggedIndex = $draggedItem.index();
                const targetIndex = $(this).index();

                if (draggedIndex < targetIndex) {
                    $(this).after($draggedItem);
                } else {
                    $(this).before($draggedItem);
                }
            }
        );

        $(".block-repeater-list").on("drop", function (e) {
            e.preventDefault();
        });
    }
}
