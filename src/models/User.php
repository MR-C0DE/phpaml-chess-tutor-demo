<?php

declare(strict_types=1);

namespace App\Models;

use AML\Data\Entity;
use AML\Data\MongoDB\Metadata\{Collection, DocumentId};

#[Collection('users')]
final class User extends Entity
{
    #[DocumentId]
    public string $id;
    public string $name;
    public string $email;
    public string $passwordHash;
    public string $createdAt;
}
