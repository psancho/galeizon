<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Adapter;

use Psancho\Galeizon\Model\Conf;
use Psancho\Galeizon\Pattern\Singleton;
use RuntimeException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\SendmailTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mime\Email;

class MailerAdapter extends Singleton
{

    const string REGEX_EMAIL_RFC_5322 = "/(?:[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*|\"(?:[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x21\\x23-\\x5b\\x5d-\\x7f]|\\\\[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x21-\\x5a\\x53-\\x7f]|\\\\[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f])+)\\])/";
    const string REGEX_EMAIL_W3C = "/^[a-zA-Z0-9.!#$%&’*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\\.[a-zA-Z0-9-]+)*$/";

    protected const string TRANSPORT_SMTP = "smtp";
    protected const string TRANSPORT_SENDMAIL = "sendmail";

    protected ?Dsn $dsn = null;
    protected ?Mailer $mailer = null;
    protected bool $isAvailable = false;

    #[\Override]
    protected function build(): void
    {
        $dsn = Conf::getInstance()->mailer?->dsn;
        $this->isAvailable = !is_null($dsn);

        if ($this->isAvailable) {
            assert(is_string($dsn));
            $this->dsn = Dsn::fromString($dsn);
        }
    }

    public function send(Email $email): void
    {
        if (!$this->isAvailable) {
            throw new RuntimeException('Cannot send an email: symphony/mailer lib is missing');
        }

        // Crée ou récupère le mailer
        $this->getMailer()->send($email);
    }

    /** @see https://emailregex.com/ */
    public static function isEmailAddress(string $address, string $regex = self::REGEX_EMAIL_RFC_5322): bool
    {
        $ok = preg_match($regex, $address);
        if ($ok === false) {
            throw new RuntimeException("Error while checking email address");

        } else {
            return $ok === 1;
        }
    }

    /** @throws RuntimeException */
    protected function getMailer(): Mailer
    {
        if (!$this->isAvailable) {
            throw new RuntimeException("%ailer not available");
        }
        if (is_null($this->mailer)) {
            assert($this->dsn instanceof Dsn);

            // TODO les *TransportFactory attendent un LoggerInterface en 3e param
            //      à pluguer avec Monolog

            switch ($this->dsn->getScheme()) {
            case static::TRANSPORT_SMTP:
                $transportFactory = new EsmtpTransportFactory;
                break;
            case static::TRANSPORT_SENDMAIL:
                $transportFactory = new SendmailTransportFactory;
                break;
            default :
                throw new RuntimeException("DSN scheme not supported");
            }

            $transport = $transportFactory->create($this->dsn);
            $this->mailer = new Mailer($transport);
        }

        assert(!is_null($this->mailer));

        return $this->mailer;
    }
}
