<?php
/**
 * Plugin Name: UP CSV Importer
 * Description: Créer et gérer des configurations XML d'import CSV.
 * Version: 0.1.2.0
 * Author: GEHIN Nicolas
 */
if (!defined('ABSPATH')) exit;

if (!defined('UP_CSV_IMPORTER_VERSION')) define('UP_CSV_IMPORTER_VERSION', '0.1.2.0');
if (!defined('UP_CSV_IMPORTER_PATH')) define('UP_CSV_IMPORTER_PATH', plugin_dir_path(__FILE__));
if (!defined('UP_CSV_IMPORTER_URL')) define('UP_CSV_IMPORTER_URL', plugin_dir_url(__FILE__));
if (!defined('UP_CSV_IMPORTER_CONFIG_DIR')) define('UP_CSV_IMPORTER_CONFIG_DIR', UP_CSV_IMPORTER_PATH . 'config-settings/');

if (!function_exists('up_csv_importer_get_config_dir')) {
    function up_csv_importer_get_config_dir() {
        $rel = get_option('up_csv_importer_config_dir');
        $rel = is_string($rel) ? trim($rel) : '';
        if ($rel) {
            // Interpréter comme chemin RELATIF à WP_CONTENT_DIR
            $rel = ltrim($rel, '/');
            $abs = trailingslashit(WP_CONTENT_DIR) . $rel;
            $abs = wp_normalize_path($abs);
            if (substr($abs, -1) !== '/') $abs .= '/';
            return $abs;
        }
        return UP_CSV_IMPORTER_CONFIG_DIR;
    }
}

require_once UP_CSV_IMPORTER_PATH . 'includes/class-up-csv-config.php';
require_once UP_CSV_IMPORTER_PATH . 'includes/class-up-csv-importer.php';
require_once UP_CSV_IMPORTER_PATH . 'includes/class-up-csv-exporter.php';
require_once UP_CSV_IMPORTER_PATH . 'admin/class-up-csv-admin-page.php';

register_activation_hook(__FILE__, function() {
    $dir = up_csv_importer_get_config_dir();
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }
});

add_action('plugins_loaded', function() {
    new UP_CSV_Admin_Page();
});

