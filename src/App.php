<?php
declare(strict_types=1);

namespace Psancho\Galeizon;

use ErrorException;
use PDO;
use Psancho\Galeizon\Model\Auth\UserIdentity;
use Psancho\Galeizon\Model\Conf;
use Psancho\Galeizon\Model\Database\Connection;
use Psancho\Galeizon\Pattern\Singleton;

require_once dirname(__DIR__, 3) . '/autoload.php';

class App extends Singleton
{
    public protected(set) Conf $conf;// @phpstan-ignore property.uninitialized
    public protected(set) PDO $dataCnx;// @phpstan-ignore property.uninitialized
    public protected(set) PDO $authCnx;// @phpstan-ignore property.uninitialized

    #[\Override]
    protected function build(): void
    {
        self::threatErrorAsException();
        $this->conf = Conf::getInstance();
        $this->dataCnx = Connection::getInstance($this->conf->database);
        $this->authCnx = Connection::getInstance($this->conf->auth?->database);

        UserIdentity::setDecorator($this->conf->app?->userDecorator);
    }

    protected static function threatErrorAsException(): void
    {
        set_error_handler(function (int $errNo, string $errStr, string $errFile, int $errLine)
        {
            // une exception dans un destructeur ça ne fait pas propre, donc je gère
            $backtrace = debug_backtrace();
            $e = new ErrorException($errStr, 0, $errNo, $errFile, $errLine);
            if ($errNo === E_USER_DEPRECATED || $errNo === E_DEPRECATED) {
                echo $e;
            } else if (isset($backtrace[2]['function']) && '__destruct' === $backtrace[2]['function']) {
                // LogProvider::error($e);
                echo 'exception_in_destructor';
            } else {
                throw $e;
            }

            return true;
        });
    }
}
