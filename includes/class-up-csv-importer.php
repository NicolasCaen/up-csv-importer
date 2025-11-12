<?php
if (!defined('ABSPATH')) exit;

class UP_CSV_Importer {
    public function import_from_config($xml, $csv_path) {
        $result = [
            'imported' => 0,
            'errors' => [],
        ];

        if (!$xml || !file_exists($csv_path)) {
            $result['errors'][] = 'Configuration XML ou fichier CSV introuvable.';
            return $result;
        }

        $post_type = isset($xml->post_type) ? sanitize_key((string)$xml->post_type) : 'post';
        $fields = [];
        $unique_meta_key = '';
        $unique_meta_csv = '';
        $nodes = [];
        // 1) XPath direct
        if (method_exists($xml, 'xpath')) {
            $xp = $xml->xpath('/config/fields/field');
            if (is_array($xp) && !empty($xp)) { $nodes = $xp; }
        }
        // 2) children()
        if (empty($nodes) && isset($xml->fields)) {
            foreach ($xml->fields->children() as $child) {
                if ($child->getName() === 'field') { $nodes[] = $child; }
            }
        }
        // 3) iterable direct
        if (empty($nodes) && isset($xml->fields->field)) {
            foreach ($xml->fields->field as $f) { $nodes[] = $f; }
        }
        foreach ($nodes as $f) {
            $entry = [
                'csv' => (string)$f['csv'],
                'data_type' => isset($f['data_type']) ? (string)$f['data_type'] : 'text',
                'field_type' => isset($f['field_type']) ? (string)$f['field_type'] : '',
                'meta_key' => isset($f['meta_key']) ? (string)$f['meta_key'] : '',
                'taxonomy' => isset($f['taxonomy']) ? (string)$f['taxonomy'] : '',
                'image_mode' => isset($f['image_mode']) ? (string)$f['image_mode'] : 'url',
            ];
            if ($entry['field_type'] === 'unique_meta' && !empty($entry['meta_key'])) {
                $unique_meta_key = $entry['meta_key'];
                $unique_meta_csv = $entry['csv'];
            }
            $fields[] = $entry;
        }
        if (empty($fields)) {
            $result['errors'][] = 'Aucun mappage défini dans la configuration.';
            return $result;
        }

        $handle = fopen($csv_path, 'r');
        if (!$handle) {
            $result['errors'][] = 'Impossible d\'ouvrir le CSV.';
            return $result;
        }

        // Détection du délimiteur (",", ";", "\t", "|")
        $delimiter = $this->detect_delimiter($csv_path);

        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false) {
            fclose($handle);
            $result['errors'][] = 'CSV vide.';
            return $result;
        }

