<div class="wrap pg-add-page">
    <h1 class="wp-heading-inline">Post created</h1>
    <?php /** @var $pg \Pg\PostGenerator */
    $ret = wp_insert_post([
        'post_content' => gzuncompress(base64_decode($_POST['post_content'])),
        'post_title' => $_POST['post_title'],
        'post_status' => $_POST['post_status']
    ]);
    ?>
    <?php if ($ret): ?>
    <p>
    <a href="/wp-admin/post.php?post=<?php echo $ret ?>&action=edit" >Edit post</a> |
    <a href="/?p=<?php echo $ret ?>" target="_blank">View post</a>
    </p>
    <?php endif; ?>
</div>
