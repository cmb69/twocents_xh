<?php

use Plib\View;

/**
 * @var View $this
 * @var string $action
 * @var string $label
 * @var string $comment_id
 * @var string $comment_user
 * @var string $comment_email
 * @var string $comment_message
 * @var string $captcha
 * @var bool $admin
 * @var string $module
 * @var string $url
 * @var string $cancel_url
 * @var ?string $csrf_token
 * @var list<string> $errors
 * @var array<string,mixed> $conf
 */
?>
<!-- twocents comment form -->
<script type="module" src="<?=$this->esc($module)?>"></script>
<form class="twocents_form" method="post" action="<?=$this->esc($url)?>" data-config='<?=$this->json($conf)?>'>
<?if (!$admin):?>
  <p class="xh_info"><?=$this->text('message_moderation')?></p>
<?endif?>
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text($error)?></p>
<?endforeach?>
<?if (isset($csrf_token)):?>
  <input type="hidden" name="xh_csrf_token" value="<?=$this->esc($csrf_token)?>">
<?endif?>
  <div>
    <label>
      <span><?=$this->text('label_user')?></span>
      <input type="text" name="twocents_user" value="<?=$this->esc($comment_user)?>" size="20" required minlength="2" maxlength="100">
    </label>
  </div>
  <div>
    <label>
      <span><?=$this->text('label_email')?></span>
      <input type="email" name="twocents_email" value="<?=$this->esc($comment_email)?>" size="20" required minlength="2" maxlength="100">
    </label>
  </div>
  <div>
    <label>
      <span><?=$this->text('label_message')?></span>
      <textarea name="twocents_message" cols="50" rows="8" required minlength="2" maxlength="2000"><?=$this->esc($comment_message)?></textarea>
    </label>
  </div>
  <?=$this->raw($captcha)?>
  <div class="twocents_form_buttons">
    <button name="twocents_do"><?=$this->text($label)?></button>
    <a href="<?=$this->esc($cancel_url)?>"><?=$this->text('label_cancel')?></a>
    <button type="reset"><?=$this->text('label_reset')?></button>
  </div>
</form>
