<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

use PDO;
use PDOException;
use Psancho\Galeizon\App;

class UserIdentity
{
    public string $username = '';
    public string $provider = '';
    public string $firstname = '';
    public string $lastname = '';
    public string $email = '';

    /** @var class-string<self> */
    private static string $decorator = __CLASS__;

    /** @param class-string<self> $decorator */
    public static function setDecorator(?string $decorator): void
    {
        if (!is_null($decorator) && is_subclass_of($decorator, __CLASS__)) {
            self::$decorator = $decorator;
        }
    }

    public static function getDecorator(): string
    {
        return self::$decorator;
    }

    /** @return $this */
    public function changePassword(string $password): self
    {
        $sql = <<<SQL
        update user
        set password = sha(:pwd)
        where username = :username
        SQL;
        $stmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $stmt->bindValue(":username", $this->username);
        $stmt->bindValue(":pwd", $password);
        $stmt->execute();

        return $this;
    }

    /** @throws DuplicateUserException */
    public function create(): static
    {
        $this->_checkExistingEmailOrUsername();

        $sql = <<<SQL
        insert into `user` (username, firstname, lastname, email)
        values (:username, :firstname, :lastName, :email)
        SQL;
        $stmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $stmt->bindValue(":username", $this->username);
        $stmt->bindValue(":firstname", $this->firstname);
        $stmt->bindValue(":lastName", $this->lastname);
        $stmt->bindValue(":email", $this->email);
        $stmt->execute();

        return $this;
    }

    /** @throws DuplicateUserException */
    private function _checkExistingEmailOrUsername(): bool
    {
        $sql = <<<SQL
        SELECT id FROM `user` WHERE email = :email OR username = :username
        SQL;
        $sqlStmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $sqlStmt->setFetchMode(PDO::FETCH_COLUMN, 0);
        $sqlStmt->execute([
            ":username" => $this->username,
            ":email" => $this->email,
        ]);
        $found = $sqlStmt->fetch();
        $sqlStmt->closeCursor();

        if ($found !== false) {
            throw new DuplicateUserException;
        }

        return false;
    }

    /** @return $this */
    public function register(): static
    {
        return $this;
    }

    public function isRegistered(): bool
    {
        return true;
    }

    /** @return $this */
    public function updateLastAccess(): static
    {
        return $this;
    }

    /**
     * vérifications dans la surcharge, comme par ex le profil utilisateur
     */
    public function meetRequirements(mixed $requirements = null): bool
    {
        return true;
    }

    /** @return $this */
    public function update(self $targetUser): static
    {
        $sql = <<<SQL
        update `user`
        set firstname = :firstname, lastname = :lastName, username = :username
        where email = :email
        SQL;
        $stmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $stmt->bindValue(":firstname", strlen($this->firstname) > 0) ? $this->firstname : $targetUser->firstname;
        $stmt->bindValue(":lastName", strlen($this->lastname) > 0) ? $this->lastname : $targetUser->lastname;
        $stmt->bindValue(":email", strlen($this->email) > 0) ? $this->email : $targetUser->email;
        $stmt->bindValue(":username", strlen($this->username) > 0) ? $this->username : $targetUser->username;
        $stmt->execute();

        return $this;
    }

    private const int MAX_LENGTH_USERNAME = 32;
    private const int MAX_LENGTH_FIRSTNAME = 32;
    private const int MAX_LENGTH_LASTNAME = 32;
    private const int MAX_LENGTH_EMAIL = 64;

    public function validateInput(): ?string
    {
        if ($this->provider === '' && strlen($this->username) > self::MAX_LENGTH_USERNAME) {
            return sprintf('User ID too long (> %d B).', self::MAX_LENGTH_USERNAME);
        } else if (strlen($this->firstname) > self::MAX_LENGTH_FIRSTNAME) {
            return sprintf('Firstname too long (> %d B).', self::MAX_LENGTH_FIRSTNAME);
        } else if (strlen($this->lastname) > self::MAX_LENGTH_LASTNAME) {
            return sprintf('Lastname too long (> %d B).', self::MAX_LENGTH_LASTNAME);
        } else if ($this->email === '') {
            return sprintf('Email required.');
        } else if (strlen($this->email) > self::MAX_LENGTH_EMAIL) {
            return sprintf('Email too long (> %d B).', self::MAX_LENGTH_EMAIL);
        } else {
            return null;
        }
    }

    public static function authenticate(string $username, string $password): ?static
    {
        $sql = <<<SQL
        select username, firstname, lastname, email
        from user
        where username = ? and password = sha(?)
        SQL;
        $sqlStmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $sqlStmt->setFetchMode(PDO::FETCH_CLASS, self::$decorator);
        $sqlStmt->execute([$username, $password]);
        /** @var ?static $user */
        $user = $sqlStmt->fetch() ?: null;
        $sqlStmt->closeCursor();
        $user?->updateLastLogin();

        return $user;
    }

    /** à surcharger si nécessaire */
    public function isActive(): bool
    {
        $sql = <<<SQL
        select active from user where username = ?
        SQL;
        $sqlStmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $sqlStmt->setFetchMode(PDO::FETCH_COLUMN, 0);
        $sqlStmt->execute([$this->username]);
        $active = $sqlStmt->fetch();
        $sqlStmt->closeCursor();

        return $active === 1;
    }

    /** @return $this */
    protected function updateLastLogin(): static
    {
        $sql = <<<SQL
        update user set last_login = now() where username = :username
        SQL;
        $stmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $stmt->bindValue(":username", $this->username);
        $stmt->execute();

        return $this;
    }

    public static function retrieveByUsername(string $username): ?static
    {
        $sql = <<<SQL
        select username, firstname, lastname, email
        from `user`
        where username = ?
        SQL;
        $sqlStmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $sqlStmt->setFetchMode(PDO::FETCH_CLASS, self::$decorator);
        $sqlStmt->execute([$username]);
        /** @var ?static $user */
        $user = $sqlStmt->fetch() ?: null;
        $sqlStmt->closeCursor();

        return $user;
    }

    public static function retrieveEmailAddress(string $contact): ?static
    {
        $sql = <<<SQL
        select username, email
        from `user`
        where username = (@contact := ?) or email = @contact
        SQL;
        $sqlStmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $sqlStmt->setFetchMode(PDO::FETCH_CLASS, self::$decorator);
        $sqlStmt->execute([$contact]);
        /** @var ?static $user */
        $user = $sqlStmt->fetch() ?: null;
        $sqlStmt->closeCursor();

        return $user;
    }

    /**
     * Doit être surchargée pour récupérer les données liées à la classe surchargeant
     *
     * @return $this
     */
    public function retrieveDecorated(): static
    {
        return $this;
    }

    /**
     * Doit être surchargée car la propriété n'est gérée que par la classe decorative
     *
     * @return list<string>
     */
    public static function getAdminEmails(): array
    {
        return [];
    }
}
