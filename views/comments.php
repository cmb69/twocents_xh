<div class="twocents_comments">
<?php if ($this->hasCommentFormAbove):?>
    <?=$this->messages()?>
    <?=$this->commentForm()?>
<?php endif?>
<?php foreach ($this->comments as $comment):?>
<?php   if ($comment->isCurrent):?>
    <?=$this->messages()?>
<?php   endif?>
    <?=$this->escape($comment->view)?>
<?php endforeach?>
<?php if ($this->hasCommentFormBelow):?>
    <?=$this->messages()?>
    <?=$this->commentForm()?>
<?php endif?>
</div>
