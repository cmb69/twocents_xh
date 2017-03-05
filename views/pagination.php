<div class="twocents_pagination">
    <span class="twocents_pag_count"><?=$this->plural('comment_count', $this->itemCount)?></span>
<?php foreach ($this->pages as $page):?>
<?php   if (empty($page)):?>
    <span class="twocents_pag_ellipsis">…</span>
<?php   elseif ($page->index === $this->currentPage):?>
    <span class="twocents_pag_current"><?=$this->escape($page->index)?></span>
<?php   else:?>
    <a class="twocents_button" href="<?=$this->escape($page->url)?>"><?=$this->escape($page->index)?></a>
<?php   endif?>
<?php endforeach?>
</div>
