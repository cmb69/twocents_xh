<?php

use Twocents\Infra\View;

/**
 * @var View $this
 * @var int $item_count
 * @var list<array{int|null,string|null}> $pages
 */
?>
<!-- twocents pagination -->
<div class="twocents_pagination">
  <span class="twocents_pag_count"><?=$this->plural('comment_count', $item_count)?></span>
<?foreach ($pages as [$page, $url]):?>
<?  if (!isset($page)):?>
  <span class="twocents_pag_ellipsis">…</span>
<?  elseif (!isset($url)):?>
  <span class="twocents_pag_current"><?=$page?></span>
<?  else:?>
  <a class="twocents_button" href="<?=$url?>"><?=$page?></a>
<?  endif?>
<?endforeach?>
</div>
