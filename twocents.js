/*!
 * Copyright 2014-2017 Christoph M. Becker
 *
 * This file is part of Twocents_XH.
 *
 * Twocents_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Twocents_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Twocents_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

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
                    return params;
                }

                params = getParams();
                pairs = [];
                for (prop in params) {
                    if (params.hasOwnProperty(prop)) {
                        pairs.push(encodeURIComponent(prop) + "=" +
                                   encodeURIComponent(params[prop]));
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
                    while (commentsDiv
                            && commentsDiv.className !== "twocents_container") {
                        commentsDiv = commentsDiv.parentNode;
                    }
                    commentsDiv.innerHTML = request.responseText;
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
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
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
            var form, textarea, divs, i, editor;

            event = event || window.event;
            form = event.target || event.srcElement;
            textarea = form.getElementsByTagName("textarea")[0];
            divs = form.getElementsByTagName("div");
            for (i = 0; i < divs.length; i++) {
                if (divs[i].className === "twocents_editor") {
                    editor = divs[i];
                    break;
                }
            }
            if (editor) {
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

            function showForm() {
                form.style.display = "";
                button.parentNode.removeChild(button);
                form.elements.twocents_user.focus();
            }

            if (form.previousSibling.nodeName.toLowerCase() !== "p") {
                form.style.display = "none";
                button = document.createElement("button");
                //button.type = "button";
                button.setAttribute("type", "button");
                button.className = "twocents_write_button";
                button.onclick = showForm;
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

            function onkeypress() {
                var textContent = div.textContent || div.innerText;

                if (!textContent) {
                    document.execCommand("formatBlock", false, "P");
                }
            }

            function setButtonStates() {
                var button, state;

                button = document.getElementById("twocents_tool_bold");
                button.disabled = !document.queryCommandEnabled("bold");
                state = document.queryCommandState("bold");
                button.style.borderStyle = state ? "inset" : "";
                button = document.getElementById("twocents_tool_italic");
                button.disabled = !document.queryCommandEnabled("italic");
                state = document.queryCommandState("italic");
                button.style.borderStyle = state ? "inset" : "";
                button = document.getElementById("twocents_tool_link");
                button.disabled = !document.queryCommandEnabled("createLink");
                button = document.getElementById("twocents_tool_unlink");
                button.disabled = !document.queryCommandEnabled("unlink");
            }

            function focus() {
                div.focus();
            }

            /**
             * Toggles bold on/off for the selection or at the insertion point.
             *
             * @returns {undefined}
             */
            function bold() {
                document.execCommand("bold");
                div.focus();
                setButtonStates();
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
                setButtonStates();
            }

            /**
             * Prompts for an URI and creates an anchor link from the selection.
             *
             * @returns {undefined}
             */
            function link() {
                var url;

                url = window.prompt(TWOCENTS.message_link, "");
                if (url) {
                    document.execCommand("createLink", false, url);
                }
                div.focus();
                setButtonStates();
            }

            /**
             * Removes the anchor element from a selected anchor link.
             *
             * @returns {undefined}
             */
            function unlink() {
                document.execCommand("unlink");
                div.focus();
                setButtonStates();
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
                bold: bold,
                italic: italic,
                link: link,
                unlink: unlink
            };
            for (prop in buttons) {
                if (buttons.hasOwnProperty(prop)) {
                    button = document.createElement("button");
                    button.id = "twocents_tool_" + prop;
                    //button.type = "button";
                    button.setAttribute("type", "button");
                    button.innerHTML = TWOCENTS["label_" + prop];
                    button.onclick = buttons[prop];
                    div2.appendChild(button);
                }
            }
            div.contentEditable = true;
            div.onkeypress = onkeypress;
            div.onkeyup = setButtonStates;
            div.onmouseup = setButtonStates;
            textarea.required = false;
            textarea.parentNode.onclick = focus;
            setButtonStates();
        }

        div = document.createElement("div");
        if (typeof div.contentEditable === "undefined" ||
            typeof document.execCommand === "undefined") {
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
