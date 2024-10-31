<?php
use Pg\Constants;
use Pg\Settings;

;
?>
<h1 class="wp-heading-inline">Create post with products from Amazon</h1>
<?php
$amazonSet = get_option(Settings::AMAZON_APP_ID)
    && get_option(Settings::AMAZON_CC)
    && get_option(Settings::AMAZON_SECRET_KEY)
    && get_option(Settings::AMAZON_ASSOCIATE_TAG);
if (!$amazonSet): ?>
    <div class="notice notice-error is-dismissible">
        <p>To pull products data from amazon you need access to amazon API. See the <a href="/wp-admin/admin.php?page=<?php echo Constants::OPTIONS_PAGE ?>">options page</a></p>
    </div>
<?php endif; ?>

<style type="text/css">
    .pg-add-page {
        font-size: 1.2em;
        line-height: 2em;
    }
    textarea {

    }
</style>
<div class="wrap pg-add-page">
    <form method="post" action="#">
        Paste URLs (or ASINs) of amazon products, one per line (*)</br>
        <textarea <?php echo $amazonSet ? '' : 'disabled="disabled"'; ?> name="products_urls" rows="5" style="width:100%"><?php echo empty($_SESSION['pg_products_urls']) ? '' : $_POST['products_urls'] ?></textarea><br/>
        Post Title: <input <?php echo $amazonSet ? '' : 'disabled="disabled"'; ?> name="post_title" style="width: 70%" value="POST TITLE"/><br/>
        Template: <select <?php echo $amazonSet ? '' : 'disabled="disabled"'; ?> name="template">
            <?php
            foreach ($pg->getTemplates() as $template) {
                ?>
                <option value="<?php echo $template ?>"><?php echo $template ?></option>
            <?php
            } ?>
        </select><br/>
        <input type="submit" value="Preview"  <?php echo $amazonSet ? '' : 'disabled="disabled"'; ?> />
    </form>
</div>
<div>
<hr/>
(*) You can also paste the product ASINs that you can also grab from an amazon page with
<a href="https://chrome.google.com/webstore/detail/asin-grabber-light/gdmicanijbiglolpggafeahdicefofbg?hl=en" target="_blank">this free browser extension</a>
</div>

