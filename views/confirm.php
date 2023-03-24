<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var string $message_key
 * @var int $count
 * @var string $csrf_token
 * @var string $key
 */
?>
<!-- twocents confirmation -->
<h1>Twocents – <?=$this->text('menu_main')?></h1>
<p class="xh_info"><?=$this->plural($message_key, $count)?></p>
<form method="post">
  <input type="hidden" name="xh_csrf_token" value="<?=$csrf_token?>">
  <button name="twocents_do" ><?=$this->text($key)?></button>
</form>
