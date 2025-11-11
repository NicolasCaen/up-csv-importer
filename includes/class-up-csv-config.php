<?php
if (!defined('ABSPATH')) exit;

class UP_CSV_Config {
    public static function load($path) {
        if (file_exists($path)) return simplexml_load_file($path);
        return false;
    }
}
