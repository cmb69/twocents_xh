/**
 * Copyright 2014-2025 Christoph M. Becker
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

// @ts-check

/**
 * @param {string} url
 * @returns {void}
 */
function doGetRequest(url) {
    const request = new XMLHttpRequest();
    request.open("GET", url);
    request.setRequestHeader("X-CMSimple-XH-Request", "twocents");
    request.onreadystatechange = function onreadystatechange() {
        if (request.readyState === 4 && request.status === 200) {
            document.querySelectorAll(".twocents_container").forEach(container => {
                container.innerHTML = request.responseText;
                init();
                container.classList.remove("twocents_loading");
            });
            if (history.state.twocents_url !== url) {
                history.pushState(updatedHistoryState(request.responseURL), document.title, request.responseURL);
            }
        }
    };
    request.send(null);
    document.querySelectorAll(".twocents_container").forEach(container => {
        container.classList.add("twocents_loading");
    });
}

/**
 * @param {string} url
 * @param {FormData} payload
 * @returns {void}
 */
function doPostRequest(url, payload) {
    const request = new XMLHttpRequest();
    request.open("POST", url);
    request.setRequestHeader("X-CMSimple-XH-Request", "twocents");
    request.onreadystatechange = () => {
        if (request.readyState === 4 && request.status === 200) {
            document.querySelectorAll(".twocents_container").forEach(container => {
                container.innerHTML = request.responseText;
                container.classList.remove("twocents_loading");
            });
            init();
            if (request.responseURL !== url) {
                history.pushState(updatedHistoryState(request.responseURL), document.title, request.responseURL);
            }
        }
    };
    request.send(payload);
    document.querySelectorAll(".twocents_container").forEach(container => {
        container.classList.add("twocents_loading");
    });
}

/**
 * @returns {void}
 */
function ajaxifyPagination() {
    document.querySelectorAll(".twocents_pagination a").forEach(anchor => {
        if (!(anchor instanceof HTMLAnchorElement)) return;
        anchor.onclick = event => {
            doGetRequest(anchor.href);
            event.preventDefault();
        };
    });
}

/**
 * @returns {void}
 */
function ajaxifyAdminTools() {
    /** @type {HTMLButtonElement} */
    var currentButton;
    document.querySelectorAll(".twocents_admin_tools form button").forEach(button => {
        if (!(button instanceof HTMLButtonElement)) return;
        button.onclick = () => {
            currentButton = button;
        };
    });
    document.querySelectorAll(".twocents_admin_tools form").forEach(form => {
        if (!(form instanceof HTMLFormElement)) return;
        form.onsubmit = event => {
            if ("confirm" in currentButton.dataset) {
                if (!window.confirm(JSON.parse(currentButton.dataset.confirm || ""))) {
                    event.preventDefault();
                }
            }
            doPostRequest(currentButton.formAction, new FormData(form, currentButton));
            event.preventDefault();
        };
    });
}

/**
 * @returns {void}
 */
function convertAnchorsToButtons() {
    const selector = ".twocents_admin_tools a, .twocents_new_comment a, .twocents_form_buttons a";
    document.querySelectorAll(selector).forEach(anchor => {
        if (!(anchor instanceof HTMLAnchorElement) || anchor.parentElement === null) return;
        const button = document.createElement("button");
        button.type = "button";
        button.onclick = event => {
            doGetRequest(anchor.href);
            event.preventDefault();
        };
        button.innerHTML = anchor.innerHTML;
        anchor.parentElement.replaceChild(button, anchor);
    });
}

/**
 * @returns {void}
 */
function prepareForm() {
    document.querySelectorAll(".twocents_form_buttons button").forEach(button => {
        if (!(button instanceof HTMLButtonElement) || button.form === null) return;
        if (button.name === "twocents_do") {
            const form = button.form;
            button.form.onsubmit = event => {
                form.querySelectorAll(".twocents_editor textarea").forEach(editor => {
                    form.querySelectorAll("textarea").forEach(textarea => {
                        textarea.value = editor.innerHTML;
                    });
                });
                doPostRequest(form.action, new FormData(form, button));
                event.preventDefault();
            };
        } else if (button.type === "reset") {
            button.onclick = () => {
                if (button.form === null) return;
                const form = button.form;
                document.querySelectorAll(".twocents_editor").forEach(editor => {
                    const textarea = form.elements.namedItem("twocents_message");
                    if (!(textarea instanceof HTMLTextAreaElement)) return;
                    editor.innerHTML = textarea.value;
                });
            };
        }
    });
}

