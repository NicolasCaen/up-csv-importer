# UP CSV Importer

## Description
Créer, configurer et enregistrer des fichiers XML décrivant comment importer un CSV dans WordPress.

## Installation
1. Déposer le dossier dans `wp-content/plugins/`.
2. Activer le plugin dans l’administration WordPress.

## Changelog
- 2025-11-11 · v0.1.2.0 · Import « réel »: upsert via `unique_meta`, support `featured_image` (URL/ID), `taxonomy` (slugs/noms, création si manquante), champs supplémentaires (`post_excerpt`, `post_status`, `post_date`), coercition `number` et `date`, résumé des résultats dans le Runner.
- 2025-11-11 · v0.1.1.0 · Réglage du dossier XML relatif à `wp-content/` + formulaire de mappage dynamique (ajout/suppression de lignes, `data_type`, `field_type`, `meta_key`).
- 2025-11-11 · v0.1.0 · Création du plugin et structure initiale (admin, includes, config-settings).
