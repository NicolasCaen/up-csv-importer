<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>Configurations XML</h1>
    <?php if (isset($_GET['saved'])): ?><div class="updated"><p>Configuration enregistrée.</p></div><?php endif; ?>
    <h2>Réglages du dossier</h2>
    <form method="post" style="margin-bottom:16px;">
        <?php wp_nonce_field('up_csv_dir_save', 'up_csv_dir_nonce'); ?>
        <p>
            <label for="up_csv_dir">Dossier des XML (chemin RELATIF à <code>wp-content/</code>)</label><br>
            <input type="text" id="up_csv_dir" name="up_csv_dir" class="regular-text" value="<?php echo isset($current_rel) ? esc_attr($current_rel) : ''; ?>" placeholder="mes-configs/" />
            <br><small>Ex: tapez <code>mes-configs/</code> pour cibler <code><?php echo trailingslashit(WP_CONTENT_DIR); ?></code>mes-configs/</small>
        </p>
        <p class="submit"><button type="submit" class="button">Enregistrer le dossier</button></p>
    </form>
    <p><a href="<?php echo admin_url('admin.php?page=up-csv-importer-new'); ?>" class="button button-primary">Nouvelle configuration</a></p>
    <table class="widefat striped">
        <thead><tr><th>Fichier</th></tr></thead>
        <tbody>
        <?php if (empty($items)): ?>
            <tr><td>Aucune configuration</td></tr>
        <?php else: foreach ($items as $file): ?>
            <tr><td><?php echo esc_html($file); ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
