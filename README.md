# Galeizon

My PHP framework.

## Quick start

Minimal `composer.json` file for the main project:

```json
{
    "name": "my-app",
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/psancho/galeizon.git"
        }
    ],
    "require": {
        "psancho/galeizon": "*"
    }
}
```

then...

```bash
composer install
```

## Mailer

The config file must be filed with the `mailer.dsn` property. See [Sending Emails with SF Mailer](https://symfony.com/doc/current/mailer.html).

## Developping

the best way to contribute to code is working on feature branch (ie. `my_feature_branch`) and ensure that composer will update right dependencies.

Root `composer.json` should look like that:

```json
{
    "minimum-stability": "dev",
    "prefer-stable": true,
    "config": {
        "preferred-install": {
            "psancho/*": "source"
        }
    },
}
```

And requiring `composer.json` should look like that:

```json
{
    "require": {
        "psancho/galeizon": "dev-my_feature_branch",
        // or:
        // "psancho/galeizon": "@dev",
    }
}
```

## License

This material comes under [license MIT](./LICENSE).
