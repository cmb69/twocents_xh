<form action="<?=$this->action?>" method="post">
    <input type="hidden" name="admin" value="plugin_main">
    <?=$this->csrfTokenInput?>
<?php foreach ($this->buttons as $button):?>
    <p>
        <button type="submit" name="action" value="<?=$this->escape($button)?>"><?=$this->text("label_$button")?></button>
    </p>
<?php endforeach?>
</form>
