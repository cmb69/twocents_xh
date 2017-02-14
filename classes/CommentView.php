<?php

/**
 * The comment views.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
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
class Twocents_CommentView extends Twocents_View
{
    /**
     * The comment to render.
     *
     * @var Twocents_Comment
     */
    protected $comment;

    /**
     * The current comment, if any.
     *
     * @var Twocents_Comment
     */
    protected $currentComment;

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
        $this->comment = $comment;
        $this->currentComment = $currentComment;
    }

    /**
     * Renders a certain comment.
     *
     * @return string (X)HTML.
     */
    public function render()
    {
        $id = $this->isCurrentComment()
            ? ''
            : ' id="twocents_comment_' . $this->comment->getId() . '"';
        $class = $this->comment->isVisible() ? '' : ' twocents_hidden';
        $html = '<div' . $id . ' class="twocents_comment' . $class . '">';
        if ($this->isCurrentComment()) {
            $view = new Twocents_CommentFormView($this->currentComment);
            $html .= $view->render();
        } else {
            if (XH_ADM) {
                $html .= $this->renderAdminTools();
            }
            $html .= '<div class="twocents_attribution">'
                . $this->renderAttribution() . '</div>'
                . '<div class="twocents_message">' . $this->renderMessage()
                . '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Renders the admin tools.
     *
     * @return string (X)HTML.
     */
    protected function renderAdminTools()
    {
        return '<div class="twocents_admin_tools">'
            . $this->renderEditLink()
            . $this->renderDeleteForm()
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
    protected function renderDeleteForm()
    {
        global $plugin_tx, $_XH_csrfProtection;

        $hideLabel = $this->comment->isVisible()
            ? $plugin_tx['twocents']['label_hide']
            : $plugin_tx['twocents']['label_show'];
        return '<form method="post" action="' . XH_hsc($this->getUrl()) . '">'
            . $_XH_csrfProtection->tokenInput()
            . tag(
                'input type="hidden" name="twocents_id" value="'
                . $this->comment->getId() . '"'
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
    protected function renderEditLink()
    {
        global $plugin_tx;

        $url = $this->getUrl() . '&twocents_id=' . $this->comment->getId();
        return '<a href="' . XH_hsc($url) . '">'
            . $plugin_tx['twocents']['label_edit'] . '</a>';
    }

    /**
     * Renders the comment attribution.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderAttribution()
    {
        global $plugin_tx;

        $date = date(
            $plugin_tx['twocents']['format_date'], $this->comment->getTime()
        );
        $time = date(
            $plugin_tx['twocents']['format_time'], $this->comment->getTime()
        );
        return strtr(
            $plugin_tx['twocents']['format_heading'],
            array(
                '{DATE}' => $date,
                '{TIME}' => $time,
                '{USER}' => XH_hsc($this->comment->getUser())
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
    protected function renderMessage()
    {
        global $plugin_cf;

        if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
            return $this->comment->getMessage();
        } else {
            return preg_replace(
                '/(?:\r\n|\r|\n)/', tag('br'), XH_hsc($this->comment->getMessage())
            );
        }
    }

    /**
     * Returns whether a comment is the current comment.
     *
     * @return bool
     */
    protected function isCurrentComment()
    {
        return isset($this->currentComment)
            && $this->currentComment->getId() == $this->comment->getId();
    }
}

?>
