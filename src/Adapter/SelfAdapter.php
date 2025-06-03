<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Adapter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\RequestOptions;
use Psancho\Galeizon\Model\Auth\GrantFlowType;
use Psancho\Galeizon\Model\Auth\OwnerType;
use Psancho\Galeizon\Model\Auth\ResponseToken;
use Psancho\Galeizon\Model\Conf;
use Psancho\Galeizon\Model\Conf\SelfConf as ConfSelf;
use Psancho\Galeizon\View\Format;
use Psancho\Galeizon\View\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * DEV only!
 *
 * À n'utiliser que dans un script de dev consommant l'API elle-même
 */
class SelfAdapter
{
    protected ConfSelf $conf;
    protected Client $client;
    protected ?ResponseToken $authz = null;

    protected const REQUIRED_OPTIONS = ['baseUrl', 'clientId', 'clientSecret'];

    public static function getInstance(): self
    {
        static $instance = null;
        if (!isset($instance)) {
            $instance = new self;
        }
        /** @var self $instance */
        return $instance;
    }

    protected function __construct()
    {
        if (is_null(Conf::getInstance()->self)) {
            throw new RuntimeException("NO_SELF_CONFIG", 1);
        }
        $this->conf = Conf::getInstance()->self;
        $errors = [];
        foreach (self::REQUIRED_OPTIONS as $option) {
            if ($this->conf->{$option} === '') {// @phpstan-ignore property.dynamicName
                $errors[] = "CONFIG_MISSING: self.$option";
            }
        }
        if (count($errors) > 0) {
            throw new RuntimeException("CONFIG_MISCONFIGURED:\n" . print_r($errors, true));
        }
        $this->client = new Client(['base_uri' => $this->conf->baseUrl]);
    }

    /** @param array<string, mixed> $options */
    public function sendRequest(string $method, string $route, array $options,
        ?OwnerType $ownerType = null,
        ?string $scope = null
    ): ResponseInterface
    {
        $method = strtoupper($method);
        if (!in_array($method, ['POST', 'GET', 'PATCH', 'PUT', 'DELETE'], true)) {
            throw new RuntimeException("Error Processing Request", 1);
        }
        if (!array_key_exists(RequestOptions::HEADERS, $options)) {
            $options[RequestOptions::HEADERS] = [];
        }
        assert(is_array($options[RequestOptions::HEADERS]));
        if (!is_null($ownerType) && !is_null($authz = $this->getAuthz($ownerType, $scope))) {
            $options[RequestOptions::HEADERS]['Authorization'] = "$authz->token_type $authz->access_token";
        }

        try {
            $response = $this->client->request($method, $route, $options);
            return $response;
        } catch (BadResponseException $e) {
            return $e->getResponse();
        }
    }

    protected function getAuthz(OwnerType $ownerType, ?string $scope = null): ?ResponseToken
    {
        if (is_null($this->authz)) {
            $this->authz = $this->postToken($ownerType, $scope);
        }
        return $this->authz;
    }

    protected function postToken(OwnerType $ownerType, ?string $scope = null): ?ResponseToken
    {
        switch ($ownerType) {
        case OwnerType::client:
            $jsonPayload = [
                'grant_type' => GrantFlowType::client_credentials->name,
                'client_id' => $this->conf->clientId,
                'client_secret' => $this->conf->clientSecret,
            ];
            break;

        case OwnerType::user:
            $jsonPayload = [
                'grant_type' => GrantFlowType::password->name,
                'username' => $this->conf->username,
                'password' => $this->conf->password,
                'client_id' => $this->conf->clientId,
            ];
            break;
        default:
            throw new RuntimeException("OWNER_TYPE UNKNOWN");
        }

        try {
            $options = [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ],
                RequestOptions::JSON => $jsonPayload,
            ];
            if (!is_null($scope)) {
                $options[RequestOptions::JSON]['scope'] = $scope;
            }
            $response = $this->client->post('token', $options);
            return self::_extractAuthz($response);

        } catch (BadResponseException $e) {
            LogAdapter::warning("POST_TOKEN_FAILURE", ['response' => $e->getResponse()]);
            return null;
        }
    }

    private static function _extractAuthz(ResponseInterface $response): ResponseToken
    {
        $response->getBody()->rewind();
        $raw = Json::tryUnserialize((string) $response->getBody());
        if (!is_object($raw)) {
            throw new RuntimeException("POST_TOKEN: NOT_A_JSON_OBJECT", 1);
        }

        $responseToken = new ResponseToken;
        if (property_exists($raw, 'expires_in') && is_int($raw->expires_in)) {
            $responseToken->expires_in = $raw->expires_in;
        }
        if (property_exists($raw, 'access_token') && is_string($raw->access_token)) {
            $responseToken->access_token = $raw->access_token;
        }
        if (property_exists($raw, 'token_type') && is_string($raw->token_type)) {
            $responseToken->token_type = $raw->token_type;
        }

        return $responseToken;
    }

    public static function printBody(StreamInterface $body): void
    {
        if (($size = $body->getSize()) > 0) {
            printf("Body (%s):\n%s\n----\n",
            Format::bytes($size),
            (string) $body);
        }
    }
}
