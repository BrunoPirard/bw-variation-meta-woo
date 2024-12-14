# Custom Meta Fields for WooCommerce Variations

Ce plugin WordPress permet d'ajouter des champs personnalisés aux variations de produits WooCommerce et de les afficher sur la page produit.

## Description

Ce plugin ajoute la possibilité de créer et gérer des champs personnalisés pour les variations de produits WooCommerce. Les valeurs de ces champs sont automatiquement affichées sur la page produit lorsqu'une variation est sélectionnée.

## Fonctionnalités

- Ajout de champs personnalisés aux variations de produits
- Interface d'administration pour gérer les champs
- Affichage automatique des valeurs sur la page produit
- Compatible avec HPOS (High-Performance Order Storage) de WooCommerce
- Support multilingue

## Prérequis

- WordPress 5.0 ou supérieur
- WooCommerce 7.0 ou supérieur
- PHP 7.4 ou supérieur

## Installation

1. Téléchargez le plugin
2. Décompressez-le dans le dossier `/wp-content/plugins/`
3. Activez le plugin via le menu 'Extensions' dans WordPress
4. Configurez les champs personnalisés dans WooCommerce > Réglages > Champs personnalisés variations

## Configuration

1. Allez dans WooCommerce > Réglages > Champs personnalisés variations
2. Ajoutez vos champs personnalisés
3. Configurez les labels pour chaque champ
4. Sélectionnez les champs à masquer si nécessaire
5. Sauvegardez les modifications

## Utilisation

### Dans l'administration

1. Éditez un produit variable
2. Dans chaque variation, vous trouverez les champs personnalisés configurés
3. Remplissez les valeurs souhaitées pour chaque variation

### Sur le front-end

Les valeurs des champs personnalisés s'affichent automatiquement lorsqu'une variation est sélectionnée sur la page produit.

## Support

Pour toute question ou problème, veuillez créer une issue sur le dépôt GitHub du plugin.

## Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :

1. Forker le projet
2. Créer une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Commiter vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Pusher sur la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## Licence

Ce plugin est sous licence GPL v2 ou ultérieure.

## Changelog

### 1.0.0
- Version initiale
- Ajout des champs personnalisés aux variations
- Interface d'administration
- Affichage sur le front-end
- Compatibilité HPOS

## Crédits

Développé par [Votre Nom/Société]

## Notes de développement

Le plugin utilise :
- Les hooks WooCommerce pour l'intégration avec les variations
- L'API Settings de WordPress pour la page d'administration
- Le système de template WooCommerce pour l'affichage front-end
