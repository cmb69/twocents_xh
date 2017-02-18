<!-- twocents pagination -->
<div class="twocents_pagination">
    <span class="twocents_pag_count"><?=$this->plural('comment_count', $this->itemCount)?></span>
<?php xdebug_break()?>
<?php foreach ($this->pages as $page):?>
<?php   if (!isset($page)):?>
    <span class="twocents_pag_ellipsis">…</span>
<?php   elseif ($page == $this->currentPage):?>
    <span class="twocents_pag_current"><?=$this->escape($page)?></span>
<?php   else:?>
    <a class="twocents_button" href="<?=$this->url($page)?>"><?=$this->escape($page)?></a>
<?php   endif?>
<?php endforeach?>
</div>
