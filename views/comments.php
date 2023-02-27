<?php

/**
 * @var bool $hasCommentFormAbove
 * @var bool $hasCommentFormBelow
 * @var string $messages
 * @var string $commentForm
 * @var list<stdClass> $comments
 */

?>

<div class="twocents_comments">
<?php if ($hasCommentFormAbove):?>
  <?=$messages?>
  <?=$commentForm?>
<?php endif?>
<?php foreach ($comments as $comment):?>
<?php   if ($comment->isCurrent):?>
  <?=$messages?>
<?php   endif?>
  <?=$comment->view?>
<?php endforeach?>
<?php if ($hasCommentFormBelow):?>
  <?=$messages?>
  <?=$commentForm?>
<?php endif?>
</div>
