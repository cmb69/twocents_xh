<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var string $id
 * @var string $css_class
 * @var bool $is_current_comment
 * @var ?html $form
 * @var ?bool $is_admin
 * @var ?string $url
 * @var ?string $edit_url
 * @var ?string $comment_id
 * @var ?string $visibility
 * @var ?html $attribution
 * @var ?html $message
 * @var ?string $csrf_token
 */

?>

<div id="<?=$this->esc($id)?>" class="twocents_comment <?=$this->esc($css_class)?>">
<?if ($is_current_comment):?>
  <?=$this->raw($form)?>
<?else:?>
<?  if ($is_admin):?>
  <div class="twocents_admin_tools">
    <a href="<?=$this->esc($edit_url)?>"><?=$this->text('label_edit')?></a>
    <form method="post" action="<?=$this->esc($url)?>">
      <input type="hidden" name="xh_csrf_token" value="<?=$this->esc($csrf_token)?>">
      <input type="hidden" name="twocents_id" value="<?=$this->esc($comment_id)?>">
      <button type="submit" name="twocents_action" value="toggle_visibility"><?=$this->text($visibility)?></button>
      <button type="submit" name="twocents_action" value="remove_comment"><?=$this->text('label_delete')?></button>
    </form>
  </div>
<?  endif?>
  <div class="twocents_attribution"><?=$this->raw($attribution)?></div>
  <div class="twocents_message"><?=$this->raw($message)?></div>
<?endif?>
</div>
