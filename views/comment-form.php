<form class="twocents_form" method="post" action="<?=$url?>">
    <?=$csrfTokenInput?>
    <input type="hidden" name="twocents_id" value="<?=$comment->getId()?>">
    <div>
        <label>
            <span><?=$this->text('label_user')?></span>
            <input type="text" name="twocents_user" value="<?=$comment->getUser()?>" size="20" required="required">
        </label>
    </div>
    <div>
        <label>
            <span><?=$this->text('label_email')?></span>
            <input type="email" name="twocents_email" value="<?=$comment->getEmail()?>" size="20" required="required">
        </label>
    </div>
    <div>
        <label>
            <span><?=$this->text('label_message')?></span>
            <textarea name="twocents_message" cols="50" rows="8" required="required"><?=$comment->getMessage()?></textarea>
        </label>
    </div>
    <?=$captcha?>
    <div class="twocents_form_buttons">
        <button type="submit" name="twocents_action" value="<?=$action?>_comment"><?=$this->text("label_{$action}")?></button>
<?php if ($comment->getId()):?>
        <a href="<?=$url?>"><?=$this->text('label_cancel')?></a>
<?php endif?>
        <button type="reset"><?=$this->text('label_reset')?></button>
    </div>
</form>
