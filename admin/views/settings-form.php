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
        <p>Ajoutez autant de lignes que nécessaire, avec le type de donnée et le type de champ cible. Si vous choisissez "meta", renseignez la clé meta.</p>
        <table class="widefat striped" id="up-csv-mapping-table">
            <thead>
                <tr>
                    <th>Index CSV</th>
                    <th>Type de donnée</th>
                    <th>Type de champ</th>
                    <th>Clé meta (si meta)</th>
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

    function createRow(i){
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input name="mapping[${i}][csv]" type="text" placeholder="0" style="width:100%"></td>
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
                    <option value="meta">meta</option>
                </select>
            </td>
            <td><input name="mapping[${i}][meta_key]" type="text" placeholder="_custom_key" style="width:100%" disabled></td>
            <td><button type="button" class="button link-delete" data-index="${i}">Supprimer</button></td>
        `;
        tableBody.appendChild(tr);
    }

    function refreshMetaDisabled(){
        tableBody.querySelectorAll('select.up-field-type').forEach(sel => {
            const i = sel.getAttribute('data-index');
            const metaInput = tableBody.querySelector(`input[name="mapping[${i}][meta_key]"]`);
            if (sel.value === 'meta') {
                metaInput.disabled = false;
            } else {
                metaInput.disabled = true;
                metaInput.value = '';
            }
        });
    }

    addBtn.addEventListener('click', function(){
        createRow(idx++);
        refreshMetaDisabled();
    });

    tableBody.addEventListener('change', function(e){
        if (e.target && e.target.classList.contains('up-field-type')){
            refreshMetaDisabled();
        }
    });

    tableBody.addEventListener('click', function(e){
        if (e.target && e.target.classList.contains('link-delete')){
            e.target.closest('tr').remove();
        }
    });

    // Initial row
    createRow(idx++);
})();
</script>
