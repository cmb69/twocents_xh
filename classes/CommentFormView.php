<?php

/**
 * The comment form views.
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
 * The comment form views.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_CommentFormView
{
    /**
     * The comment.
     *
     * @var Twocents_Comment
     */
    protected $comment;

    /**
     * Initializes a new instance.
     *
     * @param Twocents_Comment $comment A comment.
     *
     * @return void
     */
    public function __construct(Twocents_Comment $comment = null)
    {
        if (isset($comment)) {
            $this->comment = $comment;
        } else {
            $this->comment = Twocents_Comment::make(null, null);
        }
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    public function render()
    {
        global $plugin_tx;

        $url = XH_hsc($this->getUrl());
        return '<form class="twocents_form" method="post" action="' . $url . '">'
            . $this->renderHiddenFormFields()
            . $this->renderUserInput()
            . $this->renderEmailInput()
            . $this->renderMessageTextarea()
            . $this->renderCaptcha()
            . $this->renderButtons()
            . '</form>';
    }

    /**
     * Renders the hidden form fields.
     *
     * @return string (X)HTML.
     *
     * @global XH_CSRFProtection The CSRF protector.
     */
    protected function renderHiddenFormFields()
    {
        global $_XH_csrfProtection;

        $html = '';
        if ($this->comment->getId()) {
            $html .= $_XH_csrfProtection->tokenInput();
        }
        $html .= tag(
            'input type="hidden" name="twocents_id" value="'
            . XH_hsc($this->comment->getId()) . '"'
        );
        return $html;
    }

    /**
     * Renders the user input field.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderUserInput()
    {
        global $plugin_tx;

        return '<div><label><span>' . $plugin_tx['twocents']['label_user']
            . '</span>'
            . tag(
                'input type="text" name="twocents_user" value="'
                . XH_hsc($this->comment->getUser())
                . '" size="20" required="required"'
            )
            . '</label></div>';
    }

    /**
     * Renders the email input field.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderEmailInput()
    {
        global $plugin_tx;

        return '<div><label><span>' . $plugin_tx['twocents']['label_email']
            . '</span>'
            . tag(
                'input type="email" name="twocents_email" value="'
                . XH_hsc($this->comment->getEmail())
                . '" size="20" required="required"'
            )
            . '</label></div>';
    }

    /**
     * Renders the message textarea.
     *
     * @return string
     *
     * @global array The localization of the plugins.
     */
    protected function renderMessageTextarea()
    {
        global $plugin_tx;

        return '<div><label><span>' . $plugin_tx['twocents']['label_message']
            . '</span>'
            . '<textarea name="twocents_message" cols="50" rows="8"'
            . ' required="required">'
            . XH_hsc($this->comment->getMessage()) . '</textarea></label></div>';
    }

    /**
     * Renders the CAPTCHA, if configured and available.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The configuration of the plugins.
     */
    protected function renderCaptcha()
    {
        global $pth, $plugin_cf;

        $pluginname = $plugin_cf['twocents']['captcha_plugin'];
        $filename = $pth['folder']['plugins'] . $pluginname . '/captcha.php';
        if (!XH_ADM && $pluginname && is_readable($filename)) {
            include_once $filename;
            return call_user_func($pluginname . '_captcha_display');
        } else {
            return '';
        }
    }

    /**
     * Renders the form buttons.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderButtons()
    {
        global $plugin_tx;

        $ptx = $plugin_tx['twocents'];
        $action = $this->comment->getId() ? 'update' : 'add';
        $html = '<div class="twocents_form_buttons">'
            . '<button type="submit" name="twocents_action" value="' . $action
            . '_comment">' . $ptx['label_' . $action] . '</button>';
        if ($this->comment->getId()) {
            $html .= '<a href="' . $this->getUrl() . '">'
                . $ptx['label_cancel'] . '</a>';
        }
        $html .= '<button type="reset">' . $ptx['label_reset'] . '</button>'
            . '</div>';
        return $html;
    }

    /**
     * Returns the URL to post or link to.
     *
     * @return string
     *
     * @global string The script name.
     */
    protected function getUrl()
    {
        global $sn;

        $queryString = preg_replace(
            '/&twocents_id=[^&]+/', '', $_SERVER['QUERY_STRING']
        );
        return $sn . '?' . $queryString;
    }
}

?>
