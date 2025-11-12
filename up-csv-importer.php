<?php
/**
 * Plugin Name: UP CSV Importer
 * Description: Créer et gérer des configurations XML d'import CSV.
 * Version: 0.1.2.2
 * Author: GEHIN Nicolas
 */
if (!defined('ABSPATH')) exit;

if (!defined('UP_CSV_IMPORTER_VERSION')) define('UP_CSV_IMPORTER_VERSION', '0.1.2.2');
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

// Sauvegarde via admin-post pour éviter le chargement direct de la sous-page pendant le POST
add_action('admin_post_up_csv_importer_save', function(){
    if (!current_user_can('manage_options')) wp_die('Permissions insuffisantes');
    if (empty($_POST['up_csv_nonce']) || !wp_verify_nonce($_POST['up_csv_nonce'], 'up_csv_save')) wp_die('Nonce invalide');

    $name = sanitize_title($_POST['config_name'] ?? '');
    $post_type = sanitize_key($_POST['post_type'] ?? 'post');
    $mappings = $_POST['mapping'] ?? [];
    $xml = new SimpleXMLElement('<config/>');
    $xml->addChild('name', $name);
    $xml->addChild('post_type', $post_type);
    $fields = $xml->addChild('fields');
    foreach ($mappings as $row) {
        $csv = isset($row['csv']) ? trim(wp_unslash($row['csv'])) : '';
        $data_type = isset($row['data_type']) ? sanitize_text_field($row['data_type']) : '';
        $field_type = isset($row['field_type']) ? sanitize_key($row['field_type']) : '';
        $meta_key = isset($row['meta_key']) ? sanitize_key($row['meta_key']) : '';
        $taxonomy = isset($row['taxonomy']) ? sanitize_key($row['taxonomy']) : '';
        $image_mode = isset($row['image_mode']) ? sanitize_key($row['image_mode']) : '';
        if ($csv !== '' && $field_type !== '') {
            $f = $fields->addChild('field');
            $f->addAttribute('csv', $csv);
            if ($data_type) $f->addAttribute('data_type', $data_type);
            $f->addAttribute('field_type', $field_type);
            if ($field_type === 'meta' && $meta_key) {
                $f->addAttribute('meta_key', $meta_key);
            }
            if ($field_type === 'unique_meta' && $meta_key) {
                $f->addAttribute('meta_key', $meta_key);
            }
            if ($field_type === 'taxonomy' && $taxonomy) {
                $f->addAttribute('taxonomy', $taxonomy);
            }
            if ($field_type === 'featured_image' && $image_mode) {
                $f->addAttribute('image_mode', in_array($image_mode, ['url','id'], true) ? $image_mode : 'url');
            }
        }
    }
    $save_dir = up_csv_importer_get_config_dir();
    if (!file_exists($save_dir)) wp_mkdir_p($save_dir);
    if ($name) {
        $path = $save_dir . $name . '.xml';
        $saved = $xml->asXML($path);
        if ($saved) {
            wp_safe_redirect(admin_url('admin.php?page=up-csv-importer&saved=1'));
            exit;
        }
    }
    wp_safe_redirect(admin_url('admin.php?page=up-csv-importer-new'));
    exit;
});

