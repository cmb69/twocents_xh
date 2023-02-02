<?php

use Twocents\Comment;
use Twocents\Url;
use Twocents\View;

/**
 * @var View $this
 * @var string $id
 * @var string $className
 * @var bool $isCurrentComment
 * @var string $form
 * @var bool $isAdmin
 * @var Url $url
 * @var Url $editUrl
 * @var string $csrfTokenInput
 * @var string $visibility
 * @var string $attribution
 * @var string $message
 * @var Comment $comment
 */

?>

<div id="<?=$id?>" class="twocents_comment <?=$className?>">
<?php if ($isCurrentComment):?>
  <?=$form?>
<?php else:?>
<?php   if ($isAdmin):?>
  <div class="twocents_admin_tools">
    <a href="<?=$editUrl?>"><?=$this->text('label_edit')?></a>
    <form method="post" action="<?=$url?>">
      <?=$csrfTokenInput?>
      <input type="hidden" name="twocents_id" value="<?=$comment->getId()?>">
      <button type="submit" name="twocents_action" value="toggle_visibility"><?=$this->text((string)$visibility)?></button>
      <button type="submit" name="twocents_action" value="remove_comment"><?=$this->text('label_delete')?></button>
    </form>
  </div>
<?php   endif?>
  <div class="twocents_attribution"><?=$attribution?></div>
  <div class="twocents_message"><?=$message?></div>
<?php endif?>
</div>
