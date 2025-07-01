import $ from "jquery";
import { RichTextEditor } from "./rich-text-editor";
import { Post } from "./post";
import { SerializeObject } from "./serialize-object";

window.$ = window.jQuery = $;

$(function () {
    new SerializeObject();
    new RichTextEditor();
    new Post();
});
