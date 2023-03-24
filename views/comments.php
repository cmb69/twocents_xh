<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var list<array{isCurrent:bool,view:string}> $comments
 * @var bool $has_comment_form_above
 * @var bool $has_comment_form_below
 * @var string $comment_form
 * @var string $messages
 */
?>
<!-- twocents comments -->
<div class="twocents_comments">
<?if ($has_comment_form_above):?>
  <?=$messages?>
  <?=$comment_form?>
<?endif?>
<?foreach ($comments as $comment):?>
<?  if ($comment['isCurrent']):?>
  <?=$messages?>
<?  endif?>
  <?=$comment['view']?>
<?endforeach?>
<?if ($has_comment_form_below):?>
  <?=$messages?>
  <?=$comment_form?>
<?endif?>
</div>