        // Construire l\'index des colonnes si des noms sont utilisés
        $header_index = [];
        foreach ($header as $idx => $name) {
            $header_index[trim((string)$name)] = $idx;
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $postarr = [
                'post_type' => $post_type,
                'post_status' => 'publish',
            ];
            $meta_updates = [];
            $tax_updates = [];
            $featured_image_id = 0;
            $unique_value = null;

            foreach ($fields as $map) {
                $col = $map['csv'];
                $idx = null;
                if ($col === '0' || preg_match('/^\d+$/', $col)) {
                    $idx = intval($col);
                } else {
                    // par nom d\'en-tête
                    if (isset($header_index[$col])) {
                        $idx = $header_index[$col];
                    }
                }
                if ($idx === null || !array_key_exists($idx, $row)) {
                    // colonne inexistante: ignorer ce champ
                    continue;
                }
                $raw = $row[$idx];
                $value = $this->coerce_value($raw, $map['data_type']);

                switch ($map['field_type']) {
                    case 'post_title':
                        $postarr['post_title'] = (string)$value;
                        break;
                    case 'post_content':
                        $postarr['post_content'] = (string)$value;
                        break;
                    case 'post_excerpt':
                        $postarr['post_excerpt'] = (string)$value;
                        break;
                    case 'post_status':
                        $st = sanitize_key((string)$value);
                        if (in_array($st, ['publish','draft','pending','private'], true)) $postarr['post_status'] = $st;
                        break;
                    case 'post_date':
                        $postarr['post_date'] = (string)$value;
                        break;
                    case 'featured_image':
                        if ($map['image_mode'] === 'id' && is_numeric($value)) {
                            $featured_image_id = intval($value);
                        } elseif ($map['image_mode'] === 'url' && is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                            $aid = $this->sideload_image_to_media($value);
                            if ($aid) $featured_image_id = $aid;
                        }
                        break;
                    case 'taxonomy':
                        if (!empty($map['taxonomy'])) {
                            $slugs = array_filter(array_map('trim', explode(',', (string)$value)));
                            if (!empty($slugs)) {
                                $tax_updates[$map['taxonomy']] = $slugs;
                            }
                        }
                        break;
                    case 'meta':
                        if (!empty($map['meta_key'])) {
                            $meta_updates[$map['meta_key']] = $value;
                        }
                        break;
                    case 'unique_meta':
                        if (!empty($map['meta_key'])) {
                            $unique_value = $value;
                        }
                        break;
                }
            }

            // Upsert par meta unique si configuré et valeur présente
            $post_id = 0;
            if (!empty($unique_meta_key) && $unique_value !== null && $unique_value !== '') {
                $existing = $this->find_post_by_meta($post_type, $unique_meta_key, $unique_value);
                if ($existing) {
                    $post_id = $existing;
                    $postarr['ID'] = $post_id;
                    $updated = wp_update_post($postarr, true);
                    if (is_wp_error($updated)) {
                        $result['errors'][] = 'Ligne non mise à jour: ' . $updated->get_error_message();
                        continue;
                    }
                }
            }
            if (!$post_id) {
                // Créer le post
                $post_id = wp_insert_post($postarr, true);
                if (is_wp_error($post_id)) {
                    $result['errors'][] = 'Ligne non importée: ' . $post_id->get_error_message();
                    continue;
                }
            }

            // Meta
            foreach ($meta_updates as $k => $v) {
                update_post_meta($post_id, $k, $v);
            }

            // Mettre/assurer la meta unique
            if (!empty($unique_meta_key) && $unique_value !== null && $unique_value !== '') {
                update_post_meta($post_id, $unique_meta_key, $unique_value);
            }

            // Taxonomies
            foreach ($tax_updates as $tax => $slugs) {
                $term_ids = [];
                foreach ($slugs as $slug_or_name) {
                    $term = get_term_by('slug', $slug_or_name, $tax);
                    if (!$term) {
                        // tenter par nom
                        $term = get_term_by('name', $slug_or_name, $tax);
                    }
                    if (!$term) {
                        $ins = wp_insert_term($slug_or_name, $tax);
                        if (!is_wp_error($ins) && isset($ins['term_id'])) {
                            $term_ids[] = intval($ins['term_id']);
                        }
                    } else {
                        $term_ids[] = intval($term->term_id);
                    }
                }
                if (!empty($term_ids)) {
                    wp_set_post_terms($post_id, $term_ids, $tax, false);
                }
            }

            // Image à la une
            if ($featured_image_id) {
                set_post_thumbnail($post_id, $featured_image_id);
            }

            $result['imported']++;
        }

        fclose($handle);
        return $result;
    }

    private function coerce_value($value, $type) {
        $value = is_string($value) ? trim($value) : $value;
        switch ($type) {
            case 'number':
                if ($value === '' || $value === null) return '';
                // Remplacer virgule par point si besoin
                $v = str_replace([',', ' '], ['.', ''], (string)$value);
                return is_numeric($v) ? 0 + $v : $value;
            case 'date':
                $ts = strtotime((string)$value);
                return $ts ? date('Y-m-d', $ts) : $value;
            case 'image':
                // Laisser tel quel pour le moment (URL/ID à gérer ultérieurement)
                return $value;
            case 'text':
            default:
                return $value;
        }
    }

    private function find_post_by_meta($post_type, $meta_key, $meta_value) {
        $q = new WP_Query([
            'post_type' => $post_type,
            'posts_per_page' => 1,
            'post_status' => 'any',
            'meta_query' => [[
                'key' => $meta_key,
                'value' => $meta_value,
                'compare' => '=',
            ]],
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        if (!empty($q->posts)) return intval($q->posts[0]);
        return 0;
    }

    private function sideload_image_to_media($url) {
        if (!function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        // media_sideload_image() retourne HTML; utiliser WP built-in pour obtenir l'ID
        $tmp = download_url($url);
        if (is_wp_error($tmp)) return 0;
        $file_array = [
            'name' => basename(parse_url($url, PHP_URL_PATH)),
            'tmp_name' => $tmp,
        ];
        $id = media_handle_sideload($file_array, 0);
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return 0;
        }
        return intval($id);
    }

    private function detect_delimiter($path) {
        $candidates = [',', ';', "\t", '|'];
        $first = '';
        $fh = @fopen($path, 'r');
        if ($fh) {
            $first = fgets($fh, 4096);
            fclose($fh);
        }
        if (!is_string($first) || $first === '') return ',';
        $best = ','; $bestCount = -1;
        foreach ($candidates as $d) {
            $cnt = substr_count($first, $d);
            if ($cnt > $bestCount) { $best = $d; $bestCount = $cnt; }
        }
        return $best;
    }
}
