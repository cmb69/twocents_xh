<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var string $action
 * @var string $csrf_token
 * @var list<array{value:string,label:string}> $buttons
 */

?>

<h1>Twocents – <?=$this->text('menu_main')?></h1>
<form action="<?=$this->esc($action)?>" method="post">
  <input type="hidden" name="admin" value="plugin_main">
  <input type="hidden" name="xh_csrf_token" value="<?=$this->esc($csrf_token)?>">
<?foreach ($buttons as $button):?>
  <p>
    <button type="submit" name="action" value="<?=$this->esc($button['value'])?>"><?=$this->text($button['label'])?></button>
  </p>
<?endforeach?>
</form>
