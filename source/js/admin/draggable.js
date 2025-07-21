export class Draggable {
    constructor() {
        this._init();
    }

    _init() {
        let $draggedItem = null;

        $(".form__list--draggable").on(
            "dragstart",
            ".form__list-item",
            function (e) {
                $draggedItem = $(this);
                $(this).addClass("form__list-item--dragging");
                e.originalEvent.dataTransfer.effectAllowed = "move";
            }
        );

        $(".form__list--draggable").on(
            "dragend",
            ".form__list-item",
            function () {
                $(this).removeClass("form__list-item--dragging");
                $draggedItem = null;
            }
        );

        $(".form__list--draggable").on(
            "dragover",
            ".form__list-item",
            function (e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = "move";
            }
        );

        $(".form__list--draggable").on(
            "dragenter",
            ".form__list-item",
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

        $(".form__list--draggable").on("drop", function (e) {
            e.preventDefault();
        });
    }
}
