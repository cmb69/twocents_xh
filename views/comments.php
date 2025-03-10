<?php

use Plib\View;

/**
 * @var View $this
 * @var string $module
 * @var list<array{id:string,css_class:string,edit_url:string,visibility_action:string,delete_action:string,visibility:string,attribution:string,message:string}> $comments
 * @var bool $has_comment_form_above
 * @var bool $has_comment_form_below
 * @var string $new_url
 * @var string $action_url
 * @var bool $is_admin
 * @var string|null $csrf_token
 */
?>
<!-- twocents comments -->
<script type="module" src="<?=$this->esc($module)?>"></script>
<div class="twocents_comments">
<?if ($has_comment_form_above):?>
  <p class="twocents_new_comment">
    <a href="<?=$this->esc($new_url)?>"><?=$this->text('label_new')?></a>
  </p>
<?endif?>
<?foreach ($comments as $comment):?>
  <div id="<?=$this->esc($comment['id'])?>" class="twocents_comment <?=$this->esc($comment['css_class'])?>">
<?  if ($is_admin):?>
<?    assert($csrf_token !== null)?>
    <div class="twocents_admin_tools">
      <a href="<?=$this->esc($comment['edit_url'])?>"><?=$this->text('label_edit')?></a>
      <form method="post" action="<?=$this->esc($action_url)?>">
        <input type="hidden" name="xh_csrf_token" value="<?=$this->esc($csrf_token)?>">
        <button type="submit" formaction="<?=$this->esc($comment['visibility_action'])?>" name="twocents_do"><?=$this->text($comment['visibility'])?></button>
        <button data-confirm='<?=$this->json($this->text('message_delete'))?>' formaction="<?=$this->esc($comment['delete_action'])?>" name="twocents_do"><?=$this->text('label_delete')?></button>
      </form>
    </div>
<?  endif?>
    <div class="twocents_attribution"><?=$this->esc($comment['attribution'])?></div>
    <div class="twocents_message"><?=$this->raw($comment['message'])?></div>
  </div>
<?endforeach?>
<?if ($has_comment_form_below):?>
  <p class="twocents_new_comment">
    <a href="<?=$this->esc($new_url)?>"><?=$this->text('label_new')?></a>
  </p>
<?endif?>
</div>
