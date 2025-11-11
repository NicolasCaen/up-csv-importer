<?php
if (!defined('ABSPATH')) exit;

class UP_CSV_Admin_Page {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu() {
        add_menu_page('CSV Importer', 'CSV Importer', 'manage_options', 'up-csv-importer', [$this, 'render_list'], 'dashicons-media-spreadsheet', 56);
        add_submenu_page('up-csv-importer', 'Nouvelle configuration', 'Nouvelle configuration', 'manage_options', 'up-csv-importer-new', [$this, 'render_form']);
    }

    public function render_list() {
        // Handle settings save
        if (!empty($_POST['up_csv_dir_nonce']) && wp_verify_nonce($_POST['up_csv_dir_nonce'], 'up_csv_dir_save')) {
            $dir = isset($_POST['up_csv_dir']) ? wp_unslash($_POST['up_csv_dir']) : '';
            $dir = is_string($dir) ? trim($dir) : '';
            if ($dir) {
                $dir = wp_normalize_path($dir);
                update_option('up_csv_importer_config_dir', $dir);
            } else {
                delete_option('up_csv_importer_config_dir');
            }
            echo '<div class="updated"><p>Réglages enregistrés.</p></div>';
        }

        $dir = function_exists('up_csv_importer_get_config_dir') ? up_csv_importer_get_config_dir() : UP_CSV_IMPORTER_CONFIG_DIR;
        if (!file_exists($dir)) wp_mkdir_p($dir);
        $configs = glob($dir . '*.xml') ?: [];
        $items = array_map('basename', $configs);
        $current_dir = esc_attr($dir);
        $current_rel = esc_attr(get_option('up_csv_importer_config_dir', ''));
        include UP_CSV_IMPORTER_PATH . 'admin/views/settings-list.php';
    }

    public function render_form() {
        if (!empty($_POST['up_csv_nonce']) && wp_verify_nonce($_POST['up_csv_nonce'], 'up_csv_save')) {
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
            $save_dir = function_exists('up_csv_importer_get_config_dir') ? up_csv_importer_get_config_dir() : UP_CSV_IMPORTER_CONFIG_DIR;
            if (!file_exists($save_dir)) wp_mkdir_p($save_dir);
            if ($name) {
                $path = $save_dir . $name . '.xml';
                $saved = $xml->asXML($path);
                if ($saved) {
                    wp_safe_redirect(admin_url('admin.php?page=up-csv-importer&saved=1'));
                    exit;
                }
            }
        }
        include UP_CSV_IMPORTER_PATH . 'admin/views/settings-form.php';
    }
}

