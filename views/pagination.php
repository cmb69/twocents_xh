<?php

use Twocents\View;

/**
 * @var View $this
 * @var int $itemCount
 * @var list<stdClass> $pages
 */

?>

<div class="twocents_pagination">
    <span class="twocents_pag_count"><?=$this->plural('comment_count', $itemCount)?></span>
<?php foreach ($pages as $page):?>
<?php   if ($page->isEllipsis):?>
    <span class="twocents_pag_ellipsis">…</span>
<?php   elseif ($page->isCurrent):?>
    <span class="twocents_pag_current"><?=$page->index?></span>
<?php   else:?>
    <a class="twocents_button" href="<?=$page->url?>"><?=$page->index?></a>
<?php   endif?>
<?php endforeach?>
</div>
