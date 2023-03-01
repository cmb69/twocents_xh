<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var string $action
 * @var string $label
 * @var string $comment_id
 * @var string $comment_user
 * @var string $comment_email
 * @var string $comment_message
 * @var html $captcha
 * @var string $url
 * @var ?string $csrf_token
 */

?>

<form class="twocents_form" method="post" action="<?=$this->esc($url)?>">
<?if (isset($csrf_token)):?>
  <input type="hidden" name="xh_csrf_token" value="<?=$this->esc($csrf_token)?>">
<?endif?>
  <input type="hidden" name="twocents_id" value="<?=$this->esc($comment_id)?>">
  <div>
    <label>
      <span><?=$this->text('label_user')?></span>
      <input type="text" name="twocents_user" value="<?=$this->esc($comment_user)?>" size="20" required="required">
    </label>
  </div>
  <div>
    <label>
      <span><?=$this->text('label_email')?></span>
      <input type="email" name="twocents_email" value="<?=$this->esc($comment_email)?>" size="20" required="required">
    </label>
  </div>
  <div>
    <label>
      <span><?=$this->text('label_message')?></span>
      <textarea name="twocents_message" cols="50" rows="8" required="required"><?=$this->esc($comment_message)?></textarea>
    </label>
  </div>
  <?=$this->raw($captcha)?>
  <div class="twocents_form_buttons">
    <button type="submit" name="twocents_action" value="<?=$this->esc($action)?>_comment"><?=$this->text($label)?></button>
<?if ($comment_id):?>
    <a href="<?=$this->esc($url)?>"><?=$this->text('label_cancel')?></a>
<?endif?>
    <button type="reset"><?=$this->text('label_reset')?></button>
  </div>
</form>
