<?php
declare(strict_types=1);

namespace Psancho\Galeizon;

use ErrorException;
use PDO;
use Psancho\Galeizon\Adapter\MailerAdapter;
use Psancho\Galeizon\Model\Conf;
use Psancho\Galeizon\Model\Database\Connection;
use Psancho\Galeizon\Pattern\Singleton;
use Symfony\Component\Mime\Email;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class App extends Singleton
{
    public protected(set) Conf $conf;// @phpstan-ignore property.uninitialized
    public protected(set) PDO $dbCnx;// @phpstan-ignore property.uninitialized

    #[\Override]
    protected function build(): void
    {
        self::threatErrorAsException();
        $this->conf = Conf::getInstance();
        $this->dbCnx = Connection::getInstance($this->conf->database);
        self::caca();
    }

    protected static function caca(): void
    {
        $email = (new Email())
        ->subject('test email')
        ->from('psancho13@gmail.com')
        ->html('<body><p>youhou</p></body>')
        ->to('tcho@club-internet.fr');

        MailerAdapter::getInstance()->send($email);
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
