<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Adapter;

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command;
use Psancho\Galeizon\Model\Conf;
use Psancho\Galeizon\Model\ConfException;
use Psancho\Galeizon\Model\File;
use Psancho\Galeizon\Pattern\Singleton;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * @phpstan-import-type Params from DriverManager as DoctrineDbParam
 * @phpstan-type MigrationsConfig array{
 *      table_storage: array{
 *          table_name: string,
 *          version_column_name: string,
 *          version_column_length: int,
 *          executed_at_column_name: string,
 *          execution_time_column_name: string
 *      },
 *      migrations_paths: array<string, string>,
 *      all_or_nothing: bool,
 *      transactional: bool,
 *      check_database_platform: bool,
 *      organize_migrations: string,
 *      connection: ?mixed,
 *      em: ?mixed
 * }
 */
class MigrationsAdapter extends Singleton
{

    public const string MIGRATION_LATEST = 'migrations:latest';

    /** @phpstan-var MigrationsConfig */
    protected static array $config = [
        'table_storage' => [
            'table_name' => 'doctrine_migration_versions',
            'version_column_name' => 'version',
            'version_column_length' => 191,
            'executed_at_column_name' => 'executed_at',
            'execution_time_column_name' => 'execution_time',
        ],

        'migrations_paths' => [
            'Psancho\Galeizon\Migrations' => './migrations',
        ],

        'all_or_nothing' => true,
        'transactional' => true,
        'check_database_platform' => true,
        'organize_migrations' => 'none',
        'connection' => null,
        'em' => null,
    ];

    protected DependencyFactory $dependencyFactory;// @phpstan-ignore property.uninitialized
    protected Application $cli;// @phpstan-ignore property.uninitialized

    #[\Override]
    protected function build(): void
    {
        self::_canonizeMigrationPath();
        $config = new ConfigurationArray(self::$config);
        $connection = DriverManager::getConnection($this->_getDbParams());
        $this->dependencyFactory = DependencyFactory::fromConnection($config, new ExistingConnection($connection));
        $this->cli = new Application('Lizy Migrations');
        $this->cli->setCatchExceptions(true);

        $this->cli->addCommands([
            new Command\DumpSchemaCommand($this->dependencyFactory),
            new Command\ExecuteCommand($this->dependencyFactory),
            new Command\GenerateCommand($this->dependencyFactory),
            new Command\LatestCommand($this->dependencyFactory),
            new Command\ListCommand($this->dependencyFactory),
            new Command\MigrateCommand($this->dependencyFactory),
            new Command\RollupCommand($this->dependencyFactory),
            new Command\StatusCommand($this->dependencyFactory),
            new Command\SyncMetadataCommand($this->dependencyFactory),
            new Command\VersionCommand($this->dependencyFactory),
        ]);
    }

    public static function useNamespace(?string $namespace, ?string $directory): void
    {
        $namespace = $namespace ?? 'Psancho\Galeizon\Migrations';
        $directory = $directory ?? './migrations';
        self::$config['migrations_paths'] = [$namespace => $directory];
    }

    private static function _canonizeMigrationPath(): string
    {
        $ns = array_key_first(self::$config['migrations_paths']);
        assert(is_string($ns));
        if (!File::isAbsolute(self::$config['migrations_paths'][$ns])) {
            self::$config['migrations_paths'][$ns]
            = realpath((dirname(__DIR__, 5) ?: '') . '/' . self::$config['migrations_paths'][$ns])
            ?: '';
        }
        $migrationsPath = self::$config['migrations_paths'][$ns];
        if (!file_exists($migrationsPath) || !is_dir($migrationsPath)) {
            throw new ConfException("CONF: MIGRATIONS FOLDER NOT FOUND");
        }
        return $ns;
    }

    /** @phpstan-return DoctrineDbParam */
    private function _getDbParams(): array
    {
        $dbConf = Conf::getInstance()->database;
        if (is_null($dbConf)
            || is_null($dbConf->migrations)
            || is_null($dbConf->migrations->credentials)
            || strlen($dbConf->migrations->credentials->login) === 0
            || strlen($dbConf->migrations->credentials->password) === 0
        ) {
            throw new ConfException("CONF_DB: incomplete migrations settings");
        }

        $dsn1 = explode(':', $dbConf->dsn, 2);
        if (count($dsn1) !== 2) {
            throw new ConfException("CONF_DB: DSN malformed");
        }

        $dsn2 = explode(';', $dsn1[1]);
        $dsn3 = ['engine' => $dsn1[0]];

        foreach ($dsn2 as $v) {
            $dsn0 = explode('=', $v);
            if (count($dsn0) !== 2) {
                throw new ConfException("CONF_DB: DSN malformed");
            }
            $dsn3[$dsn0[0]] = $dsn0[1];
        }
        /** @phpstan-var DoctrineDbParam $params */
        $params = [
            'dbname' => array_key_exists('dbname', $dsn3) ? $dsn3['dbname'] : '',
            'user' => $dbConf->migrations->credentials->login,
            'password' => $dbConf->migrations->credentials->password,
            'host' => array_key_exists('host', $dsn3) ? $dsn3['host'] : '',
            'driver' => 'pdo_' . $dsn3['engine'],
        ];
        if (array_key_exists('port', $dsn3)) {
            $params['port'] = (int) $dsn3['port'];
        }
        if (array_key_exists('charset', $dsn3)) {
            $params['charset'] = $dsn3['charset'];
        }
        return $params;
    }

    /** autoExit MUST be OFF before cli is invoked programatically */
    public function resetAutoExit(): self
    {
        $this->cli->setAutoExit(false);
        return $this;
    }

    /**
     * @param string $args
     *
     * @return int 0 if ok, != 0 if ko
     */
    public function run(...$args): int
    {
        $input = null;
        if ($args) {// @phpstan-ignore if.condNotBoolean
            assert(array_key_exists(0, $args));
            $command = $args[0];
            if (!$this->cli->has($command) && !$this->cli->isAutoExitEnabled()) {
                LogAdapter::warning("COMMAND_UNKNOWN: $command");
                return -1;
            }
            /** @phpstan-var non-empty-list<string> $args */
            array_unshift($args, 'migrations');
            $input = new ArgvInput($args);
        }
        return $this->cli->run($input);
    }

    public function getActualLatest(): string
    {
        return (string) $this->dependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest');
    }

    public static function getExpectedLatest(): string
    {
        $ns = self::_canonizeMigrationPath();
        assert(array_key_exists('migrations_paths', self::$config));
        $list = array_diff(scandir(self::$config['migrations_paths'][$ns]) ?: [], ['..', '.']);
        sort($list);
        return $ns . '\\' . basename(array_pop($list) ?? '', '.php');
    }

    public function isUpToDate(): bool
    {
        return 0 === $this->dependencyFactory->getMigrationStatusCalculator()->getNewMigrations()->count();
    }
}
