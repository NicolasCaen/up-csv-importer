<?php
if (!defined('ABSPATH')) exit;

class UP_CSV_Importer {
    public function import_from_config($xml, $csv_path) {
        return ['imported' => 0, 'errors' => []];
    }
}
