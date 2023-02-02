<?php

use Twocents\View;

/**
 * @var View $this
 * @var string $logo
 * @var string $version
 * @var list<array{state:string,label:string,stateLabel:string}> $checks
 */

?>

<h1>Twocents <?=$version?></h1>
<div>
  <h2><?=$this->text('syscheck_title')?></h2>
<?php foreach ($checks as $check):?>
  <p class="xh_<?=$check['state']?>"><?=$this->text('syscheck_message', $check['label'], $check['stateLabel'])?></li>
<?php endforeach?>
</div>
