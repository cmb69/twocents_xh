<<?=$this->url()?>#twocents_comment_<?=$this->comment->getId()?>>

<?=$this->text('label_user')?>: <?=$this->comment->getUser()?>

<?=$this->text('label_email')?>: <mailto:<?=$this->comment->getEmail()?>>
<?=$this->text('label_message')?>:

<?=$this->message()?>
