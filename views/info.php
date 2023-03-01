<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var string $version
 * @var list<array{key:string,arg:string,class:string,state:string}> $checks
 */

?>

<h1>Twocents <?=$this->esc($version)?></h1>
<div>
  <h2><?=$this->text('syscheck_title')?></h2>
<?foreach ($checks as $check):?>
  <p class="<?=$this->esc($check['class'])?>"><?=$this->text($check['key'], $check['arg'])?>: <?=$this->text($check['state'])?></li>
<?endforeach?>
</div>
