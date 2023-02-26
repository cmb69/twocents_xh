<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var string $message
 * @var string $action
 * @var string $csrfTokenInput
 * @var list<string> $buttons
 */

?>

<h1>Twocents – <?=$this->text('menu_main')?></h1>
<?=$message?>
<form action="<?=$action?>" method="post">
  <input type="hidden" name="admin" value="plugin_main">
  <input name="xh_csrf_token" value="<?=$csrfTokenInput?>">
<?php foreach ($buttons as $button):?>
  <p>
    <button type="submit" name="action" value="<?=$button?>"><?=$this->text("label_$button")?></button>
  </p>
<?php endforeach?>
</form>
