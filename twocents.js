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

            button = document.createElement("button");
            button.type = "button";
            button.onclick = function () {
                window.location.href = a.href;
            };
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

        divs = document.getElementsByTagName("div");
        for (i = 0; i < divs.length; i += 1) {
            if (divs[i].className === "twocents_admin_tools") {
                form = divs[i].getElementsByTagName("form")[0];
                form.onsubmit = confirmDeletion;
            }
        }
    }

    convertAsToButtons();
    addDeleteConfirmation();
}());
