<?php

namespace Peers;

use BackedEnum;
use Bottledcode\DurablePhp\DurableClient;
use Bottledcode\DurablePhp\State\EntityId;
use Bottledcode\DurablePhp\State\Serializer;
use Bottledcode\SwytchFramework\Template\Interfaces\AuthenticationServiceInterface;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Peers\Model\Interfaces\User;

class Authentication implements AuthenticationServiceInterface
{
    public function __construct(private DurableClient $client)
    {
    }

    public function isAuthenticated(): bool
    {
        $user = $this->getUser();

        return $user !== null;
    }

    public function getUser(): User|null
    {
        $session = $_COOKIE['__session'] ?? null;

        if ($session === null) {
            return null;
        }

        $keys = 'https://' . getenv('CLERK_FRONTEND_API') . '/.well-known/jwks.json';
        $keys = json_decode(file_get_contents($keys), true);

        $jwt = JWT::decode($session, JWK::parseKeySet($keys));

        $user = $jwt->sub;
        var_dump($user);

        $storedUser = $this->client->getEntitySnapshot(new EntityId(User::class, $user));
        if($storedUser !== null) {
            var_dump($storedUser);
        }

        $client = new Client();

        $user = $client->get('https://api.clerk.com/v1/users/' . $user, ['headers' => ['Authorization' => 'Bearer ' . getenv('CLERK_PRIVATE_API_KEY')]]);
        $user = json_decode($user->getBody()->getContents(), true);

        if($storedUser === null) {

        }

        return Serializer::deserialize($user, ClerkUser::class);
    }

    public function isAuthorizedVia(BackedEnum ...$role): bool
    {
        return true;
    }
}
