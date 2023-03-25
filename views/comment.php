<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var string $id
 * @var string $css_class
 * @var bool $is_current_comment
 * @var ?string $form
 * @var ?bool $is_admin
 * @var ?string $url
 * @var ?string $edit_url
 * @var ?string $visibility_action
 * @var ?string $delete_action
 * @var ?string $visibility
 * @var ?string $attribution
 * @var ?string $message
 * @var ?string $csrf_token
 */
?>
<!-- twocents comment -->
<div id="<?=$id?>" class="twocents_comment <?=$css_class?>">
<?if ($is_current_comment):?>
  <?=$form?>
<?else:?>
<?  if ($is_admin):?>
  <div class="twocents_admin_tools">
    <a href="<?=$edit_url?>"><?=$this->text('label_edit')?></a>
    <form method="post" action="<?=$url?>">
      <input type="hidden" name="xh_csrf_token" value="<?=$csrf_token?>">
      <button type="submit" formaction="<?=$visibility_action?>" name="twocents_do"><?=$this->text($visibility)?></button>
      <button data-action="delete" formaction="<?=$delete_action?>" name="twocents_do"><?=$this->text('label_delete')?></button>
    </form>
  </div>
<?  endif?>
  <div class="twocents_attribution"><?=$attribution?></div>
  <div class="twocents_message"><?=$message?></div>
<?endif?>
</div>
