/**
 * @file      The plugin's JavaScript.
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   $Id$
 */

/*global TWOCENTS */

(function () {
    "use strict";

    function convertAsToButtons() {
        var divs, i, as;

        function convertAToButton(a) {
            var button;

            function relocate() {
                window.location.href = a.href;
            }

            button = document.createElement("button");
            button.type = "button";
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

    function addDeleteConfirmation() {
        var divs, i, form;

        function confirmDeletion() {
            return window.confirm(TWOCENTS.deleteMessage);
        }

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

    function addAjaxSubmission() {
        var buttons, i, button;

        function submit(form) {
            var request;

            function serialize() {
                var params, pairs, prop;

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

            function onreadystatechange() {
                var commentsDiv, scrollMarker;

                if (request.readyState === 4 && request.status === 200) {
                    commentsDiv = form.parentNode;
                    commentsDiv.innerHTML = request.responseText;
                    commentsDiv.className = "";
                    convertAsToButtons();
                    addDeleteConfirmation();
                    addAjaxSubmission();
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

        function onsubmit() {
            return !submit(button.form);
        }

        buttons = document.getElementsByTagName("button");
        for (i = 0; i < buttons.length; i += 1) {
            button = buttons[i];
            if (button.name === "twocents_action" &&
                    button.value === "add_comment") {
                button.form.onsubmit = onsubmit;
            }
        }
    }

    convertAsToButtons();
    addDeleteConfirmation();
    addAjaxSubmission();
}());
