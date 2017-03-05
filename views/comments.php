<div class="twocents_comments">
<?php foreach ($this->comments as $comment):?>
<?php   if ($comment->isCurrent):?>
    <?=$this->messages()?>
<?php   endif?>
    <?=$this->escape($comment->view)?>
<?php endforeach?>
<?php if ($this->hasAddComment):?>
    <?=$this->messages()?>
    <?=$this->commentForm()?>
<?php endif?>
</div>
