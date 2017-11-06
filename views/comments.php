<div class="twocents_comments">
<?php if ($hasCommentFormAbove):?>
    <?=$messages?>
<?php $commentForm()?>
<?php endif?>
<?php foreach ($comments as $comment):?>
<?php   if ($comment->isCurrent):?>
    <?=$messages?>
<?php   endif?>
<?php ($comment->view)()?>
<?php endforeach?>
<?php if ($hasCommentFormBelow):?>
    <?=$messages?>
<?php $commentForm()?>
<?php endif?>
</div>
