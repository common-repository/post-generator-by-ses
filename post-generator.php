<?php
/*
  Plugin Name: Post generator by SES
  Version: 0.1
  Author: SES
  Description: Create posts with multiple amazon products given the amazon URLs.
 */

use Pg\Constants;
use Pg\Settings;

defined('ABSPATH') or die('only wp can call me');


if (is_admin()) {
    add_action('admin_init', function () {
        Settings::registerSettings();
    });

    $pg = require_once __DIR__ . '/includes/pg.php'; /* @var $pg Pg\PostGenerator */

    // menu
    add_action('admin_menu', function () use ($pg) {
        add_menu_page('Post generator', 'Post generator', 'administrator', Constants::PAGE_ADD_URL, function () use ($pg) {
            require_once __DIR__ . '/admin/add-from-url.php';
        }, plugin_dir_url(__DIR__) . '/post-generator/admin/static/icon.png');

        add_submenu_page(Constants::PAGE_ADD_URL, 'Options', 'Options', 'administrator', Constants::OPTIONS_PAGE, function () use ($pg) {
            require_once __DIR__ . '/admin/options.php';
        });
    });
}
