<?php
use Pg\Settings;

?>
<style type="text/css">
    input.setting {
        min-width: 400px;
        max-width: 90%;
    }
</style>
<div class="wrap">
    <form method="post" action="options.php">
        <?php settings_fields(Settings::GROUP); ?>
        <?php do_settings_sections(Settings::GROUP); ?>
        <hr/>
        <h2>Amazon Settings</h2>
        <a href="https://affiliate-program.amazon.com/" target="_blank">Apply for a free affiliate account</a>
        <table class="form-table">
            <tr>
                <th scope="row">
                    Amazon Country Code
                </th>
                <td>
                    <select name="<?php echo Settings::AMAZON_CC ?>">
                        <option>Choose one...</option>
                        <?php foreach (\Ec\Amazon\AmazonUtils::getMarketPlaces() as $cc => $name) {
    ?>
                            <option value="<?php echo $cc ?>" <?php echo get_option(Settings::AMAZON_CC) == $cc ? 'selected="selected"' : '' ?>><?php echo $name ?> (<?php echo $cc ?>)</option>
                        <?php
} ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    App ID
                </th>
                <td>
                    <input name="<?php echo Settings::AMAZON_APP_ID ?>" class="setting"
                           value="<?php echo get_option(Settings::AMAZON_APP_ID) ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Secret ID
                </th>
                <td>
                    <input name="<?php echo Settings::AMAZON_SECRET_KEY ?>" class="setting"
                           value="<?php echo get_option(Settings::AMAZON_SECRET_KEY) ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Associate tag
                </th>
                <td>
                    <input name="<?php echo Settings::AMAZON_ASSOCIATE_TAG ?>" class="setting"
                           value="<?php echo get_option(Settings::AMAZON_ASSOCIATE_TAG) ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Cache API calls for this amount of seconds
                </th>
                <td>
                    <input name="<?php echo Settings::AMAZON_CACHE_LIFETIME ?>" class="setting"
                           value="<?php echo get_option(Settings::AMAZON_CACHE_LIFETIME) ?>"/><br/>
                    0 = no caching, 3600 = 1 hour (default), 86400 = 1 day
                </td>
            </tr>
        </table>

        <br/>
        <hr/>
        <h2>Youtube settings</h2>
        Read <a href="https://developers.google.com/youtube/v3/getting-started" target="_blank">google developers
            youtube API guide</a> to obtain a free developer key
        <table class="form-table">
            <tr>
                <th scope="row">
                    Google app name
                </th>
                <td>
                    <input name="<?php echo Settings::GOOGLE_APP_NAME ?>" style="width: 400px"
                           value="<?php echo get_option(Settings::GOOGLE_APP_NAME) ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Google dev key
                </th>
                <td>
                    <input name="<?php echo Settings::GOOGLE_DEV_KEY ?>" style="width: 400px"
                           value="<?php echo get_option(Settings::GOOGLE_DEV_KEY) ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Youtube search string
                </th>
                <td>
                    <input name="<?php echo Settings::YOUTUBE_SEARCH_STRING ?>" class="setting"
                           value="<?php echo get_option(Settings::YOUTUBE_SEARCH_STRING) ?: '%title% review' ?>"/><br/>
                    e.g. "%title% review"
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Cache API calls for this amount of seconds
                </th>
                <td>
                    <input name="<?php echo Settings::GOOGLE_CACHE_LIFETIME ?>" class="setting"
                           value="<?php echo get_option(Settings::GOOGLE_CACHE_LIFETIME) ?>"/><br/>
                    0 = no caching, 3600 = 1 hour (default), 86400 = 1 day
                </td>
            </tr>
        </table>

        <br/>
        <hr/>
        <h2>Ebay settings</h2>
        Register to <a href="https://developer.ebay.com/" target="_blank">Ebay developer</a> and create an app to use the Finding API
        and query price products. <br/>
        You can monetize ebay links registering to
        <a href="http://go.skimlinks.com/?id=122915X1583492&xs=1&url=http://skimlinks.com" target="_blank">Skimlink</a> and place the javascript in the template footer
        or (less revenue on average) via the <a href="https://epn.ebay.com/tools/smart-links" target="_blank">Ebay EPN smart link</a>
        <table class="form-table">
            <tr>
                <th scope="row">
                    Ebay App ID
                </th>
                <td>
                    <input name="<?php echo Settings::EBAY_APP_ID ?>" class="setting"
                           value="<?php echo get_option(Settings::EBAY_APP_ID) ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Ebay Cert ID
                </th>
                <td>
                    <input name="<?php echo Settings::EBAY_CERT_ID ?>" class="setting"
                           value="<?php echo get_option(Settings::EBAY_CERT_ID) ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Ebay Dev ID
                </th>
                <td>
                    <input name="<?php echo Settings::EBAY_DEV_ID ?>" class="setting"
                           value="<?php echo get_option(Settings::EBAY_DEV_ID) ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    EBAY Global ID
                </th>
                <td>
                    <select name="<?php echo Settings::EBAY_GLOBAL_ID ?>">
                        <option>Choose one...</option>
                        <?php foreach (\Ec\Ebay\EbayUtils::getMarketplaceIds() as $mid) {
        ?>
                            <option value="<?php echo $mid ?>" <?php echo get_option(Settings::EBAY_GLOBAL_ID) == $mid ? 'selected="selected"' : '' ?>><?php echo $mid ?></option>
                        <?php
    } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    Cache API calls for this amount of seconds
                </th>
                <td>
                    <input name="<?php echo Settings::EBAY_CACHE_LIFETIME ?>" class="setting"
                           value="<?php echo get_option(Settings::EBAY_CACHE_LIFETIME) ?>"/><br/>
                    0 = no caching, 3600 = 1 hour (default), 86400 = 1 day
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
