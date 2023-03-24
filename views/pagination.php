<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var int $item_count
 * @var list<array{index:?int,url:?string,is_current:?bool,is_ellipsis:bool}> $pages
 */
?>
<!-- twocents pagination -->
<div class="twocents_pagination">
  <span class="twocents_pag_count"><?=$this->plural('comment_count', $item_count)?></span>
<?foreach ($pages as $page):?>
<?  if ($page['is_ellipsis']):?>
  <span class="twocents_pag_ellipsis">…</span>
<?  elseif ($page['is_current']):?>
  <span class="twocents_pag_current"><?=$page['index']?></span>
<?  else:?>
  <a class="twocents_button" href="<?=$page['url']?>"><?=$page['index']?></a>
<?  endif?>
<?endforeach?>
</div>
