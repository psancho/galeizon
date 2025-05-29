# Upgrading

## 8.15

### Intégration de symfony/mailer

* La lib est maintenant requise avec lisy. Plus besoin de l'ajouter dans le `composer.json` du projet racine

### Intégration de doctrine/migration

* le script `vendor/bin/lizy-migrations` remplace le script `vendor/bin/doctrine-migrations`, et s'utilise de la même façon.
* les fichiers `migrations.php` et `migrations-db.php` ne sont plus utilisés et peuvent être supprimés si seul le script `vendor/bin/lizy-migrations` est utilisé.
* nouveau repository à déclarer dans le `composer.json,` du projet racine :

  ```json
  "repositories": [
      {
          "type": "package",
          "package": {
              "name": "developers-toolbox/ini-file-parser",
              "version": "0.1.5",
              "source": {
                  "url": "https://github.com/DevelopersToolbox/ini-file-parser.git",
                  "type": "git",
                  "reference": "tags/v0.1.5"
              }
          }
      }
  ]
  ```

## 8.14

version initiale du fichier UPGRADING
