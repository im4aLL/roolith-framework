// Import the base block blot
const Block = Quill.import("blots/block");

// Create custom block class using <div>
class DivBlock extends Block {
    static blotName = "block"; // must override the default block
    static tagName = "div"; // use <div> instead of <p>
    static className = null; // optional: remove any default class
}

// Register the custom block
Quill.register(DivBlock, true);

export class RichTextEditor {
    constructor(editorElement) {
        if (!editorElement) {
            throw new Error("editorElement is required");
        }

        this.editor = null;
        this.editorElement = editorElement;
        this.init();
    }

    init() {
        this.initQuill();
        this.addValueToQuill();
        this.watchForChanges();
        this.imageUploadHandler();
        this.registerImageUrlToolbar();
    }

    getToolbar() {
        return [
            ["bold", "italic", "underline", "strike"], // toggled buttons
            ["blockquote", "code-block"],
            ["link", "image", "image-url", "video", "formula"],

            [{ header: 1 }, { header: 2 }], // custom button values
            [{ list: "ordered" }, { list: "bullet" }, { list: "check" }],
            [{ script: "sub" }, { script: "super" }], // superscript/subscript
            [{ indent: "-1" }, { indent: "+1" }], // outdent/indent
            [{ direction: "rtl" }], // text direction

            [{ size: ["small", false, "large", "huge"] }], // custom dropdown
            [{ header: [1, 2, 3, 4, 5, 6, false] }],

            [{ color: [] }, { background: [] }], // dropdown with defaults from theme
            [{ align: [] }],

            ["clean"], // remove formatting button
        ];
    }

    initQuill() {
        const toolbarOptions = this.getToolbar();

        this.editor = new Quill(this.editorElement, {
            modules: {
                syntax: true,
                toolbar: {
                    container: toolbarOptions,
                },
            },
            placeholder: "Type here...",
            theme: "snow",
        });
    }

    _getValueElement() {
        return $(this.editorElement)
            .closest(".form__field")
            .find(".form__editor-value");
    }

    watchForChanges() {
        this.editor.on("text-change", () => {
            const html = this.editor.getSemanticHTML();
            const valueInputElement = this._getValueElement();

            valueInputElement.html(html);
        });
    }

    imageUploadHandler() {
        this.editor.getModule("toolbar").addHandler("image", () => {
            this._selectLocalImage();
        });
    }

    _selectLocalImage() {
        const input = document.createElement("input");
        input.setAttribute("type", "file");
        input.click();

        // Listen upload local image and save to server
        input.onchange = () => {
            const file = input.files[0];

            // file type is only image.
            if (/^image\//.test(file.type)) {
                this._saveToServer(file);
            } else {
                console.warn("You could only upload images.");
            }
        };
    }

    _saveToServer(file) {
        const fd = new FormData();
        fd.append("image", file);

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "/admin/pages/file/upload", true);
        xhr.onload = () => {
            if (xhr.status === 200) {
                this._insertToEditor(xhr.response);
            }
        };
        xhr.send(fd);
    }

    _insertToEditor(url) {
        const range = this.editor.getSelection();
        this.editor.insertEmbed(range.index, "image", url);
    }

    addValueToQuill() {
        const value = this._getValueElement().html();

        if (!value) {
            return;
        }

        const delta = this.editor.clipboard.convert({ html: value });

        this.editor.setContents(delta, "silent");
    }

    registerImageUrlToolbar() {
        const imageUrlButton = document.querySelector(".ql-image-url");

        imageUrlButton.addEventListener("click", () => {
            const range = this.editor.getSelection();
            const value = prompt("What is the image URL");

            this.editor.insertEmbed(
                range.index,
                "image",
                value,
                Quill.sources.USER
            );
        });
    }
}
