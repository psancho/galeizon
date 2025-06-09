<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Control;

use Closure;
use Psancho\Galeizon\Adapter\LogAdapter;
use Psancho\Galeizon\Adapter\MailerAdapter;
use Psancho\Galeizon\Adapter\MigrationsAdapter;
use Psancho\Galeizon\Adapter\SlimAdapter\Endpoint;
use Psancho\Galeizon\Model\Conf;
use Psancho\Galeizon\Model\ConfException;
use Psancho\Galeizon\Model\FireAndForget;
use Psancho\Galeizon\View\StatusCode;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\ServerRequest;
use Symfony\Component\Mime\Email;
use Throwable;

class MigrationController extends SlimController
{
    /**
     * PUT /migrations
     *
     * statut réponse :
     * - HTTP 503 (service unavailable) le code exécuté n'est pas à jour
     * - HTTP 204 (no content) le schéma est déjà à jour, rien à faire
     * - HTTP 202 (accepted) la mise à jour a débuté
     *
     * @param array<string, string> $args
     */
    #[Endpoint(verb: "PUT", path: "/migrations", authz: "adminSchema")]
    public function put(ServerRequest $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $expectedLatest = (string) $request->getBody();

        try {
            $actualLatest = MigrationsAdapter::getInstance()->getActualLatest();
        } catch (ConfException $e) {
            LogAdapter::warning($e);
            return $response->withStatus(StatusCode::HTTP_500_INTERNAL_SERVER_ERROR);
        }

        if ($actualLatest !== $expectedLatest) {
            return $response->withStatus(StatusCode::HTTP_503_SERVICE_UNAVAILABLE);
        }

        if (MigrationsAdapter::getInstance()->isUpToDate()) {
            return $response->withStatus(StatusCode::HTTP_204_NO_CONTENT);
        }

        FireAndForget::getInstance()->addJob(
            Closure::fromCallable([self::class, 'doMigrate'])
        );

        return $response->withStatus(StatusCode::HTTP_202_ACCEPTED);
    }

    public static function doMigrate(): void
    {
        try {
            $exitCode = MigrationsAdapter::getInstance()->resetAutoExit()->run('migrate', '-n');
        } catch (Throwable $e) {
            LogAdapter::error($e);
            $exitCode = -1;
        }
        if ($exitCode !== 0) {
            LogAdapter::warning("MIGRATION_FAILURE");
        } else {
            LogAdapter::info("MIGRATION_SUCCESS");
        }

        if (($from = Conf::getInstance()->database->migrations->reportFrom ?? '') !== ''
            && count($to = Conf::getInstance()->database->migrations->reportTo ?? []) !== 0
        ) {
            $email = (new Email)
            ->subject("Migration result")
            ->from($from)
            ->to(...$to)
            ->text(sprintf(
                ($exitCode === 0)
                    ? "Migration complete on %s.\nDB is up to date."
                    : "Migration failure on %s.\nPlease, see logs.",
                Conf::getInstance()->database->migrations->reportEnv ?? 'unkown environment'))
            ;
            MailerAdapter::getInstance()->send($email);
        }
    }
}
