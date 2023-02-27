<?php

use Twocents\Value\Comment;
use Twocents\Infra\Url;
use Twocents\Infra\View;

/**
 * @var View $this
 * @var Url $url
 * @var Comment $comment
 * @var string|null $csrfToken
 * @var string $captcha
 * @var string $action
 */

?>

<form class="twocents_form" method="post" action="<?=$url?>">
<?if (isset($csrfToken)):?>
  <input type="hidden" name="xh_csrf_token" value="<?=$csrfToken?>">
<?endif?>
  <input type="hidden" name="twocents_id" value="<?=$comment->id()?>">
  <div>
    <label>
      <span><?=$this->text('label_user')?></span>
      <input type="text" name="twocents_user" value="<?=$comment->user()?>" size="20" required="required">
    </label>
  </div>
  <div>
    <label>
      <span><?=$this->text('label_email')?></span>
      <input type="email" name="twocents_email" value="<?=$comment->email()?>" size="20" required="required">
    </label>
  </div>
  <div>
    <label>
      <span><?=$this->text('label_message')?></span>
      <textarea name="twocents_message" cols="50" rows="8" required="required"><?=$comment->message()?></textarea>
    </label>
  </div>
  <?=$captcha?>
  <div class="twocents_form_buttons">
    <button type="submit" name="twocents_action" value="<?=$action?>_comment"><?=$this->text("label_{$action}")?></button>
<?php if ($comment->id()):?>
    <a href="<?=$url?>"><?=$this->text('label_cancel')?></a>
<?php endif?>
    <button type="reset"><?=$this->text('label_reset')?></button>
  </div>
</form>
