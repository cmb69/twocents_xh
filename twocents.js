/**
 * Twocents_XH
 *
 * @author  Christoph M. Becker <cmbecker69@gmx.de>
 * @license GPL-3.0+
 */

/*jslint browser: true, maxlen: 80 */
/*global TWOCENTS */

(function () {
    "use strict";

    var init;

    /**
     * Convert all relevant anchor elements to buttons.
     *
     * @returns {undefined}
     */
    function convertAsToButtons() {
        var divs, i, as;

        /**
         * Convert a single anchor element to a button.
         *
         * @param {HTMLAnchorElement} a
         *
         * @returns {undefined}
         */
        function convertAToButton(a) {
            var button;

            function relocate() {
                window.location.href = a.href;
            }

            button = document.createElement("button");
            //button.type = "button";
            button.setAttribute("type", "button");
            button.onclick = relocate;
            button.innerHTML = a.innerHTML;
            a.parentNode.replaceChild(button, a);
        }

        divs = document.getElementsByTagName("div");
        for (i = 0; i < divs.length; i += 1) {
            if (divs[i].className === "twocents_admin_tools" ||
                    divs[i].className === "twocents_form_buttons") {
                as = divs[i].getElementsByTagName("a");
                if (as.length === 1) {
                    convertAToButton(as[0]);
                }
            }
        }
    }

    /**
     * Adds a delete confirmatio to all relevant elements.
     *
     * @returns {undefined}
     */
    function addDeleteConfirmation() {
        var divs, i, form;

        /**
         * Displays a confirmation dialog.
         *
         * @returns {boolean}
         */
        function confirmDeletion() {
            return window.confirm(TWOCENTS.message_delete);
        }

        /**
         * Removes the submit handler of the respective form.
         *
         * @returns {undefined}
         */
        function removeSubmitHandler(event) {
            event = event || window.event;
            (event.target || event.srcElement).form.onsubmit = null;
        }

        divs = document.getElementsByTagName("div");
        for (i = 0; i < divs.length; i += 1) {
            if (divs[i].className === "twocents_admin_tools") {
                form = divs[i].getElementsByTagName("form")[0];
                form.getElementsByTagName("button")[0].onclick =
                        removeSubmitHandler;
                form.onsubmit = confirmDeletion;
            }
        }
    }

    /**
     * Prepares the form.
     *
     * @returns {undefined}
     */
    function prepareForm() {
        var buttons, i, button;

        /**
         * Submits a form.
         *
         * @param {HTMLFormElement} form
         *
         * @returns {undefined}
         */
        function submit(form) {
            var request;

            /**
             * Serializes (application/x-www-form-urlencoded) the form data.
             *
             * @returns {string}
             */
            function serialize() {
                var params, pairs, prop;

                /**
                 * Returns an object with properties for relevant parameter.
                 *
                 * @returns {Object}
                 */
                function getParams() {
                    var params, elements, i, element;

                    params = {};
                    elements = form.elements;
                    for (i = 0; i < elements.length; i += 1) {
                        element = elements[i];
                        if (element.name) {
                            params[element.name] = element.value;
                        }
                    }
                    params.twocents_ajax = '';
                    return params;
                }

                params = getParams();
                pairs = [];
                for (prop in params) {
                    if (params.hasOwnProperty(prop)) {
                        pairs.push(encodeURIComponent(prop) + "="
                                + encodeURIComponent(params[prop]));
                    }
                }
                return pairs.join("&");
            }

            /**
             * Ready state change callbak.
             *
             * @returns {undefined}
             */
            function onreadystatechange() {
                var commentsDiv, scrollMarker;

                if (request.readyState === 4 && request.status === 200) {
                    commentsDiv = form.parentNode;
                    commentsDiv.innerHTML = request.responseText;
                    commentsDiv.className = "";
                    init();
                    scrollMarker = document.getElementById(
                        "twocents_scroll_marker"
                    );
                    if (scrollMarker) {
                        scrollMarker.scrollIntoView(
                            scrollMarker.nextSibling.nodeName.toLowerCase() ===
                                    "p"
                        );
                    }
                }
            }

            if (typeof XMLHttpRequest === "undefined") {
                return false;
            }
            request = new XMLHttpRequest();
            request.open("POST", window.location.href);
            request.setRequestHeader("Content-Type",
                    "application/x-www-form-urlencoded");
            request.onreadystatechange = onreadystatechange;
            request.send(serialize());
            form.parentNode.className = "twocents_loading";
            return true;
        }

        /**
         * Updates the textarea from the content-editable.
         *
         * @param {Event} event
         *
         * @returns {boolean}
         */
        function update(event) {
            var form, textarea, editor, nextSibling;

            event = event || window.event;
            form = event.target || event.srcElement;
            textarea = form.getElementsByTagName("textarea")[0];
            if ((nextSibling = textarea.parentNode.nextSibling)) {
                editor = nextSibling.nextSibling;
                textarea.value = editor.innerHTML;
            }
            return true;
        }

        /**
         * Event handler for form submit.
         *
         * @param {Event} event
         *
         * @returns {boolean}
         */
        function onsubmit(event) {
            var form;

            event = event || window.event;
            form = event.target || event.srcElement;
            update(event);
            return !submit(form);
        }

        /**
         * Hides a form.
         *
         * @param {HTMLFormElement} form
         *
         * @returns {undefined}
         */
        function hideForm(form) {
            var button;

            if (form.previousSibling.nodeName.toLowerCase() !== "p") {
                form.style.display = "none";
                button = document.createElement("button");
                //button.type = "button";
                button.setAttribute("type", "button");
                button.className = "twocents_write_button";
                button.onclick = function () {
                    form.style.display = "";
                    button.parentNode.removeChild(button);
                    form.elements.twocents_user.focus();
                };
                button.innerHTML = TWOCENTS.label_new;
                form.parentNode.appendChild(button);
            }
        }

        /**
         * Resets the editor to the value of the textarea.
         *
         * @param {Event} event
         *
         * @returns {undefined}
         */
        function reset(event) {
            var button, divs, i;

            event = event || window.event;
            button = event.target || event.srcElement;
            divs = button.form.getElementsByTagName("div");
            for (i = 0; i < divs.length; i += 1) {
                if (divs[i].className === "twocents_editor") {
                    divs[i].innerHTML =
                            button.form.elements.twocents_message.value;
                }
            }
        }

        buttons = document.getElementsByTagName("button");
        for (i = 0; i < buttons.length; i += 1) {
            button = buttons[i];
            if (button.name === "twocents_action") {
                if (button.value === "add_comment") {
                    button.form.onsubmit = onsubmit;
                    hideForm(button.form);
                } else if (button.value === "update_comment") {
                    button.form.onsubmit = update;
                }
            } else if (button.type === "reset") {
                button.onclick = reset;
            }
        }
    }

    /**
     * Makes the editors.
     *
     * @returns {undefined}
     */
    function makeEditors() {
        var textareas, i, textarea, div;

        /**
         * Makes an editor.
         *
         * @param {HTMLTextAreaElement} textarea
         *
         * @returns {undefined}
         */
        function makeEditor(textarea) {
            var div, button, div2, buttons, prop;

            /**
             * Toggles bold on/off for the selection or at the insertion point.
             *
             * @returns {undefined}
             */
            function bold() {
                document.execCommand("bold");
                div.focus();
            }

            /**
             * Toggles italics on/off for the selection or at the insertion
             * point.
             *
             * @returns {undefined}
             */
            function italic() {
                document.execCommand("italic");
                div.focus();
            }

            /**
             * Prompts for an URI and creates an anchor link from the selection.
             *
             * @returns {undefined}
             */
            function link() {
                var url;

                url = window.prompt("URL");
                document.execCommand("createLink", false, url);
                div.focus();
            }

            /**
             * Removes the anchor element from a selected anchor link.
             *
             * @returns {undefined}
             */
            function unlink() {
                document.execCommand("unlink");
                div.focus();
            }

            div2 = document.createElement("div");
            div2.className = "twocents_editor_toolbar";
            div = document.createElement("div");
            div.className = "twocents_editor";
            div.innerHTML = textarea.value;
            textarea.parentNode.parentNode.appendChild(div2);
            textarea.parentNode.parentNode.appendChild(div);
            textarea.style.display = "none";
            buttons = {
                "bold": bold,
                "italic": italic,
                "link": link,
                "unlink": unlink
            };
            for (prop in buttons) {
                if (buttons.hasOwnProperty(prop)) {
                    button = document.createElement("button");
                    //button.type = "button";
                    button.setAttribute("type", "button");
                    button.innerHTML = prop;
                    button.onclick = buttons[prop];
                    div2.appendChild(button);
                }
            }
            div.contentEditable = true;
            div.onkeypress = function () {
                var textContent = div.textContent || div.innerText;

                if (!textContent) {
                    document.execCommand("formatBlock", false, "P");
                }
            };
            textarea.required = false;
            textarea.parentNode.onclick = function () {
                div.focus();
            };
        }

        div = document.createElement("div");
        if (typeof div.contentEditable === "undefined"
                || typeof document.execCommand === "undefined") {
            return;
        }
        textareas = document.getElementsByTagName("textarea");
        for (i = 0; i < textareas.length; i += 1) {
            textarea = textareas[i];
            if (textarea.name === "twocents_message") {
                makeEditor(textarea);
            }
        }
    }

    /**
     * Initializes the plugin.
     *
     * @returns {undefined}
     */
    init = function () {
        convertAsToButtons();
        addDeleteConfirmation();
        prepareForm();
        if (TWOCENTS.comments_markup === "HTML") {
            makeEditors();
        }
    };

    init();
}());
