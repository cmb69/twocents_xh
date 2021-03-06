/**
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

    function find(selector, target) {
        target = target || document;
        if (typeof target.querySelectorAll !== "undefined") {
            return target.querySelectorAll(selector);
        } else {
            return [];
        }
    }

    function each(items, func) {
        for (var i = 0, length = items.length; i < length; i++) {
            func(items[i]);
        }
    }

    function on(target, event, listener) {
        if (typeof target.addEventListener !== "undefined") {
            target.addEventListener(event, listener, false);
        } else if (typeof target.attachEvent !== "undefined") {
            target.attachEvent("on" + event, listener);
        }
    }

    function doGetRequest(url) {
        var request = new XMLHttpRequest();
        request.open("GET", url);
        request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        request.onreadystatechange = (function onreadystatechange() {
            var request = this;
            if (request.readyState === 4 && request.status === 200) {
                each(find(".twocents_container"), function (container) {
                    container.innerHTML = request.responseText;
                    init();
                    container.className = container.className.replace(/ twocents_loading$/, "");
                });
            }
        });
        request.send(null);
        each(find(".twocents_container"), function (container) {
            container.className += " twocents_loading";
        });
    }

    function doPostRequest(url, payload) {
        var request = new XMLHttpRequest();
        request.open("POST", url);
        request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        request.onreadystatechange = (function () {
            var request = this;
            if (request.readyState === 4 && request.status === 200) {
                each(find(".twocents_container"), function (container) {
                    container.innerHTML = request.responseText;
                    container.className = container.className.replace(/ twocents_loading$/, "");
                });
                init();
                each(find("#twocents_scroll_marker"), function (scrollMarker) {
                    scrollMarker.scrollIntoView(
                        scrollMarker.nextSibling.nodeName.toLowerCase() ===
                                "p"
                    );
                });
            }
        });
        request.send(payload);
        each(find(".twocents_container"), function (container) {
            container.className += " twocents_loading";
        });
    }

    function ajaxifyPagination() {
        each(find(".twocents_pagination a"), function (anchor) {
            anchor.onclick = (function () {
                doGetRequest(this.href);
                return false;
            });
        });
    }

    function ajaxifyAdminTools() {
        var currentButton;
        each(find(".twocents_admin_tools form button"), function (button) {
            button.onclick = (function () {
                currentButton = this;
            });
        });
        each(find(".twocents_admin_tools form"), function (form) {
            form.onsubmit = (function () {
                if (currentButton.value === "remove_comment") {
                    var confirmed = window.confirm(TWOCENTS.message_delete);
                    if (!confirmed) {
                        return false;
                    }
                }
                var params = [];
                each([this["xh_csrf_token"], this["twocents_id"], currentButton], function (element) {
                    params.push(encodeURIComponent(element.name) + "=" + encodeURIComponent(element.value));
                });
                doPostRequest(this.action, params.join("&"));
                return false;
            });       
        });
    }

    /**
     * Convert all relevant anchor elements to buttons.
     *
     * @returns {undefined}
     */
    function convertAnchorsToButtons() {
        each(find(".twocents_admin_tools a, .twocents_form_buttons a"), function (anchor) {
            var button = document.createElement("button");
            //button.type = "button";
            button.setAttribute("type", "button");
            button.onclick = (function () {
                doGetRequest(anchor.href);
                return false;
            });
            button.innerHTML = anchor.innerHTML;
            anchor.parentNode.replaceChild(button, anchor);
        });
    }

    /**
     * Serializes (application/x-www-form-urlencoded) the form data.
     *
     * @returns {string}
     */
    function serialize(form) {
        var params, pairs, prop;

        params = {};
        each(find("[name]", form), function (element) {
            params[element.name] = element.value;
        });
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
     * Prepares the form.
     *
     * @returns {undefined}
     */
    function prepareForm() {

        /**
         * Updates the textarea from the content-editable.
         *
         * @param {Event} event
         *
         * @returns {boolean}
         */
        function update(form) {
            each(find(".twocents_editor", form), function (editor) {
                each(find("textarea", form), function (textarea) {
                    textarea.value = editor.innerHTML;
                });
            });
            return true;
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
                button.onclick = (function () {
                    form.style.display = "";
                    this.parentNode.removeChild(this);
                    form.elements.twocents_user.focus();
                });
                button.innerHTML = TWOCENTS.label_new;
                form.parentNode.insertBefore(button, form.nextSibling);
            }
        }

        each(find(".twocents_comments button"), function (button) {
            if (button.name === "twocents_action") {
                if (button.value === "add_comment" || button.value === "update_comment") {
                    button.form.onsubmit = (function () {
                        update(this);
                        doPostRequest(this.action, serialize(this));
                        return false;
                    });
                    if (button.value === "add_comment") {
                        hideForm(button.form);
                    }
                }
            } else if (button.type === "reset") {
                button.onclick = (function () {
                    var form = this.form;
                    each(find(".twocents_editor", form), function (editor) {
                        editor.innerHTML = form.elements.twocents_message.value;
                    });
                });
            }
        });
    }

    /**
     * Makes an editor.
     *
     * @param {HTMLTextAreaElement} textarea
     *
     * @returns {undefined}
     */
    function makeEditor(textarea) {
        var div, button, div2, buttons, prop;

        var updateButtonStates = (function () {
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
        });

        div2 = document.createElement("div");
        div2.className = "twocents_editor_toolbar";
        div = document.createElement("div");
        div.className = "twocents_editor";
        div.innerHTML = textarea.value;
        textarea.parentNode.parentNode.appendChild(div2);
        textarea.parentNode.parentNode.appendChild(div);
        textarea.style.display = "none";
        buttons = ({
            bold: (function () {
                document.execCommand("bold");
                div.focus();
                updateButtonStates();
            }),
            italic: (function () {
                document.execCommand("italic");
                div.focus();
                updateButtonStates();
            }),
            link: (function () {
                var url = window.prompt(TWOCENTS.message_link, "");
                if (url) {
                    document.execCommand("createLink", false, url);
                }
                div.focus();
                updateButtonStates();
            }),
            unlink: (function () {
                document.execCommand("unlink");
                div.focus();
                updateButtonStates();
            })
        });
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
        div.onkeypress = (function () {
            var textContent = div.textContent || div.innerText;
            if (!textContent) {
                document.execCommand("formatBlock", false, "P");
            }
        });
        div.onkeyup = updateButtonStates;
        div.onmouseup = updateButtonStates;
        textarea.required = false;
        textarea.parentNode.onclick = (function () {
            div.focus();
        });
        updateButtonStates();
    }

    function isRteSupported() {
        var div = document.createElement("div");
        return (typeof div.contentEditable !== "undefined" &&
                typeof document.execCommand !== "undefined");
    }

    /**
     * Initializes the plugin.
     *
     * @returns {undefined}
     */
    init = function () {
        ajaxifyPagination();
        convertAnchorsToButtons();
        ajaxifyAdminTools();
        prepareForm();
        if (TWOCENTS.comments_markup === "HTML") {
            if (isRteSupported()) {
                each(find("textarea[name='twocents_message']"), function (textarea) {
                    makeEditor(textarea);
                })
            }
        }
    };

    on(window, "load", init);
}());
