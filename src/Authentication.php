<?php

namespace Peers;

use BackedEnum;
use Bottledcode\DurablePhp\DurableClient;
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
    public function __construct(private DurableClientInterface $client)
    {
    }

    public function isAuthenticated(): bool
    {
        $user = $this->getUser();

        return $user !== null;
    }

    public function getUser(): ClerkUser|null
    {
        $session = $_COOKIE['__session'] ?? null;

        if ($session === null) {
            return null;
        }

        $keys = 'https://' . getenv('CLERK_FRONTEND_API') . '/.well-known/jwks.json';
        $keys = json_decode(file_get_contents($keys), true);

        try {
            $jwt = JWT::decode($session, JWK::parseKeySet($keys));
        } catch(ExpiredException) {
            return null;
        }

        $user = $jwt->sub;

        try {
            $entityId = new EntityId(User::class, $user);
            /**
             * @var User $storedUser
             */
            $storedUser = $this->client->getEntitySnapshot($entityId);
        } catch(\Exception) {
            $storedUser = null;
        }
        if($storedUser !== null) {
            return new ClerkUser($user, StateId::fromEntityId($entityId), $storedUser->getFirstName(), $storedUser->getLastName(), $storedUser->getImageUrl(), []);
        }

        $client = new Client();

        $user = $client->get('https://api.clerk.com/v1/users/' . $user, ['headers' => ['Authorization' => 'Bearer ' . getenv('CLERK_PRIVATE_API_KEY')]]);
        $user = json_decode($user->getBody()->getContents(), true);

        $user = Serializer::deserialize($user, ClerkUser::class);

        if($storedUser === null) {
            $id = new EntityId(User::class, $user->id);
            $this->client->signalEntity($id, 'updateName', [$user->firstName, $user->lastName]);
            $this->client->signalEntity($id, 'updateImage', [$user->imageUrl]);
        }

        return $user;
    }

    public function isAuthorizedVia(BackedEnum ...$role): bool
    {
        return true;
    }
}
