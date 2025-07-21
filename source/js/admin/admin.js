import $ from "jquery";
import { RichTextEditor } from "./rich-text-editor";
import { Post } from "./post";
import { SerializeObject } from "./serialize-object";
import { DeleteItem } from "./delete-item";
import { Repeater } from "./repeater";
import { DeleteFile } from "./delete-file";
import { Draggable } from "./draggable";

window.$ = window.jQuery = $;

$(function () {
    new SerializeObject();

    const editors = $(".form__field--editor");

    if (editors.length > 0) {
        $.each(editors, (index, editor) => {
            const richTextField = $(editor).find(".form__editor");

            new RichTextEditor(richTextField.get(0));
        });
    }

    new Post();
    new DeleteItem();
    new Repeater();
    new DeleteFile();
    new Draggable();
});
