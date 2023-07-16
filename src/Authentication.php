<?php

namespace Peers;

use BackedEnum;
use Bottledcode\DurablePhp\DurableClientInterface;
use Bottledcode\DurablePhp\State\EntityId;
use Bottledcode\DurablePhp\State\Ids\StateId;
use Bottledcode\DurablePhp\State\Serializer;
use Bottledcode\SwytchFramework\Template\Interfaces\AuthenticationServiceInterface;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Peers\Model\Interfaces\User;

class Authentication implements AuthenticationServiceInterface
{
    public function __construct(private readonly DurableClientInterface $client, private readonly ReCache $cache)
    {
    }

    public function isAuthenticated(): bool
    {
        $user = $this->getUser();

        return $user !== null;
    }

    public function getUser(): ClerkUser|null
    {
        static $user = null;

        if ($user !== null) {
            return $user;
        }

        $session = $_COOKIE['__session'] ?? null;

        if ($session === null) {
            return null;
        }

        $keys = $this->cache->getOrSet('jwks3', function () {
            $keys = 'https://' . getenv('CLERK_FRONTEND_API') . '/.well-known/jwks.json';
            return file_get_contents($keys);
        });

        $keys = json_decode($keys, true);

        try {
            $jwt = JWT::decode($session, JWK::parseKeySet($keys));
        } catch (ExpiredException) {
            return null;
        }

        $user = $decodedUser = $jwt->sub;

        $entityId = new EntityId(User::class, $user);
        /**
         * @var User|null $storedUser
         */
        $storedUser = $this->client->getEntitySnapshot($entityId);
        if ($storedUser !== null) {
            // update the user after the request is finished
            register_shutdown_function(function() use ($decodedUser, $entityId) {
                fastcgi_finish_request();

                $client = new Client();
                $user = $client->get('https://api.clerk.com/v1/users/' . $decodedUser, ['headers' => ['Authorization' => 'Bearer ' . getenv('CLERK_PRIVATE_API_KEY')]]);
                $user = json_decode($user->getBody()->getContents(), true);

                $user = Serializer::deserialize($user, ClerkUser::class);

                $this->client->signalEntity($entityId, 'updateName', [$user->firstName, $user->lastName]);
                $this->client->signalEntity($entityId, 'updateImage', [$user->imageUrl]);
            });

            return $user = new ClerkUser($user, StateId::fromEntityId($entityId), $storedUser->getFirstName(), $storedUser->getLastName(), $storedUser->getImageUrl(), []);
        }

        $client = new Client();

        $user = $client->get('https://api.clerk.com/v1/users/' . $user, ['headers' => ['Authorization' => 'Bearer ' . getenv('CLERK_PRIVATE_API_KEY')]]);
        $user = json_decode($user->getBody()->getContents(), true);

        $user = Serializer::deserialize($user, ClerkUser::class);

        $this->client->signalEntity($entityId, 'updateName', [$user->firstName, $user->lastName]);
        $this->client->signalEntity($entityId, 'updateImage', [$user->imageUrl]);

        $user = $user->with(externalId: (string) StateId::fromEntityId($entityId));

        return $user;
    }

    public function isAuthorizedVia(BackedEnum ...$role): bool
    {
        return true;
    }
}
