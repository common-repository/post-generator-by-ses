<?php

// FORCE STEP 2 (Debug)
//$_POST['products_urls'] = "https://www.amazon.com/Samsung-Galaxy-Tablet-Black-SM-T560NZKUXAR/dp/B018QAYM7C?keywords=tablet&qid=1540685935&sr=8-6&ref=sr_1_6\nhttps://www.amazon.com/Android-Tablet-Inch-Card-Slots/dp/B07563YPTN?keywords=tablet&qid=1540685935&sr=8-8&ref=sr_1_8";
//$_POST['template'] = "product-list.html.twig";
//$_POST['post_title'] = "Best tablets November 2018";

// FORCE STEP 3 (Debug)
//$_POST['post_title'] ="temp".time();
//$_POST['post_content'] = base64_encode(gzcompress("content".time()));
//$_POST['post_status'] = "draft";

?>
<h1 class=""></h1>


<?php
if (!empty($_POST['post_title']) && !empty($_POST['post_content']) && !empty($_POST['post_status'])) {
    require_once __DIR__ . '/add-from-url/step3.php';
} elseif (!empty($_POST['products_urls']) && !empty($_POST['post_title']) && !empty($_POST['template'])) {
    require_once __DIR__ . '/add-from-url/step2.php';
} else {
    require_once __DIR__ . '/add-from-url/step1.php';
}
?>
<hr/>
<div>
This is a free plugin.
    If you wish to support the development, please make a <a href="https://www.paypal.me/ec83" target="_blank">small
        donation</a>.
    If you want to get in touch to request new features and fixes, please write to <a href="mailto:info@softwareengineeringsolutions.co.uk" target="_blank">this email address</a>.
</div>


