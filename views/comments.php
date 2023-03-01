<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var list<array{isCurrent:bool,view:html}> $comments
 * @var bool $has_comment_form_above
 * @var bool $has_comment_form_below
 * @var html $comment_form
 * @var html $messages
 */

?>

<div class="twocents_comments">
<?if ($has_comment_form_above):?>
  <?=$this->raw($messages)?>
  <?=$this->raw($comment_form)?>
<?endif?>
<?foreach ($comments as $comment):?>
<?  if ($comment['isCurrent']):?>
  <?=$this->raw($messages)?>
<?  endif?>
  <?=$this->raw($comment['view'])?>
<?endforeach?>
<?if ($has_comment_form_below):?>
  <?=$this->raw($messages)?>
  <?=$this->raw($comment_form)?>
<?endif?>
</div>
