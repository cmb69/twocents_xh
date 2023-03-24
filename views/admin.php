<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var string $flash_message
 * @var list<array{value:string,label:string}> $buttons
 */
?>
<!-- twocents administration -->
<h1>Twocents – <?=$this->text('menu_main')?></h1>
<?if ($flash_message):?>
<div><?=$flash_message?></div>
<?endif?>
<form method="get">
  <input type="hidden" name="selected" value="twocents">
  <input type="hidden" name="admin" value="plugin_main">
<?foreach ($buttons as $button):?>
  <p><button name="twocents_action" value="<?=$button['value']?>"><?=$this->text($button['label'])?></button></p>
<?endforeach?>
</form>
