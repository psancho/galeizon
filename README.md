# Galeizon

My PHP framework, built on top of [Slim framework](https://www.slimframework.com/).

It integrates:

- [Monolog](https://github.com/Seldaek/monolog),
- [Symfony mailer](https://symfony.com/doc/current/mailer.html),
- [Doctrine migrations](https://www.doctrine-project.org/projects/migrations.html),
- built in oAuth2 solution

[Galeizon](https://www.valleedugaleizon.fr/le-galeizon/) is one of the 30 wild rivers of France, more precisely in [les Cévennes](https://causses-et-cevennes.fr/cevennes).

NOTE: config filename may be `config.jsonc` or `config.json`.

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

## Logging

logging is based on [`monolog/monolog`](https://github.com/Seldaek/monolog), witch is [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md) complient.

Logging methods (and levels) are, from lower to higher:

- `debug`: detailled info for dev or debug purpose
- `info`: interesting event
- `notice`: significant event
- `warning`: unexpected event other than error
- `error`: runtime error (non blocking)
- `critical`: blocking error
- `alert`: part of app is unavailable
- `emergency`: system unusuable

Galeizon comes with 2 preconfigured handlers:

- rotatings files (in `logs` subdir)
- stdout (used by docker)

Preconfigured handlers have to be set in config file

Further handlers (such as AWS cloudwatch) may be pushed to logger, using `LogAdapter::pushHandler()`

Example of `config.json` snippet:

```json
"monolog": {
    // minimum report level, default to debug
    // one of emergency|alert|critical|error|warning|notice|info|debug
    "level": "debug",
    // comma separated list of target systems, among files, stdout
    "systems": "stdout",
    // number 0 rotating files (0 for unlimited, defaulting to 1)
    "maxFiles": 1
},
```

## Fire and forget (AKA FnF)

FnF purpose is to close the client connection immediately, while the script continues running, in order to avoid timeout errors.

```php
// define my job callback:
class MyCtl {
    public static function myJobCb($myParam) { /* ... */ }
}

// add my job:
FireAndForget::getInstance()->addJob(
    Closure::fromCallable([MyCtl::class, 'myJobCb']),
    ['my param']
);

// here comes the end of webscript
// in a Controller, this is done by returning a Response
// in a View, this is done directly by writing status, headers, etc.
// status code `HTTP 202 accepted` should be fired.

// append following snippet at the end of the script file:
if (FireAndForget::getInstance()->hasJobs()) {
    FireAndForget::getInstance()->run();
}
```

## Migrations

Galeizon is based on doctrine/migrations lib.

Specific settings may be set in `config.json` to override default values (shown here).

```json
"database": {
    "dsn": "mysql:host=myHost;port=3306;dbname=myDb;charset=utf8",
    "migrations": {
        "namespace": "Psancho\\Galeizon\\Migrations",
        "directory": "./migrations",
        "credentials": {
            "login": "myLogin",
            "password": "myPwd"
        }
    }
},
```

### Usage

```sh
# list of available commands
vendor/bin/galeizon-migrations

# help for a command
vendor/bin/galeizon-migrations help status

# show status
vendor/bin/galeizon-migrations status
```

__NOTE__: autoExit MUST be OFF before cli is invoked programmatically.

```php
$exitCode = MigrationsAdapter::getInstance()->resetAutoExit()->run('myCommand');
if ($exitCode !== 0) {
    // error handling
}
```

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
