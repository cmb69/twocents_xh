<form class="twocents_form" method="post" action="<?=$this->url()?>">
    <?=$this->csrfTokenInput()?>
    <input type="hidden" name="twocents_id" value="<?=$this->escape($this->comment->getId())?>">
    <div>
        <label>
            <span><?=$this->text('label_user')?></span>
            <input type="text" name="twocents_user" value="<?=$this->escape($this->comment->getUser())?>" size="20" required="required">
        </label>
    </div>
    <div>
        <label>
            <span><?=$this->text('label_email')?></span>
            <input type="email" name="twocents_email" value="<?=$this->escape($this->comment->getEmail())?>" size="20" required="required">
        </label>
    </div>
    <div>
        <label>
            <span><?=$this->text('label_message')?></span>
            <textarea name="twocents_message" cols="50" rows="8" required="required"><?=$this->escape($this->comment->getMessage())?></textarea>
        </label>
    </div>
    <?=$this->captcha()?>
    <div class="twocents_form_buttons">
        <button type="submit" name="twocents_action" value="<?=$this->action()?>_comment"><?=$this->text("label_{$this->action}")?></button>
<?php if ($this->comment->getId()):?>
        <a href="<?=$this->url()?>"><?=$this->text('label_cancel')?></a>
<?php endif?>
        <button type="reset"><?=$this->text('label_reset')?></button>
    </div>
</form>
