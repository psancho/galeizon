<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

use PDO;
use PDOException;
use Psancho\Galeizon\Adapter\UnimplementedException;
use Psancho\Galeizon\App;

class Registration extends UserIdentity
{
    public string $password = '';

    public function alreadyExists(): bool
    {
        $sql = <<<SQL
        select username
        from user
        where username = ? or email = ?
        SQL;
        $stmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $stmt->setFetchMode(PDO::FETCH_COLUMN, 0);
        $stmt->execute([$this->username, $this->email]);
        /** @var string|false $found */
        $found = $stmt->fetch();
        $stmt->closeCursor();

        return $found !== false;
    }

    #[\Override]
    public function register(): static
    {
        if (method_exists(UserIdentity::getDecorator(), 'fromObject')) {
            /** @var UserIdentity $user */
            $user = (UserIdentity::getDecorator())::fromObject($this);
            $user->register();
        } else {
            throw new UnimplementedException("REGISTER_NOT_IMPLEMENTED", 1);
        }
        return $this;
    }

    public static function fromObject(object $object): self
    {
        $typed = new self;

        if (property_exists($object, 'username') && is_string($object->username)) {
            $typed->username = $object->username;
        }
        if (property_exists($object, 'password') && is_string($object->password)) {
            $typed->password = $object->password;
        }
        if (property_exists($object, 'email') && is_string($object->email)) {
            $typed->email = $object->email;
        }
        if (property_exists($object, 'firstname') && is_string($object->firstname)) {
            $typed->firstname = $object->firstname;
        }
        if (property_exists($object, 'lastname') && is_string($object->lastname)) {
            $typed->lastname = $object->lastname;
        }

        return $typed;
    }
}
