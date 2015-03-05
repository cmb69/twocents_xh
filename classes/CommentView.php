<?php

/**
 * The comment views.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2015 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

/**
 * The comment views.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_CommentView
{
    /**
     * The comment to render.
     *
     * @var Twocents_Comment
     */
    private $_comment;

    /**
     * The current comment, if any.
     *
     * @var Twocents_Comment
     */
    private $_currentComment;

    /**
     * Initializes a new instance.
     *
     * @param Twocents_Comment $comment        A comment to render.
     * @param Twocents_Comment $currentComment The posted comment.
     *
     * @return void
     */
    public function __construct(
        Twocents_Comment $comment, Twocents_Comment $currentComment = null
    ) {
        $this->_comment = $comment;
        $this->_currentComment = $currentComment;
    }

    /**
     * Renders a certain comment.
     *
     * @return string (X)HTML.
     */
    public function render()
    {
        $id = $this->_isCurrentComment()
            ? ''
            : ' id="twocents_comment_' . $this->_comment->getId() . '"';
        $class = $this->_comment->isVisible() ? '' : ' twocents_hidden';
        $html = '<div' . $id . ' class="twocents_comment' . $class . '">';
        if ($this->_isCurrentComment()) {
            $view = new Twocents_CommentFormView($this->_currentComment);
            $html .= $view->render();
        } else {
            if (XH_ADM) {
                $html .= $this->_renderAdminTools();
            }
            $html .= '<p>' . $this->_renderHeading() . '</p>'
                . '<blockquote>' . $this->_renderMessage()
                . '</blockquote>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Renders the admin tools.
     *
     * @return string (X)HTML.
     */
    private function _renderAdminTools()
    {
        return '<div class="twocents_admin_tools">'
            . $this->_renderEditLink()
            . $this->_renderDeleteForm()
            . '</div>';
    }

    /**
     * Renders the delete form.
     *
     * @return string (X)HTML.
     *
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _renderDeleteForm()
    {
        global $plugin_tx, $_XH_csrfProtection;

        $hideLabel = $this->_comment->isVisible()
            ? $plugin_tx['twocents']['label_hide']
            : $plugin_tx['twocents']['label_show'];
        return '<form method="post" action="' . XH_hsc($this->_getUrl()) . '">'
            . $_XH_csrfProtection->tokenInput()
            . tag(
                'input type="hidden" name="twocents_id" value="'
                . $this->_comment->getId() . '"'
            )
            . '<button type="submit" name="twocents_action"'
            . ' value="toggle_visibility">' . $hideLabel . '</button>'
            . '<button type="submit" name="twocents_action" value="remove_comment">'
            . $plugin_tx['twocents']['label_delete'] . '</button>'
            . '</form>';
    }

    /**
     * Renders the edit link.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderEditLink()
    {
        global $plugin_tx;

        $url = $this->_getUrl() . '&twocents_id=' . $this->_comment->getId();
        return '<a href="' . XH_hsc($url) . '">'
            . $plugin_tx['twocents']['label_edit'] . '</a>';
    }

    /**
     * Renders the comment heading.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderHeading()
    {
        global $plugin_tx;

        $date = date(
            $plugin_tx['twocents']['format_date'], $this->_comment->getTime()
        );
        $time = date(
            $plugin_tx['twocents']['format_time'], $this->_comment->getTime()
        );
        return strtr(
            $plugin_tx['twocents']['format_heading'],
            array(
                '{DATE}' => $date,
                '{TIME}' => $time,
                '{USER}' => XH_hsc($this->_comment->getUser())
            )
        );
    }

    /**
     * Renders the comment message.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     */
    private function _renderMessage()
    {
        global $plugin_cf;

        if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
            return $this->_comment->getMessage();
        } else {
            return preg_replace(
                '/(?:\r\n|\r|\n)/', tag('br'), XH_hsc($this->_comment->getMessage())
            );
        }
    }

    /**
     * Returns the URL to post or link to.
     *
     * @return string
     *
     * @global string The script name.
     */
    private function _getUrl()
    {
        global $sn;

        $queryString = preg_replace(
            '/&twocents_id=[^&]+/', '', $_SERVER['QUERY_STRING']
        );
        return $sn . '?' . $queryString;
    }

    /**
     * Returns whether a comment is the current comment.
     *
     * @return bool
     */
    private function _isCurrentComment()
    {
        return isset($this->_currentComment)
            && $this->_currentComment->getId() == $this->_comment->getId();
    }
}

?>
