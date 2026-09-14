# Local Page Generator

Plugin WordPress permettant de générer des pages Elementor à partir d'un fichier CSV validé.

## Qualité et tests

Le projet utilise PHPUnit dans une installation WordPress de test séparée. Cette approche vérifie le comportement réel des fonctions WordPress et de la base de données sans toucher au site Local utilisé pour le développement.

La suite couvre actuellement :

- la détection du séparateur, des en-têtes et des erreurs CSV ;
- la limite de 500 lignes CSV ;
- le calcul et le verrouillage des étapes de l'onboarding ;
- la réinitialisation sécurisée des données temporaires ;
- la détection des variables LPG dans les données Elementor ;
- la création atomique des pages et la copie des métadonnées Elementor.

### Prérequis locaux

- PHP 7.4 ou plus récent ;
- Composer 2 ;
- MySQL ou MariaDB.

Le script utilise Subversion (`svn`) lorsqu'il est disponible. Sinon, il récupère automatiquement la même suite de tests depuis le miroir GitHub officiel de WordPress.

La base de tests doit être dédiée : son contenu est réinitialisé par WordPress pendant les tests. Ne jamais utiliser la base du site de développement ou de production.

### Installation

```bash
composer install
composer test:install
```

La commande `test:install` suppose une base `wordpress_test` accessible avec `root` / `root` sur `127.0.0.1`. Pour utiliser d'autres identifiants :

```bash
bash bin/install-wp-tests.sh nom_base utilisateur mot_de_passe hote latest
```

### Commandes quotidiennes

```bash
composer lint
composer test
composer check
```

- `lint` vérifie la syntaxe de chaque fichier PHP hors dépendances.
- `test` exécute PHPUnit.
- `check` lance successivement la syntaxe puis les tests.

## Intégration continue

Le workflow `.github/workflows/tests.yml` est exécuté sur les pull requests, les pushs vers `main` et manuellement depuis GitHub.

Il réalise deux contrôles indépendants :

1. validation de `composer.json` et syntaxe PHP ;
2. tests dans un vrai WordPress avec MySQL, sur PHP 7.4 à 8.4.

Le workflow possède uniquement une permission de lecture sur le dépôt et annule automatiquement une ancienne exécution lorsqu'un nouveau commit arrive sur la même branche.

Dependabot contrôle chaque semaine les dépendances Composer et les versions majeures des actions GitHub. Ses mises à jour sont regroupées pour limiter le nombre de pull requests.

## Ajouter un test

Créer un fichier terminé par `Test.php` dans `tests/Unit/`. Chaque classe teste un seul service et chaque méthode commence par `test_` afin de rester cohérente avec les conventions PHPUnit de WordPress.
