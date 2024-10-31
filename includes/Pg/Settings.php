<?php
namespace Pg;
class Settings
{
    const GROUP = 'pg';
    const AMAZON_APP_ID = 'amazon_app_id';
    const AMAZON_CC = 'amazon_cc';
    const AMAZON_SECRET_KEY = 'amazon_secret_key';
    const AMAZON_ASSOCIATE_TAG = 'amazon_tag';
    const AMAZON_CACHE_LIFETIME = 'amazon_cache_lifetime';
    const GOOGLE_APP_NAME = 'google_app_name';
    const GOOGLE_DEV_KEY = 'google_dev_key';
    const GOOGLE_CACHE_LIFETIME = 'google_cache_lifetime';
    const YOUTUBE_SEARCH_STRING = 'youtube_search_string';
    const EBAY_APP_ID = 'ebay_app_id';
    const EBAY_CERT_ID = 'ebay_cert_id';
    const EBAY_DEV_ID = 'ebay_dev_id';
    const EBAY_GLOBAL_ID = 'ebay_global_id';
    const EBAY_CACHE_LIFETIME = 'ebay_cache_lifetime';
    public static function registerSettings()
    {
        $settings = [
            self::AMAZON_APP_ID,
            self::AMAZON_CC,
            self::AMAZON_SECRET_KEY,
            self::AMAZON_ASSOCIATE_TAG,
            self::AMAZON_CACHE_LIFETIME,
            self::GOOGLE_APP_NAME,
            self::GOOGLE_DEV_KEY,
            self::GOOGLE_CACHE_LIFETIME,
            self::YOUTUBE_SEARCH_STRING,
            self::EBAY_APP_ID,
            self::EBAY_CERT_ID,
            self::EBAY_DEV_ID,
            self::EBAY_GLOBAL_ID,
            self::EBAY_CACHE_LIFETIME,
        ];
        foreach ($settings as $s) {
            register_setting(self::GROUP, $s);
        }
    }
}
