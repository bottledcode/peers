<?php

namespace Peers;

use Crell\fp\Evolvable;
use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\KeyType;
use Crell\Serde\Renaming\Cases;

readonly class ClerkUser
{
    use Evolvable {
        Evolvable::with as private _with;
    }

    public function __construct(
        public string $id,
        #[Field(renameWith: Cases::snake_case)]
        public string $externalId = '',
        #[Field(renameWith: Cases::snake_case)]
        public string $firstName = '',
        #[Field(renameWith: Cases::snake_case)]
        public string $lastName = '',
        #[Field(renameWith: Cases::snake_case)]
        public string $imageUrl = '',
        #[Field(renameWith: Cases::snake_case)]
        #[DictionaryField(arrayType: 'string', keyType: KeyType::String)]
        public array $privateMetadata = [],
        public bool $changed = false
    )
    {
    }

    public function generateUserProfileLink(): string
    {
        return getenv('CLERK_FRONTEND_API') . '/user';
    }
}
