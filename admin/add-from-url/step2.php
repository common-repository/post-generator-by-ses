<?php
/** @var $pg \Pg\PostGenerator */
$content = $pg->renderTemplateWithProductObjects(explode("\n", $_POST['products_urls']), $_POST['template']);
?>
<style type="text/css">
.pg-preview-page {
    background-color: #FFF;
    padding: 5px;
    margin: 10px 0px;
}
</style>
<div class="wrap pg-add-page">
    <h1 class="wp-heading-inline">Post Preview</h1>

    <form method="post" action="#">
        <button name="post_status" value="draft">Create Draft and edit</button>
        <button name="post_status" value="publish">Publish immediately</button>
        <input type="hidden" name="post_title"  value="<?php echo $_POST['post_title'] ?>"/>
        <input type="hidden" name="post_content" value="<?php echo base64_encode(gzcompress($content)) ?>"/>
    </form>
    <div class="pg-preview-page">
        <h2><?php echo $_POST['post_title'] ?></h2>
    </div>
    <div class="pg-preview-page">
        <?php echo $content ?>
    </div>
</div>
Operation log:<br/>
<textarea style="width: 80%;" rows="10" disabled="disabled">
<?php
foreach ($pg->getErrors() as $url => $errors) {
    echo $url . ":\n";
    foreach ($errors as $error) {
        echo ' - ' . $error . "\n";
    }
}
 ?>
</textarea>