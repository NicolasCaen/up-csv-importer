<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>Nouvelle configuration</h1>
    <form method="post">
        <?php wp_nonce_field('up_csv_save', 'up_csv_nonce'); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="config_name">Nom</label></th>
                <td><input name="config_name" id="config_name" type="text" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="post_type">Post Type</label></th>
                <td><input name="post_type" id="post_type" type="text" class="regular-text" value="post" required></td>
            </tr>
        </table>
        <h2>Mappage</h2>
        <p>Ajoutez autant de lignes que nécessaire. Selon le type de champ choisi, renseignez les paramètres associés.</p>
        <table class="widefat striped" id="up-csv-mapping-table">
            <thead>
                <tr>
                    <th>Index/Nom CSV</th>
                    <th>Type de donnée</th>
                    <th>Type de champ</th>
                    <th>Clé meta</th>
                    <th>Taxonomy</th>
                    <th>Mode image</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        <p><button type="button" class="button" id="up-csv-add-row">+ Ajouter une ligne</button></p>
        <p class="submit"><button type="submit" class="button button-primary">Enregistrer</button></p>
    </form>
</div>
<script>
(function(){
    const tableBody = document.querySelector('#up-csv-mapping-table tbody');
    const addBtn = document.getElementById('up-csv-add-row');
    let idx = 0;

    function rowHtml(i){
        return `
            <td><input name="mapping[${i}][csv]" type="text" placeholder="0 ou title" style="width:100%"></td>
            <td>
                <select name="mapping[${i}][data_type]" style="width:100%">
                    <option value="text">text</option>
                    <option value="number">number</option>
                    <option value="image">image</option>
                    <option value="date">date</option>
                </select>
            </td>
            <td>
                <select name="mapping[${i}][field_type]" class="up-field-type" data-index="${i}" style="width:100%">
                    <option value="post_title">post_title</option>
                    <option value="post_content">post_content</option>
                    <option value="post_excerpt">post_excerpt</option>
                    <option value="post_status">post_status</option>
                    <option value="post_date">post_date</option>
                    <option value="featured_image">featured_image</option>
                    <option value="taxonomy">taxonomy</option>
                    <option value="meta">meta</option>
                    <option value="unique_meta">unique_meta</option>
                </select>
            </td>
            <td><input name="mapping[${i}][meta_key]" type="text" placeholder="_custom_key" style="width:100%" disabled></td>
            <td><input name="mapping[${i}][taxonomy]" type="text" placeholder="category" style="width:100%" disabled></td>
            <td>
                <select name="mapping[${i}][image_mode]" style="width:100%" disabled>
                    <option value="url">url</option>
                    <option value="id">id</option>
                </select>
            </td>
            <td><button type="button" class="button link-delete" data-index="${i}">Supprimer</button></td>
        `;
    }

    function createRow(i){
        const tr = document.createElement('tr');
        tr.innerHTML = rowHtml(i);
        tableBody.appendChild(tr);
    }

    function refreshToggles(){
        tableBody.querySelectorAll('select.up-field-type').forEach(sel => {
            const i = sel.getAttribute('data-index');
            const metaInput = tableBody.querySelector(`input[name="mapping[${i}][meta_key]"]`);
            const taxInput = tableBody.querySelector(`input[name="mapping[${i}][taxonomy]"]`);
            const imgMode = tableBody.querySelector(`select[name="mapping[${i}][image_mode]"]`);
            const type = sel.value;
            // Defaults
            metaInput.disabled = true; taxInput.disabled = true; imgMode.disabled = true;
            if (type === 'meta' || type === 'unique_meta') metaInput.disabled = false;
            if (type === 'taxonomy') taxInput.disabled = false;
            if (type === 'featured_image') imgMode.disabled = false;
        });
    }

    addBtn.addEventListener('click', function(){ createRow(idx++); refreshToggles(); });
    tableBody.addEventListener('change', function(e){ if (e.target && e.target.classList.contains('up-field-type')) refreshToggles(); });
    tableBody.addEventListener('click', function(e){ if (e.target && e.target.classList.contains('link-delete')) e.target.closest('tr').remove(); });

    // Initial row
    createRow(idx++);
    refreshToggles();
})();
</script>
