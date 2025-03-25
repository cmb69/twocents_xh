<?php

use Plib\View;

/**
 * @var View $this
 * @var string $module
 * @var string $id
 * @var string $attribution
 * @var string $message
 * @var string $url
 * @var bool $moderated
 */
?>
<!-- twocents comment -->
<script type="module" src="<?=$this->esc($module)?>"></script>
<p class="xh_success"><?=$this->text("message_posted")?></p>
<div class="twocents_comments">
  <div id="<?=$this->esc($id)?>" class="twocents_comment">
    <div class="twocents_attribution"><?=$this->esc($attribution)?></div>
    <div class="twocents_message"><?=$this->raw($message)?></div>
  </div>
<?if ($moderated):?>
  <p class="xh_info"><?=$this->text("message_moderated")?></p>
<?endif?>
  <div class="twocents_form_buttons">
    <a href="<?=$this->esc($url)?>"><?=$this->text("label_overview")?></a>
  </div>
</div>