/**
 * @param {HTMLTextAreaElement} textarea
 * @returns {void}
 */
function makeEditor(textarea) {
    /** @type {Object<string,string>} */
    var conf;
    /** @type {HTMLDivElement} */
    var div;

    const buttons = [
        {
            name: "bold",
            handler: () => {
                document.execCommand("bold");
                div.focus();
                updateButtonStates();
            },
        }, {
            name: "italic",
            handler: () => {
                document.execCommand("italic");
                div.focus();
                updateButtonStates();
            },
        }, {
            name: "link", 
            handler: () => {
                const url = window.prompt(conf.message_link, "");
                if (url) {
                    document.execCommand("createLink", false, url);
                }
                div.focus();
                updateButtonStates();
            },
        }, {
            name: "unlink",
            handler: () => {
                document.execCommand("unlink");
                div.focus();
                updateButtonStates();
            },
        }
    ];

    /**
     * @returns {boolean}
     */
    function isRteSupported() {
        const div = document.createElement("div");
        return typeof div.contentEditable !== "undefined" &&
            typeof document.execCommand !== "undefined";
    }

    /** @returns {void} */
    function updateButtonStates() {
        /** @type {HTMLButtonElement|null} */
        var button;

        button = document.querySelector("#twocents_tool_bold");
        if (button !== null) {
            button.disabled = !document.queryCommandEnabled("bold");
            button.style.borderStyle = document.queryCommandState("bold") ? "inset" : "";
        }
        button = document.querySelector("#twocents_tool_italic");
        if (button !== null) {
            button.disabled = !document.queryCommandEnabled("italic");
            button.style.borderStyle = document.queryCommandState("italic") ? "inset" : "";
        }
        button = document.querySelector("#twocents_tool_link");
        if (button !== null) {
            button.disabled = !document.queryCommandEnabled("createLink");
        }
        button = document.querySelector("#twocents_tool_unlink");
        if (button !== null) {
            button.disabled = !document.queryCommandEnabled("unlink");
        }
    }

    /**
     * @param {HTMLElement} container
     * @param {HTMLElement} label
     * @returns {void}
     */
    function init(container, label) {
        const div2 = document.createElement("div");
        div2.className = "twocents_editor_toolbar";
        div = document.createElement("div");
        div.className = "twocents_editor";
        div.innerHTML = textarea.value;
        container.appendChild(div2);
        container.appendChild(div);
        textarea.style.display = "none";
        for (let but of buttons) {
            const button = document.createElement("button");
            button.id = "twocents_tool_" + but.name;
            button.type = "button";
            button.innerHTML = conf["label_" + but.name];
            button.onclick = but.handler;
            div2.appendChild(button);
        }
        div.contentEditable = "true";
        div.onkeypress = () => {
            const textContent = div.textContent || div.innerText;
            if (!textContent) {
                document.execCommand("formatBlock", false, "P");
            }
        };
        div.onkeyup = updateButtonStates;
        div.onmouseup = updateButtonStates;
        textarea.required = false;
        label.onclick = () => {
            div.focus();
        };
        updateButtonStates();
    }

    if (!isRteSupported()) {
        return;
    }
    conf = JSON.parse(textarea.dataset.config || "");
    if (conf.comments_markup !== "HTML") return;
    if (textarea.parentElement === null || textarea.parentElement.parentElement === null) return;
    init(textarea.parentElement.parentElement, textarea.parentElement);
}

/**
 * @param {string} url
 * @returns Object
 */
function updatedHistoryState(url) {
    return Object.assign({}, history.state, {"twocents_url": url});
}

/**
 * @returns {void}
 */
function init() {
    ajaxifyPagination();
    convertAnchorsToButtons();
    ajaxifyAdminTools();
    prepareForm();
    document.querySelectorAll("textarea[name='twocents_message']").forEach(textarea => {
        if (!(textarea instanceof HTMLTextAreaElement)) return;
        makeEditor(textarea);
    });
}

history.replaceState(updatedHistoryState(location.href), document.title, location.href);
addEventListener("popstate", event => {
    if ("state" in event && "twocents_url" in event.state) {
        doGetRequest(event.state.twocents_url);
    }
});

init();
