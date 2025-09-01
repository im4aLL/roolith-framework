import $ from "jquery";
import { RichTextEditor } from "./rich-text-editor";
import { Post } from "./post";
import { SerializeObject } from "./serialize-object";
import { DeleteItem } from "./delete-item";
import { Repeater } from "./repeater";
import { DeleteFile } from "./delete-file";
import { SiteSettings } from "./site-settings";
import { PasswordToggle } from "./password-toggle";
import { Layout } from "./layout";
import { Event } from "./event";

window.$ = window.jQuery = $;
window["Event"] = Event;
window["parseTemplate"] = (template, data) => {
    return template.replace(/\{([\w\.]*)\}/g, (match, key) => {
        const keys = key.split(".");
        let v = data[keys.shift()];

        for (const k of keys) {
            v = v?.[k];
        }

        return v !== undefined && v !== null ? v : "";
    });
};

$(function () {
    new SerializeObject();

    const editors = $(".form-field--editor");

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
    new SiteSettings();
    new PasswordToggle();
    new Layout();
});
