<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var string $json
 * @var string $filename
 */

?>

<script type="text/javascript">var TWOCENTS = <?=$this->raw($json)?>;</script>
<script type="text/javascript" src="<?=$this->esc($filename)?>"></script>
