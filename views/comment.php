<div id="<?=$this->id()?>" class="twocents_comment <?=$this->className()?>">
<?php if ($this->isCurrentComment):?>
        <?=$this->form()?>
<?php else:?>
<?php   if ($this->isAdmin):?>
    <div class="twocents_admin_tools">
        <a href="<?=$this->editUrl()?>"><?=$this->text('label_edit')?></a>
        <form method="post" action="<?=$this->url()?>">
            <?=$this->csrfTokenInput()?>
            <input type="hidden" name="twocents_id" value="<?=$this->escape($this->comment->getId())?>">
            <button type="submit" name="twocents_action" value="toggle_visibility"><?=$this->text($this->visibility)?></button>
            <button type="submit" name="twocents_action" value="remove_comment"><?=$this->text('label_delete')?></button>
        </form>
    </div>
<?php   endif?>
    <div class="twocents_attribution"><?=$this->attribution()?></div>
    <div class="twocents_message"><?=$this->message()?></div>
<?php endif?>
</div>
